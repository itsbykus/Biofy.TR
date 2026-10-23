@php
    $data = $block?->data ?? $type->defaults();
    $tz = auth()->user()->timezone ?: setting('site.timezone');
    $fmt = fn ($dt) => $dt ? $dt->copy()->setTimezone($tz)->format('Y-m-d\TH:i') : '';
@endphp
<form method="POST" action="{{ $block ? route('blocks.update', $block) : route('blocks.store', $profile) }}" data-editor-form novalidate>
    @csrf
    @if ($block) @method('PUT') @endif
    <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
    <input type="hidden" name="type" value="{{ $type->key() }}">

    <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
        <h2 class="flex items-center gap-2 text-base font-semibold"><x-icon :name="$type->icon()" />{{ $type->name() }}</h2>
        <button type="button" class="btn btn-ghost btn-icon" data-dialog-close aria-label="{{ __('common.close') }}"><x-icon name="x" /></button>
    </div>

    <div class="max-h-[65vh] space-y-4 overflow-y-auto px-5 py-5">
        @include('dashboard.pages.blocks.'.$type->key(), ['data' => $data])

        @if ($type->isSchedulable())
            <details class="rounded-lg border border-zinc-200 p-3" @if ($block?->starts_at || $block?->ends_at) open @endif>
                <summary class="cursor-pointer text-sm font-medium">{{ __('editor.schedule') }} @unless ($can['scheduling'])<span class="badge badge-yellow">Pro</span>@endunless</summary>
                @if ($can['scheduling'])
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <x-form.input name="starts_at" type="datetime-local" :label="__('editor.starts_at')" :value="$fmt($block?->starts_at)" />
                        <x-form.input name="ends_at" type="datetime-local" :label="__('editor.ends_at')" :value="$fmt($block?->ends_at)" />
                    </div>
                    <p class="help">{{ __('editor.schedule_help', ['tz' => $tz]) }}</p>
                @else
                    <p class="mt-2 text-sm text-zinc-500">{{ __('editor.schedule_locked') }}</p>
                @endif
            </details>
        @endif
    </div>

    <div class="flex justify-end gap-2 border-t border-zinc-100 px-5 py-4">
        <button type="button" class="btn btn-ghost" data-dialog-close>{{ __('common.cancel') }}</button>
        <button class="btn btn-dark">{{ $block ? __('common.save') : __('editor.add_block') }}</button>
    </div>
</form>
