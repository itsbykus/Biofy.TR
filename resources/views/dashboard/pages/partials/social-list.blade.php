<div class="space-y-4">
    @if ($profile->socialLinks->isNotEmpty())
        <ol class="space-y-2" data-sortable="{{ route('social.reorder', $profile) }}" aria-label="{{ __('editor.tabs.social') }}">
            @foreach ($profile->socialLinks as $link)
                <li data-id="{{ $link->id }}" class="card flex items-center gap-2 p-2 sm:gap-3">
                    <span class="cursor-grab rounded p-1.5 text-zinc-400 hover:bg-zinc-100" draggable="true" aria-hidden="true"><x-icon name="grip-vertical" /></span>
                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-zinc-100 text-zinc-800"><x-brand-icon :platform="$link->platform" class="size-4" /></span>
                    <form method="POST" action="{{ route('social.update', $link) }}" data-editor-form class="flex min-w-0 flex-1 items-center gap-2">
                        @csrf @method('PUT')
                        <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                        <label class="sr-only" for="social-{{ $link->id }}">{{ __('social.platforms.'.$link->platform) }}</label>
                        <input id="social-{{ $link->id }}" name="value" value="{{ $link->url }}" class="input min-w-0 flex-1">
                        <button class="btn btn-ghost btn-sm">{{ __('common.save') }}</button>
                    </form>
                    <button type="button" class="btn btn-ghost btn-sm btn-icon" data-move="up" aria-label="{{ __('editor.move_up') }}"><x-icon name="arrow-up" class="size-3.5" /></button>
                    <button type="button" class="btn btn-ghost btn-sm btn-icon" data-move="down" aria-label="{{ __('editor.move_down') }}"><x-icon name="arrow-down" class="size-3.5" /></button>
                    <form method="POST" action="{{ route('social.destroy', $link) }}" data-editor-form data-confirm="{{ __('social.delete_confirm') }}">@csrf @method('DELETE')
                        <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                        <button class="btn btn-danger-ghost btn-sm btn-icon" aria-label="{{ __('common.delete') }}"><x-icon name="trash-2" class="size-3.5" /></button>
                    </form>
                </li>
            @endforeach
        </ol>
    @endif

    <form method="POST" action="{{ route('social.store', $profile) }}" data-editor-form class="card card-pad space-y-3" novalidate>
        @csrf
        <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
        <div class="flex items-center justify-between">
            <h3 class="card-title">{{ __('social.add') }}</h3>
            <span class="text-xs text-zinc-500">{{ $profile->socialLinks->count() }} / {{ limit_label($limits['social']) }}</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-[180px_1fr_auto] sm:items-start">
            <x-form.select name="platform" :options="collect($platforms)->mapWithKeys(fn ($p) => [$p => __('social.platforms.'.$p)])->all()" :aria-label="__('social.platform')" />
            <x-form.input name="value" :placeholder="__('social.value_placeholder')" :aria-label="__('social.value')" />
            <button class="btn btn-dark"><x-icon name="plus" />{{ __('common.add') }}</button>
        </div>
        <p class="help">{{ __('social.value_help') }}</p>
    </form>

    @unless ($profile->blocks->contains('type', 'social_icons'))
        <p class="alert alert-info"><x-icon name="info" class="mt-0.5" /><span>{{ __('social.no_block_notice') }}</span></p>
    @endunless
</div>
