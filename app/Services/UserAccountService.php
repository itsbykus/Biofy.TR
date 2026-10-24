<?php

namespace App\Services;

use App\Exceptions\UserFacingException;
use App\Models\FeatureDefinition;
use App\Models\MediaFile;
use App\Models\Package;
use App\Models\Profile;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserFeatureOverride;
use App\Models\UserPackageAssignment;
use App\Notifications\GenericTemplatedNotification;
use App\Services\Mail\Mailer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAccountService
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Entitlements $entitlements,
        private readonly SlugService $slugs,
        private readonly AuditLogger $audit,
        private readonly SecurityLogger $security,
        private readonly Mailer $mailer,
    ) {
    }

    /** Creates the account, its default package assignment and a first (draft) bio page. */
    public function register(array $data, ?string $verifiedEmailAt = null): User
    {
        $username = $this->slugs->normalize($data['username']);

        if ($problem = $this->slugs->problemWithUsername($username)) {
            throw ValidationException::withMessages(['username' => __($problem)]);
        }

        // The account handle doubles as the first page's slug, so it must be free there too.
        if ($problem = $this->slugs->problemWithProfileSlug($username)) {
            throw ValidationException::withMessages(['username' => __($problem)]);
        }

        $user = DB::transaction(function () use ($data, $username, $verifiedEmailAt) {
            $user = new User([
                'name' => trim($data['name']),
                'username' => $username,
                'email' => Str::lower(trim($data['email'])),
                'password' => $data['password'] ?? null,
                'locale' => in_array($data['locale'] ?? null, config('biofy.locales'), true) ? $data['locale'] : 'tr',
            ]);

            if ($verifiedEmailAt !== null) {
                $user->email_verified_at = $verifiedEmailAt;
            }

            $user->password_changed_at = isset($data['password']) ? now() : null;
            $user->save();

            if ($package = $this->entitlements->defaultPackage()) {
                UserPackageAssignment::query()->create([
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                    'starts_at' => now(),
                    'source' => 'registration',
                ]);
            }

            app(ProfileService::class)->create($user, ['slug' => $username, 'display_name' => $user->name]);

            return $user;
        });

        if (! $user->hasVerifiedEmail()) {
            $this->mailer->send($user, new \App\Notifications\VerifyEmailNotification);
        }

        $this->mailer->send($user, new GenericTemplatedNotification('welcome', [], route('dashboard')));

        return $user;
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => $newPassword,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $this->security->log($user, SecurityLogger::PASSWORD_CHANGED);
        $this->notifySecurity($user, 'password_changed');
    }

    public function suspend(User $user, string $reason, User $actor): void
    {
        if ($user->isSuperAdmin()) {
            throw new UserFacingException(__('admin.users.cannot_suspend_superadmin'));
        }

        $user->forceFill([
            'status' => User::STATUS_SUSPENDED,
            'suspended_at' => now(),
            'suspension_reason' => Str::limit($reason, 490),
            'remember_token' => Str::random(60),
        ])->save();

        $this->terminateSessions($user);
        $user->tokens()->delete();
        $this->entitlements->flushUser($user);
        app(Domains\DomainService::class)->flushCache();

        $this->audit->log('user.suspended', $user, ['reason' => $reason], $actor);
        $this->security->log($user, SecurityLogger::ACCOUNT_SUSPENDED);
        $this->mailer->send($user, new GenericTemplatedNotification('account_suspended', ['reason' => $reason]));
    }

    public function reactivate(User $user, User $actor): void
    {
        $user->forceFill(['status' => User::STATUS_ACTIVE, 'suspended_at' => null, 'suspension_reason' => null])->save();
        $this->entitlements->flushUser($user);
        app(Domains\DomainService::class)->flushCache();

        $this->audit->log('user.reactivated', $user, [], $actor);
        $this->security->log($user, SecurityLogger::ACCOUNT_REACTIVATED);
        $this->mailer->send($user, new GenericTemplatedNotification('account_reactivated', ['date' => now()->toDateString()], route('login')));
    }

    public function requestDeletion(User $user): void
    {
        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            throw new UserFacingException(__('account.last_superadmin'));
        }

        $user->forceFill(['deletion_requested_at' => now(), 'remember_token' => Str::random(60)])->save();
        $this->entitlements->flushUser($user);
        $this->security->log($user, SecurityLogger::DELETION_REQUESTED);

        $graceDays = $this->settings->int('privacy.account_deletion_grace_days');
        $this->mailer->send($user, new GenericTemplatedNotification('deletion_requested', ['date' => now()->addDays($graceDays)->toDateString()]));
    }

    public function cancelDeletion(User $user, ?User $actor = null): void
    {
        $user->forceFill(['deletion_requested_at' => null])->save();
        $this->entitlements->flushUser($user);
        $this->security->log($user, SecurityLogger::DELETION_CANCELLED);

        if ($actor && $actor->id !== $user->id) {
            $this->audit->log('user.deletion_cancelled', $user, [], $actor);
        }
    }

    /** Removes the account, its pages, analytics, domains and uploaded files. Irreversible. */
    public function deletePermanently(User $user, ?User $actor): void
    {
        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            throw new UserFacingException(__('account.last_superadmin'));
        }

        $snapshot = ['id' => $user->id, 'email_hash' => hash('sha256', $user->email), 'username' => $user->username];

        DB::transaction(function () use ($user) {
            MediaFile::query()->where('user_id', $user->id)->get()->each(function (MediaFile $m) {
                Storage::disk($m->disk)->delete($m->path);
            });

            $this->terminateSessions($user);
            $user->tokens()->delete();
            $user->delete();
        });

        app(Domains\DomainService::class)->flushCache();
        $this->audit->log('user.deleted', null, $snapshot, $actor);
    }

    public function assignPackage(User $user, Package $package, ?Carbon $startsAt, ?Carbon $endsAt, ?string $note, User $actor): UserPackageAssignment
    {
        if ($package->is_archived) {
            throw ValidationException::withMessages(['package_id' => __('packages.archived_cannot_assign')]);
        }

        if ($package->custom_for_user_id !== null && $package->custom_for_user_id !== $user->id) {
            throw ValidationException::withMessages(['package_id' => __('packages.custom_other_user')]);
        }

        $startsAt ??= now();

        $assignment = DB::transaction(function () use ($user, $package, $startsAt, $endsAt, $note, $actor) {
            // Any assignment still running when the new one starts ends at that moment.
            UserPackageAssignment::query()->where('user_id', $user->id)->whereNull('revoked_at')
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $startsAt))
                ->where('starts_at', '<=', $startsAt)
                ->update(['ends_at' => $startsAt]);

            return UserPackageAssignment::query()->create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'source' => 'admin',
                'note' => $note,
                'assigned_by' => $actor->id,
            ]);
        });

        $this->entitlements->flushUser($user);
        $this->audit->log('user.package_assigned', $user, ['package' => $package->key, 'starts_at' => $startsAt->toIso8601String(), 'ends_at' => $endsAt?->toIso8601String(), 'note' => $note], $actor);

        return $assignment;
    }

    public function revokeAssignment(UserPackageAssignment $assignment, User $actor): void
    {
        $assignment->update(['revoked_at' => now()]);
        $this->entitlements->flushUser($assignment->user);
        $this->audit->log('user.package_revoked', $assignment->user, ['package_id' => $assignment->package_id], $actor);
    }

    public function setOverride(User $user, string $key, string $value, ?string $note, ?Carbon $expiresAt, User $actor): void
    {
        $definition = FeatureDefinition::query()->where('key', $key)->firstOrFail();
        $value = $definition->isBoolean() ? ($value === '1' ? '1' : '0') : (string) max(-1, (int) $value);

        UserFeatureOverride::query()->updateOrCreate(
            ['user_id' => $user->id, 'feature_key' => $key],
            ['value' => $value, 'note' => $note, 'expires_at' => $expiresAt, 'created_by' => $actor->id],
        );

        $this->entitlements->flushUser($user);
        $this->audit->log('user.override_set', $user, ['feature' => $key, 'value' => $value, 'expires_at' => $expiresAt?->toIso8601String()], $actor);
    }

    public function removeOverride(User $user, string $key, User $actor): void
    {
        UserFeatureOverride::query()->where('user_id', $user->id)->where('feature_key', $key)->delete();
        $this->entitlements->flushUser($user);
        $this->audit->log('user.override_removed', $user, ['feature' => $key], $actor);
    }

    public function syncRoles(User $user, array $roleKeys, User $actor): void
    {
        if (! $actor->isSuperAdmin()) {
            throw new UserFacingException(__('admin.users.only_superadmin_roles'));
        }

        $roles = Role::query()->whereIn('key', $roleKeys)->get();
        $removingSuper = $user->isSuperAdmin() && ! $roles->contains('key', Role::SUPERADMIN);

        if ($removingSuper && $this->superAdminCount() <= 1) {
            throw new UserFacingException(__('account.last_superadmin'));
        }

        $before = $user->roles->pluck('key')->all();
        $user->roles()->sync($roles->mapWithKeys(fn (Role $r) => [$r->id => ['assigned_by' => $actor->id, 'created_at' => now()]])->all());
        $user->load('roles');

        $this->audit->log('user.roles_changed', $user, ['before' => $before, 'after' => $roles->pluck('key')->all()], $actor);
        $this->terminateSessions($user);
    }

    public function setProfileStatus(Profile $profile, string $status, ?string $reason, User $actor): void
    {
        if (! in_array($status, [Profile::STATUS_DRAFT, Profile::STATUS_PUBLISHED, Profile::STATUS_SUSPENDED, Profile::STATUS_ARCHIVED], true)) {
            throw ValidationException::withMessages(['status' => __('validation.in', ['attribute' => 'status'])]);
        }

        if ($status === Profile::STATUS_PUBLISHED && $profile->published_version_id === null) {
            throw new UserFacingException(__('profiles.nothing_published'));
        }

        $before = $profile->status;
        $profile->forceFill(['status' => $status, 'suspension_reason' => $status === Profile::STATUS_SUSPENDED ? $reason : null])->save();
        $this->audit->log('profile.status_changed', $profile, ['before' => $before, 'after' => $status, 'reason' => $reason], $actor);
    }

    public function terminateSessions(User $user, ?string $exceptSessionId = null): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    public function unlinkGoogle(User $user): void
    {
        if (! $user->hasPassword()) {
            throw new UserFacingException(__('account.set_password_before_unlink'));
        }

        SocialAccount::query()->where('user_id', $user->id)->where('provider', 'google')->delete();
        $this->security->log($user, SecurityLogger::GOOGLE_UNLINKED);
    }

    /** Personal data export (KVKK / GDPR right of access). */
    public function export(User $user): array
    {
        $user->load(['profiles.blocks', 'profiles.socialLinks', 'customDomains', 'packageAssignments.package', 'socialAccounts']);

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'username', 'email', 'locale', 'timezone', 'status', 'created_at', 'email_verified_at', 'last_login_at']),
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'linked_accounts' => $user->socialAccounts->map->only(['provider', 'provider_email', 'created_at'])->all(),
            'packages' => $user->packageAssignments->map(fn ($a) => ['package' => $a->package?->key, 'starts_at' => $a->starts_at, 'ends_at' => $a->ends_at])->all(),
            'profiles' => $user->profiles->map(fn (Profile $p) => [
                'slug' => $p->slug,
                'status' => $p->status,
                'details' => $p->only(['display_name', 'title', 'bio_short', 'bio_long', 'contact_email', 'contact_phone', 'location', 'seo_title', 'seo_description']),
                'theme' => ['key' => $p->theme_key, 'settings' => $p->theme_settings],
                'tracking' => $p->tracking,
                'blocks' => $p->blocks->map->only(['type', 'data', 'is_visible', 'starts_at', 'ends_at'])->all(),
                'social_links' => $p->socialLinks->map->only(['platform', 'url'])->all(),
                'analytics_daily' => DB::table('analytics_daily_summaries')->where('profile_id', $p->id)->where('block_id', 0)->orderBy('date')->get(['date', 'views', 'unique_visitors', 'clicks'])->all(),
            ])->all(),
            'custom_domains' => $user->customDomains->map->only(['hostname', 'status', 'created_at'])->all(),
            'short_links' => \App\Models\ShortLink::query()->where('user_id', $user->id)->get(['code', 'target_url', 'title', 'clicks', 'created_at'])->all(),
            'media_files' => MediaFile::query()->where('user_id', $user->id)->get(['path', 'mime', 'size', 'created_at'])->all(),
            'security_events' => SecurityEvent::query()->where('user_id', $user->id)->latest('id')->limit(500)->get(['type', 'ip_address', 'user_agent', 'created_at'])->all(),
        ];
    }

    public function superAdminCount(): int
    {
        return User::query()->whereHas('roles', fn ($q) => $q->where('key', Role::SUPERADMIN))->count();
    }

    public function notifySecurity(User $user, string $eventKey): void
    {
        $this->mailer->send($user, new GenericTemplatedNotification('security_alert', [
            'event' => __('security.events.'.$eventKey, [], $user->preferredLocale()),
            'time' => now()->setTimezone($user->timezone ?: $this->settings->get('site.timezone'))->format('d.m.Y H:i'),
            'ip' => request()->ip(),
        ]));
    }

    public function verifyPassword(User $user, ?string $password): bool
    {
        return $user->hasPassword() && $password !== null && Hash::check($password, $user->password);
    }
}
