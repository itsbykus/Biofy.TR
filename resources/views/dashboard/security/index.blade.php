@extends('layouts.app')

@section('title', __('nav.security'))

@section('content')
    <x-page-header :title="__('nav.security')" :description="__('security.help')" />

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Two-factor authentication --}}
        <section id="two-factor" class="card card-pad space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="card-title">{{ __('security.two_factor') }}</h2>
                <span class="badge {{ $user->hasTwoFactorEnabled() ? 'badge-green' : 'badge-gray' }}">{{ $user->hasTwoFactorEnabled() ? __('common.enabled') : __('common.disabled') }}</span>
            </div>
            <p class="muted">{{ __('security.two_factor_help') }}</p>

            @if ($recoveryCodes)
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">{{ __('security.recovery_title') }}</p>
                    <p class="mt-1 text-xs text-amber-900">{{ __('security.recovery_help') }}</p>
                    <ul class="mt-3 grid grid-cols-2 gap-1 font-mono text-sm">
                        @foreach ($recoveryCodes as $code)<li>{{ $code }}</li>@endforeach
                    </ul>
                    <button type="button" class="btn btn-secondary btn-sm mt-3" data-copy="{{ implode("\n", $recoveryCodes) }}" data-copied="{{ __('common.copied') }}"><x-icon name="copy" class="size-3.5" />{{ __('common.copy') }}</button>
                </div>
            @endif

            @if ($user->hasTwoFactorEnabled())
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('security.2fa.recovery') }}">@csrf<button class="btn btn-secondary">{{ __('security.regenerate_codes') }}</button></form>
                    <form method="POST" action="{{ route('security.2fa.disable') }}" data-confirm="{{ __('security.disable_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost">{{ __('security.disable_2fa') }}</button></form>
                </div>
                <p class="text-xs text-zinc-500">{{ __('security.remaining_codes', ['count' => count($user->two_factor_recovery_codes ?? [])]) }}</p>
            @elseif ($pendingSecret)
                <div class="space-y-3">
                    <p class="text-sm">{{ __('security.scan_qr') }}</p>
                    <div class="w-fit rounded-lg border border-zinc-200 bg-white p-2">{!! $pendingQr !!}</div>
                    <details class="text-xs text-zinc-500"><summary class="cursor-pointer">{{ __('security.manual_key') }}</summary><p class="code mt-2 inline-block">{{ trim(chunk_split($pendingSecret, 4, ' ')) }}</p></details>
                    <form method="POST" action="{{ route('security.2fa.confirm') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end" novalidate>
                        @csrf
                        <x-form.input name="code" :label="__('auth.authentication_code')" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required class="sm:w-48" />
                        <button class="btn btn-dark">{{ __('security.confirm_enable') }}</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('security.2fa.start') }}">@csrf<button class="btn btn-dark"><x-icon name="shield-check" />{{ __('security.enable_2fa') }}</button></form>
            @endif
        </section>

        {{-- Google --}}
        <section class="card card-pad space-y-3">
            <h2 class="card-title">{{ __('security.google') }}</h2>
            @if ($googleLinked)
                <p class="text-sm text-zinc-600">{{ __('security.google_linked', ['email' => $googleLinked->provider_email]) }}</p>
                @if ($user->hasPassword())
                    <form method="POST" action="{{ route('security.google.unlink') }}" data-confirm="{{ __('security.google_unlink_confirm') }}">@csrf @method('DELETE')<button class="btn btn-secondary">{{ __('security.google_unlink') }}</button></form>
                @else
                    <p class="help">{{ __('account.set_password_before_unlink') }}</p>
                @endif
            @elseif ($googleEnabled)
                <p class="text-sm text-zinc-600">{{ __('security.google_help') }}</p>
                @include('auth.partials.google-button', ['label' => __('security.google_link'), 'href' => route('auth.google.redirect', ['intent' => 'link'])])
            @else
                <p class="text-sm text-zinc-500">{{ __('security.google_unavailable') }}</p>
            @endif
        </section>

        {{-- Sessions --}}
        <section class="card card-pad space-y-3 lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="card-title">{{ __('security.sessions') }}</h2>
                @if ($sessions->count() > 1)
                    <form method="POST" action="{{ route('security.sessions.others') }}" data-confirm="{{ __('security.logout_others_confirm') }}">@csrf<button class="btn btn-secondary btn-sm">{{ __('security.logout_others') }}</button></form>
                @endif
            </div>
            <ul class="divide-y divide-zinc-100">
                @foreach ($sessions as $session)
                    <li class="flex items-center gap-3 py-3 text-sm">
                        <x-icon :name="$session['device'] === 'mobile' ? 'smartphone' : 'monitor'" class="size-5 text-zinc-500" />
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ $session['agent'] }} @if ($session['current'])<span class="badge badge-green">{{ __('security.this_device') }}</span>@endif</p>
                            <p class="text-xs text-zinc-500">{{ $session['ip'] }} · {{ __('security.last_active', ['time' => $session['last_active']->diffForHumans()]) }}</p>
                        </div>
                        @unless ($session['current'])
                            <form method="POST" action="{{ route('security.sessions.destroy', $session['id_hash']) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm">{{ __('auth.sign_out') }}</button></form>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Security log --}}
        <section class="card card-pad lg:col-span-2">
            <h2 class="card-title mb-3">{{ __('security.events_title') }}</h2>
            @if ($events->isEmpty())
                <p class="muted">{{ __('security.no_events') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>{{ __('security.event') }}</th><th>IP</th><th class="hidden md:table-cell">{{ __('security.device') }}</th><th>{{ __('common.date') }}</th></tr></thead>
                        <tbody>
                            @foreach ($events as $event)
                                <tr>
                                    <td @class(['font-medium', 'text-red-700' => in_array($event->type, ['login_failed', 'two_factor_failed', 'lockout'], true)])>{{ __('security.events.'.$event->type) }}</td>
                                    <td class="font-mono text-xs">{{ $event->ip_address }}</td>
                                    <td class="hidden text-xs text-zinc-500 md:table-cell">{{ \App\Support\UserAgent::describe($event->user_agent) }}</td>
                                    <td class="text-xs whitespace-nowrap text-zinc-500">{{ local_time($event->created_at) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
