<?php

namespace App\Services;

use App\Blocks\BlockRegistry;
use App\Exceptions\EditConflictException;
use App\Exceptions\UserFacingException;
use App\Models\MediaFile;
use App\Models\Profile;
use App\Models\ProfileBlock;
use App\Models\ProfileSlugRedirect;
use App\Models\ProfileSocialLink;
use App\Models\ProfileVersion;
use App\Models\User;
use App\Themes\ThemeRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Profile lifecycle: create, edit (draft), publish (immutable snapshot), revert,
 * archive. Visitors only ever see a published snapshot, so editing the draft can
 * never change the live page by accident.
 */
class ProfileService
{
    private const KEEP_VERSIONS = 20;

    public function __construct(
        private readonly Entitlements $entitlements,
        private readonly SlugService $slugs,
        private readonly BlockRegistry $blocks,
        private readonly ThemeRegistry $themes,
        private readonly Settings $settings,
    ) {
    }

    public function create(User $owner, array $data): Profile
    {
        $count = Profile::query()->where('user_id', $owner->id)->where('status', '!=', Profile::STATUS_ARCHIVED)->count();

        if (! $this->entitlements->withinLimit($owner, Entitlements::MAX_PAGES, $count)) {
            throw new UserFacingException(__('profiles.limit_reached'));
        }

        $slug = $this->slugs->normalize($data['slug'] ?? '');

        if ($problem = $this->slugs->problemWithProfileSlug($slug)) {
            throw ValidationException::withMessages(['slug' => __($problem)]);
        }

        $theme = (string) $this->settings->get('profiles.default_theme');

        if (! $this->themes->isActive($theme) || $this->themes->isPremium($theme)) {
            $theme = ThemeRegistry::FALLBACK;
        }

        return DB::transaction(function () use ($owner, $data, $slug, $theme) {
            $profile = new Profile([
                'slug' => $slug,
                'display_name' => trim($data['display_name'] ?? $owner->name),
                'theme_key' => $theme,
                'theme_settings' => [],
                'language' => $owner->preferredLocale(),
            ]);
            $profile->user_id = $owner->id;
            $profile->status = Profile::STATUS_DRAFT;
            $profile->save();

            ProfileBlock::query()->create([
                'profile_id' => $profile->id,
                'type' => 'social_icons',
                'position' => 0,
                'is_visible' => true,
                'data' => ['size' => 'md'],
            ]);

            return $profile;
        });
    }

    /** Throws when the editor's copy is older than the stored draft (edited in another tab). */
    public function assertRevision(Profile $profile, mixed $revision): void
    {
        if ($revision !== null && $revision !== '' && (int) $revision !== $profile->draft_revision) {
            throw new EditConflictException;
        }
    }

    public function updateDetails(Profile $profile, array $data): Profile
    {
        $owner = $profile->user;

        foreach (['avatar_media_id', 'banner_media_id', 'seo_image_media_id'] as $mediaField) {
            if (array_key_exists($mediaField, $data) && $data[$mediaField]) {
                $owned = MediaFile::query()->whereKey($data[$mediaField])->where('user_id', $owner->id)->exists();

                if (! $owned) {
                    throw ValidationException::withMessages([$mediaField => __('validation.custom.media.not_owned')]);
                }
            }
        }

        if (! $this->entitlements->allows($owner, Entitlements::SEO_ADVANCED)) {
            unset($data['seo_image_media_id'], $data['seo_noindex']);
        }

        $profile->fill($data);

        if ($profile->isDirty()) {
            $profile->save();
            $profile->markDirty();
        }

        return $profile;
    }

    public function changeSlug(Profile $profile, string $newSlug, bool $keepRedirect): Profile
    {
        $newSlug = $this->slugs->normalize($newSlug);

        if ($newSlug === $profile->slug) {
            return $profile;
        }

        if ($problem = $this->slugs->problemWithProfileSlug($newSlug, $profile)) {
            throw ValidationException::withMessages(['slug' => __($problem)]);
        }

        return DB::transaction(function () use ($profile, $newSlug, $keepRedirect) {
            $old = $profile->slug;
            ProfileSlugRedirect::query()->where('old_slug', $newSlug)->where('profile_id', $profile->id)->delete();

            if ($keepRedirect && $this->entitlements->allows($profile->user, Entitlements::SLUG_REDIRECTS)) {
                $days = $this->settings->int('profiles.slug_redirect_days');
                ProfileSlugRedirect::query()->updateOrCreate(
                    ['old_slug' => $old],
                    ['profile_id' => $profile->id, 'expires_at' => $days > 0 ? now()->addDays($days) : null],
                );
            }

            $profile->slug = $newSlug;
            $profile->save();

            return $profile;
        });
    }

