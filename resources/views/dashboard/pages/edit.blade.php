@extends('layouts.app')

@php
    $extraAssets = ['resources/js/editor.js'];
    $hideErrorSummary = true;
    $tabs = [
        'content' => ['layers', __('editor.tabs.content')],
        'profile' => ['user', __('editor.tabs.profile')],
        'social' => ['share-2', __('editor.tabs.social')],
        'theme' => ['palette', __('editor.tabs.theme')],
        'seo' => ['search', __('editor.tabs.seo')],
        'settings' => ['settings', __('editor.tabs.settings')],
    ];
    $i18n = [
        'saving' => __('editor.status.saving'), 'saved' => __('editor.status.saved'), 'error' => __('editor.status.error'),
        'unsaved' => __('editor.status.unsaved'), 'offline' => __('editor.status.offline'), 'conflict' => __('editor.status.conflict'),
        'uploading' => __('media.uploading'), 'uploaded' => __('media.uploaded'), 'loading' => __('common.loading'),
        'status_draft' => __('common.status.draft'), 'status_published' => __('common.status.published'),
        'status_archived' => __('common.status.archived'), 'status_suspended' => __('common.status.suspended'),
    ];
@endphp

@section('title', __('editor.title', ['name' => $profile->display_name]))

@section('content')
<div data-editor-root data-profile-id="{{ $profile->id }}" data-revision="{{ $profile->draft_revision }}" data-i18n='@json($i18n)'>
    {{-- Header --}}
    <div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <a href="{{ route('pages.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">← {{ __('nav.pages') }}</a>
            <div class="mt-1 flex flex-wrap items-center gap-2">
                <h1 class="truncate text-2xl font-bold tracking-tight">{{ $profile->display_name }}</h1>
                <span class="badge {{ $profile->status === 'published' ? 'badge-green' : ($profile->status === 'suspended' ? 'badge-red' : 'badge-gray') }}" data-publish-state data-status="{{ $profile->status }}">{{ __('common.status.'.$profile->status) }}</span>
                <span class="badge badge-yellow" data-unpublished @if (! $profile->has_unpublished_changes || ! $profile->published_version_id) hidden @endif>{{ __('editor.unpublished_changes') }}</span>
                <span class="text-xs text-zinc-500" data-save-status aria-live="polite"></span>
            </div>
            <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500">
                <a href="{{ $profile->publicUrl() }}" target="_blank" rel="noopener" class="truncate hover:text-zinc-900">{{ preg_replace('#^https?://#', '', $profile->publicUrl()) }}</a>
                <button type="button" class="btn btn-ghost btn-sm btn-icon" data-copy="{{ $profile->canonicalUrl() }}" data-copied="{{ __('common.copied') }}" aria-label="{{ __('common.copy_link') }}"><x-icon name="copy" class="size-3.5" /></button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('pages.preview', $profile) }}" target="_blank" class="btn btn-secondary"><x-icon name="eye" />{{ __('editor.preview') }}</a>
            @if ($profile->published_version_id && $profile->has_unpublished_changes)
                <form method="POST" action="{{ route('pages.revert', $profile) }}" data-confirm="{{ __('editor.revert_confirm') }}">@csrf
                    <button class="btn btn-ghost"><x-icon name="undo-2" />{{ __('editor.revert') }}</button>
                </form>
            @endif
            @if ($profile->status === 'published')
                <form method="POST" action="{{ route('pages.unpublish', $profile) }}" data-editor-form data-confirm="{{ __('editor.unpublish_confirm') }}">@csrf
                    <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                    <button class="btn btn-ghost">{{ __('editor.unpublish') }}</button>
                </form>
            @endif
            @if ($profile->status !== 'suspended' && $profile->status !== 'archived')
                <form method="POST" action="{{ route('pages.publish', $profile) }}" data-editor-form>@csrf
                    <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                    <button class="btn btn-primary"><x-icon name="send" />{{ __('editor.publish') }}</button>
                </form>
            @endif
        </div>
    </div>

    @if ($profile->status === 'suspended')
        <div class="alert alert-error mb-4"><x-icon name="lock" class="mt-0.5" /><p>{{ __('editor.suspended_notice') }} @if ($profile->suspension_reason)<br><span class="text-xs">{{ $profile->suspension_reason }}</span>@endif</p></div>
    @endif

    <div id="editor-conflict" class="alert alert-warning mb-4" hidden role="alert">
        <x-icon name="triangle-alert" class="mt-0.5" />
        <p class="flex-1">{{ __('editor.conflict') }}</p>
        <a href="{{ route('pages.edit', ['profile' => $profile, 'tab' => $tab]) }}" class="btn btn-dark btn-sm">{{ __('editor.reload') }}</a>
    </div>
    <div id="editor-multitab" class="alert alert-info mb-4" hidden role="status"><x-icon name="info" class="mt-0.5" /><p>{{ __('editor.multitab') }}</p></div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px] xl:grid-cols-[minmax(0,1fr)_400px]">
        <div class="min-w-0">
            <nav class="mb-5 flex overflow-x-auto border-b border-zinc-200" aria-label="{{ __('editor.sections') }}">
                @foreach ($tabs as $key => [$icon, $label])
                    <a href="{{ route('pages.edit', ['profile' => $profile, 'tab' => $key]) }}" class="tab" @if ($tab === $key) aria-current="page" @endif><x-icon :name="$icon" />{{ $label }}</a>
                @endforeach
            </nav>

            @include('dashboard.pages.tabs.'.$tab)
        </div>

        {{-- Live preview of the draft --}}
        <aside class="hidden lg:block" aria-label="{{ __('editor.live_preview') }}">
            <div class="sticky top-6">
                <div class="mb-2 flex items-center justify-between">
                    <p class="text-sm font-semibold">{{ __('editor.live_preview') }}</p>
                    <div class="flex items-center gap-1">
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-device="mobile" aria-pressed="true" aria-label="{{ __('editor.mobile') }}"><x-icon name="smartphone" /></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-device="desktop" aria-pressed="false" aria-label="{{ __('editor.desktop') }}"><x-icon name="monitor" /></button>
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-refresh-preview aria-label="{{ __('editor.refresh') }}"><x-icon name="refresh-cw" /></button>
                    </div>
                </div>
                <div class="preview-shell" data-device="mobile">
                    <iframe id="preview-frame" title="{{ __('editor.live_preview') }}" src="{{ route('pages.preview', $profile) }}" data-src="{{ route('pages.preview', $profile) }}" loading="lazy"></iframe>
                </div>
                <p class="mt-2 text-center text-xs text-zinc-500">{{ __('editor.preview_hint') }}</p>
            </div>
        </aside>
    </div>

    {{-- Block type picker --}}
    <dialog id="block-picker" class="modal" aria-labelledby="block-picker-title">
        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
            <h2 id="block-picker-title" class="text-base font-semibold">{{ __('editor.add_block') }}</h2>
            <button type="button" class="btn btn-ghost btn-icon" data-dialog-close aria-label="{{ __('common.close') }}"><x-icon name="x" /></button>
        </div>
        <div class="max-h-[70vh] space-y-5 overflow-y-auto p-5">
            @foreach ($registry->grouped() as $group => $types)
                <section>
                    <h3 class="mb-2 text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ __('blocks.groups.'.$group) }}</h3>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($types as $key => $type)
                            @php($locked = $type->isAdvanced() && ! $can['advanced_blocks'])
                            <button type="button" @class(['flex items-start gap-3 rounded-xl border p-3 text-left transition', 'border-zinc-200 hover:border-ink hover:bg-zinc-50' => ! $locked, 'cursor-not-allowed border-zinc-100 bg-zinc-50 opacity-60' => $locked])
                                @if ($locked) disabled aria-disabled="true" @else data-load-block-form="{{ route('blocks.create', ['profile' => $profile, 'type' => $key]) }}" @endif>
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-brand-soft text-brand-ink"><x-icon :name="$type->icon()" /></span>
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2 text-sm font-semibold">{{ $type->name() }} @if ($type->isAdvanced())<span class="badge badge-yellow">Pro</span>@endif</span>
                                    <span class="block text-xs text-zinc-500">{{ $type->description() }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endforeach
            @unless ($can['advanced_blocks'])
                <p class="rounded-lg bg-brand-soft px-4 py-3 text-sm text-brand-ink">{{ __('editor.advanced_locked') }} <a href="{{ route('plan.show') }}" class="font-semibold underline">{{ __('plans.see_plans') }}</a></p>
            @endunless
        </div>
    </dialog>

    <dialog id="block-dialog" class="modal" aria-label="{{ __('editor.block_editor') }}">
        <div data-dialog-body></div>
    </dialog>
</div>
@endsection

@push('head')
    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        .preview-shell { margin: 0 auto; border-radius: 28px; border: 10px solid #111; overflow: hidden; background: #fff; transition: width .2s ease; height: min(720px, calc(100vh - 140px)); }
        .preview-shell[data-device="mobile"] { width: 100%; max-width: 360px; }
        .preview-shell[data-device="desktop"] { width: 100%; max-width: none; border-width: 6px; border-radius: 14px; }
        .preview-shell iframe { width: 100%; height: 100%; border: 0; display: block; }
        [data-save-status][data-state="error"] { color: #b91c1c; }
        [data-save-status][data-state="saved"] { color: #047857; }
    </style>
@endpush
