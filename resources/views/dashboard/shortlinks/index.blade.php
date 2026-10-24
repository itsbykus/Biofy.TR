@extends('layouts.app')

@section('title', __('nav.shortlinks'))

@section('content')
    <x-page-header :title="__('nav.shortlinks')" :description="__('shortlinks.help')">
        <x-slot:actions>
            <span class="badge badge-gray">{{ __('shortlinks.usage', ['used' => $used, 'limit' => limit_label($limit)]) }}</span>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="space-y-3">
            @forelse ($links as $link)
                <article class="card">
                    <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ $link->shortUrl() }}" target="_blank" rel="noopener" class="font-mono text-sm font-semibold text-zinc-900 hover:underline">{{ $link->displayUrl() }}</a>
                                <button type="button" class="btn btn-ghost btn-sm btn-icon" data-copy="{{ $link->shortUrl() }}" data-copied="{{ __('common.copied') }}" aria-label="{{ __('common.copy') }}"><x-icon name="copy" class="size-3.5" /></button>
                                @unless ($link->is_active)<span class="badge badge-red">{{ __('shortlinks.disabled') }}</span>@endunless
                            </div>
                            @if ($link->title)<p class="mt-0.5 text-sm font-medium text-zinc-700">{{ $link->title }}</p>@endif
                            <p class="mt-0.5 truncate text-xs text-zinc-500" title="{{ $link->target_url }}">→ {{ $link->target_url }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-4">
                            <div class="text-right">
                                <p class="text-lg font-bold tabular-nums">{{ num($link->clicks) }}</p>
                                <p class="text-[11px] text-zinc-500">{{ __('shortlinks.clicks') }}</p>
                            </div>
                            <img src="{{ route('shortlinks.qr', [$link, 'svg']) }}" alt="{{ __('shortlinks.qr_alt', ['url' => $link->displayUrl()]) }}" class="size-16 rounded-lg border border-zinc-200 bg-white p-1" loading="lazy">
                        </div>
                    </div>
                    <details class="border-t border-zinc-100">
                        <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2.5 text-xs font-medium text-zinc-600 hover:bg-zinc-50">
                            <span>{{ __('shortlinks.manage') }}</span>
                            <span class="text-zinc-400">{{ __('shortlinks.created_at', ['date' => local_time($link->created_at, 'd.m.Y')]) }}@if ($link->last_clicked_at) · {{ __('shortlinks.last_click', ['date' => local_time($link->last_clicked_at)]) }}@endif</span>
                        </summary>
                        <div class="space-y-4 p-4 pt-1">
                            <form method="POST" action="{{ route('shortlinks.update', $link) }}" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end" novalidate>
                                @csrf @method('PUT')
                                <x-form.input name="target_url" :id="'t-'.$link->id" type="url" :label="__('shortlinks.target')" :value="$link->target_url" required />
                                <x-form.input name="title" :id="'n-'.$link->id" :label="__('shortlinks.title')" :value="$link->title" maxlength="120" />
                                <button class="btn btn-dark">{{ __('common.save') }}</button>
                            </form>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('shortlinks.qr', [$link, 'png']) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-3.5" />QR PNG</a>
                                <a href="{{ route('shortlinks.qr', [$link, 'svg']) }}?download=1" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-3.5" />QR SVG</a>
                                <a href="{{ route('qr.show', ['content' => $link->shortUrl()]) }}" class="btn btn-ghost btn-sm"><x-icon name="palette" class="size-3.5" />{{ __('shortlinks.custom_qr') }}</a>
                                <form method="POST" action="{{ route('shortlinks.destroy', $link) }}" class="ml-auto" data-confirm="{{ __('shortlinks.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm">{{ __('common.delete') }}</button></form>
                            </div>
                        </div>
                    </details>
                </article>
            @empty
                <x-empty icon="link-2" :title="__('shortlinks.empty')" :text="__('shortlinks.empty_text')" />
            @endforelse
            <div>{{ $links->links() }}</div>
        </div>

        <aside>
            <form method="POST" action="{{ route('shortlinks.store') }}" class="card card-pad space-y-4 lg:sticky lg:top-6" novalidate>
                @csrf
                <h2 class="card-title">{{ __('shortlinks.create') }}</h2>
                <x-form.input name="target_url" type="url" :label="__('shortlinks.target')" placeholder="https://" inputmode="url" required />
                <div>
                    <label for="f-code" class="label">{{ __('shortlinks.alias') }}</label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-zinc-300 bg-zinc-50 px-2.5 text-xs text-zinc-500">{{ preg_replace('#^https?://#', '', $base) }}</span>
                        <input id="f-code" name="code" value="{{ old('code') }}" class="input rounded-l-none" maxlength="40" autocapitalize="none" spellcheck="false" placeholder="{{ __('shortlinks.alias_placeholder') }}" @error('code') aria-invalid="true" @enderror>
                    </div>
                    <p class="{{ $errors->has('code') ? 'error' : 'help' }}">{{ $errors->first('code') ?: __('shortlinks.alias_help') }}</p>
                </div>
                <x-form.input name="title" :label="__('shortlinks.title')" maxlength="120" :help="__('shortlinks.title_help')" />
                <button class="btn btn-dark w-full" @disabled($limit !== -1 && $used >= $limit)><x-icon name="link-2" />{{ __('shortlinks.create') }}</button>
                @if ($limit !== -1 && $used >= $limit)
                    <p class="text-xs text-zinc-500">{{ __('shortlinks.limit_reached') }} <a href="{{ route('plan.show') }}" class="font-medium underline">{{ __('plans.see_plans') }}</a></p>
                @endif
            </form>
        </aside>
    </div>
@endsection
