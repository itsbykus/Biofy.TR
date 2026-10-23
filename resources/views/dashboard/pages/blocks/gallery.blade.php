<div class="grid gap-3 sm:grid-cols-2">
    <x-form.input name="data[title]" :label="__('blocks.fields.section_title')" :value="$data['title'] ?? ''" maxlength="100" />
    <x-form.select name="data[columns]" :label="__('blocks.fields.columns')" :value="(string) ($data['columns'] ?? 2)" :options="['2' => '2', '3' => '3']" />
</div>
<div data-repeater data-max="12">
    <p class="label">{{ __('blocks.fields.images') }}</p>
    <div class="space-y-3" data-repeater-list>
        @foreach (($data['items'] ?? [['media_id' => null, 'alt' => '']]) as $i => $item)
            <div class="space-y-2 rounded-lg border border-zinc-200 p-3" data-repeater-item>
                <x-form.media :name="'data[items]['.$i.'][media_id]'" :label="__('blocks.fields.image')" :value="$item['media_id'] ?? null" purpose="gallery" :library="$media" />
                <input class="input" name="data[items][{{ $i }}][alt]" value="{{ $item['alt'] ?? '' }}" placeholder="{{ __('blocks.fields.alt') }}" maxlength="150" aria-label="{{ __('blocks.fields.alt') }}">
                <button type="button" class="btn btn-danger-ghost btn-sm" data-repeater-remove>{{ __('common.remove') }}</button>
            </div>
        @endforeach
    </div>
    <template data-repeater-template>
        <div class="space-y-2 rounded-lg border border-zinc-200 p-3" data-repeater-item>
            <x-form.media name="data[items][__i__][media_id]" :label="__('blocks.fields.image')" purpose="gallery" :library="$media" />
            <input class="input" name="data[items][__i__][alt]" placeholder="{{ __('blocks.fields.alt') }}" maxlength="150" aria-label="{{ __('blocks.fields.alt') }}">
            <button type="button" class="btn btn-danger-ghost btn-sm" data-repeater-remove>{{ __('common.remove') }}</button>
        </div>
    </template>
    <button type="button" class="btn btn-secondary btn-sm mt-3" data-repeater-add><x-icon name="plus" class="size-3.5" />{{ __('blocks.add_image') }}</button>
    <p class="error" data-error-for="data.items" hidden></p>
</div>