    public function setTheme(Profile $profile, string $themeKey, array $settingsInput): Profile
    {
        $owner = $profile->user;

        if (! $this->themes->isActive($themeKey)) {
            throw ValidationException::withMessages(['theme_key' => __('themes.unavailable')]);
        }

        if ($this->themes->isPremium($themeKey) && ! $this->entitlements->allows($owner, Entitlements::PREMIUM_THEMES)) {
            throw ValidationException::withMessages(['theme_key' => __('themes.requires_upgrade')]);
        }

        $advanced = $this->entitlements->allows($owner, Entitlements::ADVANCED_CUSTOMIZATION);
        $themeChanged = $themeKey !== $profile->theme_key;
        $current = $themeChanged ? [] : ($profile->theme_settings ?? []);

        // Switching theme starts from that theme's own design (no carried-over colours).
        $clean = $themeChanged ? [] : \App\Themes\ThemeSettings::sanitize($settingsInput, $advanced, $current);

        // Switching light/dark applies the theme's palette for that mode instead of the old colours.
        $previousMode = \App\Themes\ThemeSettings::resolve($this->themes->get($profile->theme_key), $current)['mode'];

        if (! $themeChanged && isset($clean['mode']) && $clean['mode'] !== $previousMode) {
            $clean = array_filter($clean, fn ($key) => ! str_ends_with($key, '_color') && ! in_array($key, ['button_bg', 'button_text', 'card_bg', 'bg_gradient_from', 'bg_gradient_to'], true), ARRAY_FILTER_USE_KEY);
        }

        if (isset($clean['bg_image_media_id'])
            && ! MediaFile::query()->whereKey($clean['bg_image_media_id'])->where('user_id', $owner->id)->exists()) {
            unset($clean['bg_image_media_id']);
        }

        $profile->theme_key = $themeKey;
        $profile->theme_settings = $clean;
        $profile->save();
        $profile->markDirty();

        return $profile;
    }

    public function publish(Profile $profile, User $actor): ProfileVersion
    {
        if ($profile->status === Profile::STATUS_SUSPENDED) {
            throw new UserFacingException(__('profiles.suspended_cannot_publish'));
        }

        if (! $profile->user->isActive()) {
            throw new UserFacingException(__('profiles.account_inactive'));
        }

        return DB::transaction(function () use ($profile, $actor) {
            $profile->refresh();
            $versionNo = (int) $profile->versions()->max('version_no') + 1;

            $version = ProfileVersion::query()->create([
                'profile_id' => $profile->id,
                'version_no' => $versionNo,
                'snapshot' => $this->buildSnapshot($profile),
                'created_by' => $actor->id,
                'created_at' => now(),
            ]);

            $profile->forceFill([
                'status' => Profile::STATUS_PUBLISHED,
                'published_version_id' => $version->id,
                'published_at' => now(),
                'has_unpublished_changes' => false,
            ])->save();

            $this->pruneVersions($profile);

            return $version;
        });
    }

    public function unpublish(Profile $profile): void
    {
        if ($profile->status === Profile::STATUS_PUBLISHED) {
            $profile->forceFill(['status' => Profile::STATUS_DRAFT])->save();
        }
    }

    public function archive(Profile $profile): void
    {
        if ($profile->status !== Profile::STATUS_SUSPENDED) {
            $profile->forceFill(['status' => Profile::STATUS_ARCHIVED])->save();
        }
    }

    public function restoreFromArchive(Profile $profile): void
    {
        $count = Profile::query()->where('user_id', $profile->user_id)->where('status', '!=', Profile::STATUS_ARCHIVED)->count();

        if (! $this->entitlements->withinLimit($profile->user, Entitlements::MAX_PAGES, $count)) {
            throw new UserFacingException(__('profiles.limit_reached'));
        }

        if ($profile->status === Profile::STATUS_ARCHIVED) {
            $profile->forceFill(['status' => Profile::STATUS_DRAFT])->save();
        }
    }

