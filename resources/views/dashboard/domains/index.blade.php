@extends('layouts.app')

@section('title', __('nav.domains'))

@section('content')
    <x-page-header :title="__('nav.domains')" :description="__('domains.help')" />

    @if (! $platformEnabled)
        <div class="alert alert-info"><x-icon name="info" class="mt-0.5" /><p>{{ __('domains.disabled_platform') }}</p></div>
    @elseif (! $allowed)
        <x-empty icon="globe" :title="__('domains.locked_title')" :text="__('domains.locked_text')">
            <a href="{{ route('plan.show') }}" class="btn btn-primary">{{ __('plans.see_plans') }}</a>
        </x-empty>
    @else
        <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
            <div class="space-y-4">
                @forelse ($domains as $domain)
                    <article class="card">
                        <header class="flex flex-col gap-3 border-b border-zinc-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-base font-semibold">{{ $domain->hostname }}</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <x-status-badge :status="$domain->status" />
                                    <span class="badge {{ $domain->isSecure() ? 'badge-green' : ($domain->ssl_status === 'failed' ? 'badge-red' : 'badge-gray') }}"><x-icon name="lock" class="size-3" />HTTPS: {{ __('domains.ssl.'.$domain->ssl_status) }}</span>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('domains.verify', $domain) }}">@csrf<button class="btn btn-dark btn-sm"><x-icon name="refresh-cw" class="size-3.5" />{{ __('domains.verify') }}</button></form>
                                <form method="POST" action="{{ route('domains.destroy', $domain) }}" data-confirm="{{ __('domains.remove_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm">{{ __('common.remove') }}</button></form>
                            </div>
                        </header>
                        <div class="space-y-4 p-4">
                            @if ($domain->status === 'disabled')
                                <p class="alert alert-error"><x-icon name="lock" class="mt-0.5" /><span>{{ __('domains.disabled_by_admin') }} {{ $domain->disabled_reason }}</span></p>
                            @endif
                            <div>
                                <p class="text-sm font-semibold">{{ __('domains.step1') }}</p>
                                <div class="mt-2 overflow-x-auto">
                                    <table class="table text-xs">
                                        <thead><tr><th>{{ __('domains.type') }}</th><th>{{ __('domains.name') }}</th><th>{{ __('domains.value') }}</th></tr></thead>
                                        <tbody>
                                            <tr>
                                                <td>TXT</td>
                                                <td><span class="code">{{ $domain->txtRecordName() }}</span></td>
                                                <td class="flex items-center gap-1"><span class="code">{{ $domain->txtRecordValue() }}</span><button type="button" class="btn btn-ghost btn-sm btn-icon" data-copy="{{ $domain->txtRecordValue() }}" data-copied="{{ __('common.copied') }}" aria-label="{{ __('common.copy') }}"><x-icon name="copy" class="size-3.5" /></button></td>
                                            </tr>
                                            @if ($cnameTarget)
                                                <tr><td>CNAME</td><td><span class="code">{{ $domain->hostname }}</span></td><td><span class="code">{{ $cnameTarget }}</span></td></tr>
                                            @endif
                                            @if ($aRecordIp)
                                                <tr><td>A</td><td><span class="code">{{ $domain->hostname }}</span></td><td><span class="code">{{ $aRecordIp }}</span></td></tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                @if ($cnameTarget && $aRecordIp)<p class="help">{{ __('domains.cname_or_a') }}</p>@endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold">{{ __('domains.step2') }}</p>
                                <p class="mt-1 text-sm text-zinc-600">{{ __('domains.ssl_help') }}</p>
                            </div>
                            <form method="POST" action="{{ route('domains.update', $domain) }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                                @csrf @method('PUT')
                                <x-form.select name="profile_id" :label="__('domains.connected_page')" :value="$domain->profile_id" :placeholder="__('common.none')" :options="$profiles->mapWithKeys(fn ($p) => [$p->id => $p->display_name.' (/'.$p->slug.')'])->all()" class="sm:w-72" :id="'domain-profile-'.$domain->id" />
                                <button class="btn btn-secondary">{{ __('common.save') }}</button>
                            </form>
                            @if ($domain->last_checked_at)
                                <details class="text-xs text-zinc-500">
                                    <summary class="cursor-pointer">{{ __('domains.last_checked', ['time' => local_time($domain->last_checked_at)]) }} @if ($domain->last_error) · {{ __('domains.errors.'.$domain->last_error) }}@endif</summary>
                                    <ul class="mt-2 space-y-1">
                                        @foreach ($domain->verifications as $check)
                                            <li class="flex items-center gap-2"><x-icon :name="$check->passed ? 'circle-check' : 'circle-x'" class="size-3.5 {{ $check->passed ? 'text-emerald-600' : 'text-red-600' }}" />{{ __('domains.checks.'.$check->check) }} — {{ $check->details }} · {{ local_time($check->created_at) }}</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-empty icon="globe" :title="__('domains.empty_title')" :text="__('domains.empty_text')" />
                @endforelse
            </div>

            <aside class="space-y-4">
                <form method="POST" action="{{ route('domains.store') }}" class="card card-pad space-y-3" novalidate>
                    @csrf
                    <h2 class="card-title">{{ __('domains.add') }}</h2>
                    <x-form.input name="hostname" :label="__('domains.hostname')" placeholder="links.ornekalanadi.com" required :help="__('domains.hostname_help')" />
                    <x-form.select name="profile_id" :label="__('domains.connected_page')" :placeholder="__('common.none')" :options="$profiles->mapWithKeys(fn ($p) => [$p->id => $p->display_name.' (/'.$p->slug.')'])->all()" />
                    <button class="btn btn-dark w-full">{{ __('domains.add') }}</button>
                    <p class="text-xs text-zinc-500">{{ __('domains.usage', ['used' => $domains->count(), 'limit' => limit_label($limit)]) }}</p>
                </form>
                <div class="card card-pad text-sm text-zinc-600">
                    <h3 class="card-title mb-2">{{ __('domains.how_title') }}</h3>
                    <ol class="list-decimal space-y-1.5 pl-5">
                        <li>{{ __('domains.how_1') }}</li>
                        <li>{{ __('domains.how_2') }}</li>
                        <li>{{ __('domains.how_3') }}</li>
                        <li>{{ __('domains.how_4') }}</li>
                    </ol>
                    @if ($instructions)<p class="mt-3 border-t border-zinc-100 pt-3 whitespace-pre-line">{{ $instructions }}</p>@endif
                    @if ($requireHttps)<p class="mt-3 text-xs">{{ __('domains.https_required') }}</p>@endif
                </div>
            </aside>
        </div>
    @endif
@endsection
