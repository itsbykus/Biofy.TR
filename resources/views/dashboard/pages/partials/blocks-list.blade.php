@if ($profile->blocks->isEmpty())
    <x-empty icon="layers" :title="__('editor.empty_title')" :text="__('editor.empty_text')">
        <button type="button" class="btn btn-primary" data-dialog-open="#block-picker"><x-icon name="plus" />{{ __('editor.add_first_block') }}</button>
    </x-empty>
@else
    <p class="mb-2 text-xs text-zinc-500">{{ __('editor.reorder_hint') }}</p>
    <ol class="space-y-2" data-sortable="{{ route('blocks.reorder', $profile) }}" aria-label="{{ __('editor.blocks') }}">
        @foreach ($profile->blocks as $block)
            @php
                $type = $registry->has($block->type) ? $registry->get($block->type) : null;
                $locked = $type?->isAdvanced() && ! $can['advanced_blocks'];
                $scheduled = $block->starts_at || $block->ends_at;
            @endphp
            <li data-id="{{ $block->id }}" @class(['card flex items-center gap-2 p-2 pr-3 sm:gap-3', 'opacity-60' => ! $block->is_visible])>
                <span class="cursor-grab rounded p-1.5 text-zinc-400 hover:bg-zinc-100 active:cursor-grabbing" draggable="true" title="{{ __('editor.drag') }}" aria-hidden="true"><x-icon name="grip-vertical" /></span>
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-zinc-100 text-zinc-700"><x-icon :name="$type?->icon() ?? 'square'" /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-zinc-900">{{ $type?->summary($block->data ?? []) ?: $type?->name() }}</p>
                    <p class="flex flex-wrap items-center gap-1.5 text-xs text-zinc-500">
                        {{ $type?->name() ?? $block->type }}
                        @unless ($block->is_visible)<span class="badge badge-gray">{{ __('editor.hidden') }}</span>@endunless
                        @if ($scheduled)<span class="badge badge-blue"><x-icon name="clock" class="size-3" />{{ $block->starts_at ? local_time($block->starts_at, 'd.m H:i') : '…' }} → {{ $block->ends_at ? local_time($block->ends_at, 'd.m H:i') : '…' }}</span>@endif
                        @if ($locked)<span class="badge badge-yellow">{{ __('editor.locked_on_plan') }}</span>@endif
                    </p>
                </div>
                <div class="flex shrink-0 items-center">
                    <button type="button" class="btn btn-ghost btn-sm btn-icon" data-move="up" aria-label="{{ __('editor.move_up') }}"><x-icon name="arrow-up" class="size-3.5" /></button>
                    <button type="button" class="btn btn-ghost btn-sm btn-icon" data-move="down" aria-label="{{ __('editor.move_down') }}"><x-icon name="arrow-down" class="size-3.5" /></button>
                    @unless ($locked)
                        <button type="button" class="btn btn-ghost btn-sm btn-icon" data-load-block-form="{{ route('blocks.edit', $block) }}" aria-label="{{ __('common.edit') }}"><x-icon name="pencil" class="size-3.5" /></button>
                    @endunless
                    <form method="POST" action="{{ route('blocks.visibility', $block) }}" data-editor-form>@csrf
                        <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                        <input type="hidden" name="visible" value="{{ $block->is_visible ? 0 : 1 }}">
                        <button class="btn btn-ghost btn-sm btn-icon" aria-label="{{ $block->is_visible ? __('editor.hide') : __('editor.show') }}"><x-icon :name="$block->is_visible ? 'eye' : 'eye-off'" class="size-3.5" /></button>
                    </form>
                    @unless ($locked)
                        <form method="POST" action="{{ route('blocks.duplicate', $block) }}" data-editor-form class="hidden sm:block">@csrf
                            <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                            <button class="btn btn-ghost btn-sm btn-icon" aria-label="{{ __('editor.duplicate') }}"><x-icon name="copy" class="size-3.5" /></button>
                        </form>
                    @endunless
                    <form method="POST" action="{{ route('blocks.destroy', $block) }}" data-editor-form data-confirm="{{ __('editor.delete_block_confirm') }}">@csrf @method('DELETE')
                        <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                        <button class="btn btn-danger-ghost btn-sm btn-icon" aria-label="{{ __('common.delete') }}"><x-icon name="trash-2" class="size-3.5" /></button>
                    </form>
                </div>
            </li>
        @endforeach
    </ol>
@endif
