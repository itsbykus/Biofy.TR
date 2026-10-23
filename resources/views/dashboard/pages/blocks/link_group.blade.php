<x-form.input name="data[title]" :label="__('blocks.fields.group_title')" :value="$data['title'] ?? ''" maxlength="100" required autofocus />
<x-form.checkbox name="data[collapsed]" :label="__('blocks.fields.collapsed')" :checked="$data['collapsed'] ?? false" />
<div data-repeater data-max="20">
    <p class="label">{{ __('blocks.fields.links') }}</p>
    <div class="space-y-2" data-repeater-list>
        @foreach (($data['items'] ?? [['title' => '', 'url' => '']]) as $i => $item)
            <div class="grid gap-2 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[1fr_1fr_auto]" data-repeater-item>
                <input class="input" name="data[items][{{ $i }}][title]" value="{{ $item['title'] }}" placeholder="{{ __('blocks.fields.title') }}" maxlength="100" aria-label="{{ __('blocks.fields.title') }}">
                <input class="input" type="url" name="data[items][{{ $i }}][url]" value="{{ $item['url'] }}" placeholder="https://" aria-label="{{ __('blocks.fields.url') }}">
                <button type="button" class="btn btn-danger-ghost btn-sm btn-icon" data-repeater-remove aria-label="{{ __('common.remove') }}"><x-icon name="trash-2" class="size-3.5" /></button>
            </div>
        @endforeach
    </div>
    <template data-repeater-template>
        <div class="grid gap-2 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[1fr_1fr_auto]" data-repeater-item>
            <input class="input" name="data[items][__i__][title]" placeholder="{{ __('blocks.fields.title') }}" maxlength="100" aria-label="{{ __('blocks.fields.title') }}">
            <input class="input" type="url" name="data[items][__i__][url]" placeholder="https://" aria-label="{{ __('blocks.fields.url') }}">
            <button type="button" class="btn btn-danger-ghost btn-sm btn-icon" data-repeater-remove aria-label="{{ __('common.remove') }}"><x-icon name="trash-2" class="size-3.5" /></button>
        </div>
    </template>
    <button type="button" class="btn btn-secondary btn-sm mt-3" data-repeater-add><x-icon name="plus" class="size-3.5" />{{ __('blocks.add_link') }}</button>
    <p class="error" data-error-for="data.items" hidden></p>
</div>
