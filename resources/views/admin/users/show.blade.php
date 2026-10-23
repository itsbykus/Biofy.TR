@extends('layouts.admin')

@section('title', $user->name)

@section('content')
    <a href="{{ route('admin.users.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">← {{ __('admin.nav.users') }}</a>
    <x-page-header class="mt-1" :title="$user->name" :description="'@'.$user->username.' · '.$user->email">
        <x-slot:actions>
            <x-status-badge :status="$user->status" />
            @if ($user->deletion_requested_at)<span class="badge badge-red">{{ __('admin.users.deletion_requested') }}</span>@endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <div class="space-y-6">
            {{-- Overview --}}
            <section class="card card-pad">
                <dl class="grid gap-4 text-sm sm:grid-cols-3">
                    <div><dt class="text-zinc-500">{{ __('admin.users.registered') }}</dt><dd class="font-medium">{{ local_time($user->created_at) }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('admin.users.last_login') }}</dt><dd class="font-medium">{{ $user->last_login_at ? local_time($user->last_login_at) : '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('admin.users.email_verified') }}</dt><dd class="font-medium">{{ $user->email_verified_at ? local_time($user->email_verified_at) : __('common.no') }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('security.two_factor') }}</dt><dd class="font-medium">{{ $user->hasTwoFactorEnabled() ? __('common.enabled') : __('common.disabled') }}</dd></div>
                    <div><dt class="text-zinc-500">Google</dt><dd class="font-medium">{{ $user->socialAccounts->firstWhere('provider', 'google')?->provider_email ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('media.storage') }}</dt><dd class="font-medium">{{ human_bytes($storageUsed) }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('admin.users.current_package') }}</dt><dd class="font-medium">{{ $currentPackage?->translate('name') ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('account.language') }}</dt><dd class="font-medium uppercase">{{ $user->locale }}</dd></div>
                    @if ($user->suspension_reason)
                        <div class="sm:col-span-3"><dt class="text-zinc-500">{{ __('admin.users.suspension_reason') }}</dt><dd class="font-medium text-red-700">{{ $user->suspension_reason }}</dd></div>
                    @endif
                </dl>
            </section>

            {{-- Pages --}}
            <section class="card">
                <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('nav.pages') }}</h2>
                <ul class="divide-y divide-zinc-100">
                    @forelse ($user->profiles as $profile)
                        <li class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center">
                            <div class="min-w-0 flex-1">
                                <a href="{{ url('/'.$profile->slug) }}" target="_blank" rel="noopener" class="font-medium hover:underline">/{{ $profile->slug }}</a>
                                <span class="text-xs text-zinc-500">· {{ $profile->display_name }} · {{ trans_choice('admin.users.blocks_count', $profile->blocks_count, ['count' => $profile->blocks_count]) }}</span>
                                <div class="mt-1 flex gap-1"><x-status-badge :status="$profile->status" />@if ($profile->verified_at)<span class="badge badge-blue">{{ __('profile.verified') }}</span>@endif</div>
                            </div>
                            @include('admin.profiles.partials.status-form', ['profile' => $profile])
                        </li>
                    @empty
                        <li class="muted px-5 py-4">{{ __('admin.users.no_pages') }}</li>
                    @endforelse
                </ul>
            </section>

            {{-- Effective entitlements --}}
            <section class="card overflow-x-auto">
                <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('admin.users.entitlements') }}</h2>
                <table class="table">
                    <thead><tr><th>{{ __('plans.feature') }}</th><th>{{ __('admin.users.effective') }}</th><th>{{ __('admin.users.source') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($entitlements as $key => $entry)
                            <tr>
                                <td><p class="font-medium">{{ $entry['definition']->translate('name') }}</p><p class="font-mono text-[11px] text-zinc-500">{{ $key }}</p></td>
                                <td>{{ is_bool($entry['value']) ? ($entry['value'] ? __('common.yes') : __('common.no')) : limit_label($entry['value']) }}</td>
                                <td><span class="badge {{ $entry['source'] === 'override' ? 'badge-blue' : ($entry['source'] === 'platform' || $entry['source'] === 'account' ? 'badge-red' : 'badge-gray') }}">{{ __('admin.users.sources.'.$entry['source']) }}</span></td>
                                <td class="text-right">
                                    @if ($user->featureOverrides->firstWhere('feature_key', $key))
                                        <form method="POST" action="{{ route('admin.users.overrides.destroy', [$user, $key]) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm">{{ __('admin.users.remove_override') }}</button></form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            {{-- Package history --}}
            <section class="card">
                <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('plans.history') }}</h2>
                <ul class="divide-y divide-zinc-100 text-sm">
                    @forelse ($user->packageAssignments as $assignment)
                        <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium">{{ $assignment->package?->translate('name') }} @if ($assignment->isCurrent())<span class="badge badge-green">{{ __('admin.users.current') }}</span>@endif @if ($assignment->revoked_at)<span class="badge badge-red">{{ __('plans.revoked') }}</span>@endif</p>
                                <p class="text-xs text-zinc-500">{{ local_time($assignment->starts_at) }} → {{ $assignment->ends_at ? local_time($assignment->ends_at) : __('plans.no_end') }} · {{ __('plans.source.'.$assignment->source) }} @if ($assignment->assigner) · {{ $assignment->assigner->name }}@endif</p>
                                @if ($assignment->note)<p class="text-xs text-zinc-600">{{ $assignment->note }}</p>@endif
                            </div>
                            @if ($assignment->isCurrent())
                                <form method="POST" action="{{ route('admin.users.package.revoke', [$user, $assignment]) }}" data-confirm="{{ __('admin.users.revoke_confirm') }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('admin.users.revoke') }}</button></form>
                            @endif
                        </li>
                    @empty
                        <li class="muted px-5 py-4">{{ __('admin.users.no_assignments') }}</li>
                    @endforelse
                </ul>
            </section>

            <section class="card">
                <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('security.events_title') }}</h2>
                <ul class="divide-y divide-zinc-100 text-sm">
                    @forelse ($events as $event)
                        <li class="flex justify-between gap-3 px-5 py-2"><span>{{ __('security.events.'.$event->type) }}</span><span class="text-xs text-zinc-500">{{ $event->ip_address }} · {{ local_time($event->created_at) }}</span></li>
                    @empty
                        <li class="muted px-5 py-4">{{ __('security.no_events') }}</li>
                    @endforelse
                </ul>
            </section>

            <section class="card">
                <h2 class="card-title border-b border-zinc-100 px-5 py-4">{{ __('admin.nav.audit') }}</h2>
                <ul class="divide-y divide-zinc-100 text-sm">
                    @forelse ($audit as $log)
                        <li class="flex justify-between gap-3 px-5 py-2"><span>{{ audit_label($log->action) }} · <span class="text-zinc-500">{{ $log->actor?->name ?? __('admin.system_actor') }}</span></span><span class="text-xs text-zinc-500">{{ local_time($log->created_at) }}</span></li>
                    @empty
                        <li class="muted px-5 py-4">{{ __('admin.dashboard.no_audit') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>

        {{-- Actions --}}
        <aside class="space-y-4">
            @can('users.manage')
                <form method="POST" action="{{ route('admin.users.package', $user) }}" class="card card-pad space-y-3">
                    @csrf
                    <h3 class="card-title">{{ __('admin.users.assign_package') }}</h3>
                    <x-form.select name="package_id" :label="__('admin.users.package')" :options="$packages->mapWithKeys(fn ($p) => [$p->id => $p->translate('name').($p->custom_for_user_id ? ' ★' : '')])->all()" required />
                    <div class="grid grid-cols-2 gap-2">
                        <x-form.input name="starts_at" type="datetime-local" :label="__('plans.starts')" />
                        <x-form.input name="ends_at" type="datetime-local" :label="__('plans.ends')" />
                    </div>
                    <x-form.input name="note" :label="__('admin.users.note')" maxlength="500" />
                    <button class="btn btn-dark w-full">{{ __('admin.users.assign') }}</button>
                    @can('packages.manage')<a href="{{ route('admin.packages.create', ['user' => $user->id]) }}" class="block text-center text-xs font-medium text-zinc-600 underline">{{ __('admin.users.create_custom_package') }}</a>@endcan
                </form>

                <form method="POST" action="{{ route('admin.users.overrides.store', $user) }}" class="card card-pad space-y-3">
                    @csrf
                    <h3 class="card-title">{{ __('admin.users.override') }}</h3>
                    <p class="text-xs text-zinc-500">{{ __('admin.users.override_help') }}</p>
                    <x-form.select name="feature_key" :label="__('plans.feature')" :options="$features->mapWithKeys(fn ($f) => [$f->key => $f->translate('name').' ('.$f->key.')'])->all()" required />
                    <x-form.input name="value" :label="__('admin.users.value')" :help="__('admin.users.value_help')" required />
                    <x-form.input name="expires_at" type="datetime-local" :label="__('admin.users.expires')" />
                    <x-form.input name="note" :label="__('admin.users.note')" />
                    <button class="btn btn-dark w-full">{{ __('common.save') }}</button>
                </form>

                <div class="card card-pad space-y-3">
                    <h3 class="card-title">{{ __('admin.users.account_actions') }}</h3>
                    @unless ($user->email_verified_at)
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('admin.users.verify-email', $user) }}">@csrf<button class="btn btn-secondary btn-sm">{{ __('admin.users.mark_verified') }}</button></form>
                            <form method="POST" action="{{ route('admin.users.resend-verification', $user) }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('auth.resend_verification') }}</button></form>
                        </div>
                    @endunless
                    @if ($user->isSuspended())
                        <form method="POST" action="{{ route('admin.users.reactivate', $user) }}">@csrf<button class="btn btn-dark w-full">{{ __('admin.users.reactivate') }}</button></form>
                    @elseif ($user->id !== auth()->id() && ! $user->isSuperAdmin())
                        <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="space-y-2" data-confirm="{{ __('admin.users.suspend_confirm') }}">
                            @csrf
                            <x-form.textarea name="reason" :label="__('admin.users.suspension_reason')" rows="2" required />
                            <button class="btn btn-danger w-full">{{ __('admin.users.suspend') }}</button>
                        </form>
                    @endif
                    @if ($user->deletion_requested_at)
                        <form method="POST" action="{{ route('admin.users.cancel-deletion', $user) }}">@csrf<button class="btn btn-secondary w-full">{{ __('account.cancel_deletion') }}</button></form>
                    @endif
                </div>
            @endcan

            @can('roles.assign')
                @if (auth()->user()->isSuperAdmin())
                    <form method="POST" action="{{ route('admin.users.roles', $user) }}" class="card card-pad space-y-3" data-confirm="{{ __('admin.users.roles_confirm') }}">
                        @csrf
                        <h3 class="card-title">{{ __('admin.users.roles') }}</h3>
                        @foreach ($roles as $role)
                            <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="checkbox mt-0.5" name="roles[]" value="{{ $role->key }}" @checked($user->hasRole($role->key))><span><span class="font-medium">{{ $role->name }}</span><br><span class="text-xs text-zinc-500">{{ $role->description }}</span></span></label>
                        @endforeach
                        <button class="btn btn-dark w-full">{{ __('common.save') }}</button>
                    </form>
                @endif
            @endcan

            @can('users.delete')
                @if ($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="card card-pad space-y-3 border-red-200" data-confirm="{{ __('admin.users.delete_confirm') }}">
                        @csrf @method('DELETE')
                        <h3 class="card-title text-red-800">{{ __('admin.users.delete') }}</h3>
                        <p class="text-xs text-zinc-600">{{ __('admin.users.delete_help') }}</p>
                        <x-form.input name="confirm" :label="__('account.type_username', ['username' => $user->username])" autocomplete="off" />
                        <button class="btn btn-danger w-full">{{ __('admin.users.delete') }}</button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
@endsection