    /** Discard draft changes: rebuild editable rows from the published snapshot. */
    public function revertToPublished(Profile $profile): void
    {
        $snapshot = $profile->publishedVersion?->snapshot;

        if (! $snapshot) {
            throw new UserFacingException(__('profiles.nothing_published'));
        }

        DB::transaction(function () use ($profile, $snapshot) {
            $p = $snapshot['profile'];
            $profile->fill([
                'display_name' => $p['display_name'],
                'title' => $p['title'],
                'bio_short' => $p['bio_short'],
                'bio_long' => $p['bio_long'],
                'contact_email' => $p['contact_email'],
                'contact_phone' => $p['contact_phone'],
                'location' => $p['location'],
                'badge' => $p['badge'],
                'avatar_media_id' => $p['avatar_media_id'],
                'banner_media_id' => $p['banner_media_id'],
                'seo_title' => $p['seo_title'],
                'seo_description' => $p['seo_description'],
                'seo_image_media_id' => $p['seo_image_media_id'],
                'seo_noindex' => $p['seo_noindex'],
                'language' => $p['language'],
                'theme_key' => $snapshot['theme']['key'],
                'theme_settings' => $snapshot['theme']['settings'],
            ]);

            $profile->blocks()->delete();

            foreach ($snapshot['blocks'] as $i => $block) {
                $row = new ProfileBlock([
                    'profile_id' => $profile->id,
                    'type' => $block['type'],
                    'position' => $i,
                    'is_visible' => true,
                    'data' => $block['data'],
                    'starts_at' => $block['starts_at'] ? \Illuminate\Support\Carbon::parse($block['starts_at']) : null,
                    'ends_at' => $block['ends_at'] ? \Illuminate\Support\Carbon::parse($block['ends_at']) : null,
                ]);
                // Keep the original id so analytics stay attributed to the same block.
                $row->id = $block['id'];
                $row->save();
            }

            foreach ($snapshot['hidden_blocks'] ?? [] as $block) {
                $row = new ProfileBlock([
                    'profile_id' => $profile->id, 'type' => $block['type'], 'position' => $block['position'],
                    'is_visible' => false, 'data' => $block['data'],
                ]);
                $row->id = $block['id'];
                $row->save();
            }

            $profile->socialLinks()->delete();

            foreach ($snapshot['social_links'] as $i => $link) {
                ProfileSocialLink::query()->create(['profile_id' => $profile->id, 'platform' => $link['platform'], 'url' => $link['url'], 'position' => $i]);
            }

            $profile->has_unpublished_changes = false;
            $profile->draft_revision = $profile->draft_revision + 1;
            $profile->save();
        });
    }

    public function delete(Profile $profile): void
    {
        $profile->delete();
    }

    public function buildSnapshot(Profile $profile): array
    {
        $profile->loadMissing(['blocks', 'socialLinks']);
        $mediaIds = array_filter([$profile->avatar_media_id, $profile->banner_media_id, $profile->seo_image_media_id, $profile->theme_settings['bg_image_media_id'] ?? null]);

        foreach ($profile->blocks as $block) {
            $mediaIds = array_merge($mediaIds, MediaService::mediaIdsInBlockData($block->data ?? []));
        }

        $media = MediaFile::query()->whereIn('id', array_unique($mediaIds))->where('user_id', $profile->user_id)->get()
            ->mapWithKeys(fn (MediaFile $m) => [$m->id => Storage::disk($m->disk)->url($m->path)])->all();

        $blocks = [];
        $hidden = [];

        foreach ($profile->blocks as $block) {
            if (! $this->blocks->has($block->type)) {
                continue;
            }

            if (! $block->is_visible) {
                $hidden[] = ['id' => $block->id, 'type' => $block->type, 'position' => $block->position, 'data' => $block->data];

                continue;
            }

            $type = $this->blocks->get($block->type);
            $blocks[] = [
                'id' => $block->id,
                'type' => $block->type,
                'data' => $block->data,
                'view' => $type->present($block->data ?? [], $media),
                'starts_at' => $block->starts_at?->toIso8601String(),
                'ends_at' => $block->ends_at?->toIso8601String(),
            ];
        }

        return [
            'schema' => 1,
            'profile' => [
                'display_name' => $profile->display_name,
                'title' => $profile->title,
                'bio_short' => $profile->bio_short,
                'bio_long' => $profile->bio_long,
                'contact_email' => $profile->contact_email,
                'contact_phone' => $profile->contact_phone,
                'location' => $profile->location,
                'badge' => $profile->badge,
                'avatar_media_id' => $profile->avatar_media_id,
                'banner_media_id' => $profile->banner_media_id,
                'seo_image_media_id' => $profile->seo_image_media_id,
                'avatar' => $media[$profile->avatar_media_id] ?? null,
                'banner' => $media[$profile->banner_media_id] ?? null,
                'seo_image' => $media[$profile->seo_image_media_id] ?? null,
                'seo_title' => $profile->seo_title,
                'seo_description' => $profile->seo_description,
                'seo_noindex' => $profile->seo_noindex,
                'language' => $profile->language,
            ],
            'theme' => [
                'key' => $profile->theme_key,
                'settings' => $profile->theme_settings ?? [],
                'bg_image' => $media[$profile->theme_settings['bg_image_media_id'] ?? 0] ?? null,
            ],
            'social_links' => $profile->socialLinks->map(fn (ProfileSocialLink $l) => ['platform' => $l->platform, 'url' => $l->url])->values()->all(),
            'blocks' => $blocks,
            'hidden_blocks' => $hidden,
            'media_ids' => array_values(array_map('intval', array_keys($media))),
        ];
    }

    private function pruneVersions(Profile $profile): void
    {
        $keep = $profile->versions()->orderByDesc('version_no')->limit(self::KEEP_VERSIONS)->pluck('id')->push($profile->published_version_id);
        $profile->versions()->whereNotIn('id', $keep->filter()->all())->delete();
    }
}
