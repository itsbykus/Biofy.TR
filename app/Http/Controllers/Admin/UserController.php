<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FeatureDefinition;
use App\Models\Package;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Models\UserPackageAssignment;
use App\Services\AuditLogger;
use App\Services\Entitlements;
use App\Services\MediaService;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly UserAccountService $accounts,
        private readonly Entitlements $entitlements,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), ['created_at', 'name', 'email', 'last_login_at'], true) ? $request->query('sort') : 'created_at';
        $direction = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $users = User::query()
            ->with(['roles', 'packageAssignments' => fn ($q) => $q->current()->with('package')])
            ->withCount('profiles')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $request->query('q')).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('username', 'like', $term));
            })
            ->when($request->query('status') === 'suspended', fn ($q) => $q->where('status', User::STATUS_SUSPENDED))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('status', User::STATUS_ACTIVE)->whereNull('deletion_requested_at'))
            ->when($request->query('status') === 'deletion', fn ($q) => $q->whereNotNull('deletion_requested_at'))
            ->when($request->query('verified') === 'yes', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->when($request->query('verified') === 'no', fn ($q) => $q->whereNull('email_verified_at'))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('key', $request->query('role'))))
            ->when($request->filled('package'), fn ($q) => $q->whereHas('packageAssignments', fn ($a) => $a->current()->where('package_id', (int) $request->query('package'))))
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'packages' => Package::query()->orderBy('sort_order')->get(),
            'roles' => Role::query()->get(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function show(User $user, MediaService $media): View
    {
        $user->load(['roles', 'profiles' => fn ($q) => $q->withCount('blocks'), 'customDomains', 'socialAccounts', 'featureOverrides',
            'packageAssignments' => fn ($q) => $q->with(['package', 'assigner:id,name'])->latest('starts_at')]);

        return view('admin.users.show', [
            'user' => $user,
            'entitlements' => $this->entitlements->resolve($user),
            'currentPackage' => $this->entitlements->currentPackage($user),
            'packages' => Package::query()->available()->where(fn ($q) => $q->whereNull('custom_for_user_id')->orWhere('custom_for_user_id', $user->id))->orderBy('sort_order')->get(),
            'features' => FeatureDefinition::query()->orderBy('group')->orderBy('sort_order')->get(),
            'roles' => Role::query()->get(),
            'events' => SecurityEvent::query()->where('user_id', $user->id)->latest('id')->limit(25)->get(),
            'audit' => AuditLog::query()->where('subject_type', 'User')->where('subject_id', $user->id)->with('actor:id,name')->latest('id')->limit(25)->get(),
            'storageUsed' => $media->usedBytes($user),
        ]);
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        abort_if($user->id === $request->user()->id, 422, __('admin.users.cannot_self'));
        $this->accounts->suspend($user, $data['reason'], $request->user());

        return back()->with('success', __('admin.users.suspended'));
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->accounts->reactivate($user, $request->user());

        return back()->with('success', __('admin.users.reactivated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, __('admin.users.cannot_self'));
        $request->validate(['confirm' => ['required', 'in:'.$user->username]], ['confirm.in' => __('account.delete_confirm_mismatch')]);
        $this->accounts->deletePermanently($user, $request->user());

        return redirect()->route('admin.users.index')->with('success', __('admin.users.deleted'));
    }

    public function cancelDeletion(Request $request, User $user): RedirectResponse
    {
        $this->accounts->cancelDeletion($user, $request->user());

        return back()->with('success', __('account.deletion_cancelled'));
    }

    public function markVerified(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $this->audit->log('user.email_verified_by_admin', $user);
        }

        return back()->with('success', __('admin.users.verified'));
    }

    public function resendVerification(Request $request, User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('success', __('auth.verification_sent'));
    }

    public function assignPackage(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'package_id' => ['required', 'integer', 'exists:packages,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at', 'after:now'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->accounts->assignPackage(
            $user,
            Package::query()->findOrFail($data['package_id']),
            isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : null,
            isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : null,
            $data['note'] ?? null,
            $request->user(),
        );

        return back()->with('success', __('admin.users.package_assigned'));
    }

    public function revokeAssignment(Request $request, User $user, UserPackageAssignment $assignment): RedirectResponse
    {
        abort_unless($assignment->user_id === $user->id, 404);
        $this->accounts->revokeAssignment($assignment, $request->user());

        return back()->with('success', __('admin.users.package_revoked'));
    }

    public function setOverride(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'feature_key' => ['required', 'string', 'exists:feature_definitions,key'],
            'value' => ['required', 'string', 'max:20', 'regex:/^-?\d+$/'],
            'note' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $this->accounts->setOverride($user, $data['feature_key'], $data['value'], $data['note'] ?? null, isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null, $request->user());

        return back()->with('success', __('admin.users.override_saved'));
    }

    public function removeOverride(Request $request, User $user, string $featureKey): RedirectResponse
    {
        $this->accounts->removeOverride($user, $featureKey, $request->user());

        return back()->with('success', __('admin.users.override_removed'));
    }

    public function syncRoles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['roles' => ['nullable', 'array'], 'roles.*' => [Rule::exists('roles', 'key')]]);
        $this->accounts->syncRoles($user, $data['roles'] ?? [], $request->user());

        return back()->with('success', __('admin.users.roles_saved'));
    }
}
