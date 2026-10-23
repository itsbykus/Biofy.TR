<div class="space-y-2 rounded-lg border border-zinc-200 p-3" data-repeater-item>
    <div class="grid gap-2 sm:grid-cols-[1fr_140px]">
        <input class="input" name="data[items][{{ $i }}][title]" value="{{ $item['title'] }}" placeholder="{{ __('blocks.fields.product_name') }}" maxlength="100" aria-label="{{ __('blocks.fields.product_name') }}">
        <input class="input" name="data[items][{{ $i }}][price]" value="{{ $item['price'] }}" placeholder="{{ __('blocks.fields.price') }}" maxlength="40" aria-label="{{ __('blocks.fields.price') }}">
    </div>
    <textarea class="textarea min-h-14" name="data[items][{{ $i }}][description]" rows="2" placeholder="{{ __('blocks.fields.description') }}" maxlength="300" aria-label="{{ __('blocks.fields.description') }}">{{ $item['description'] }}</textarea>
    <input class="input" type="url" name="data[items][{{ $i }}][url]" value="{{ $item['url'] }}" placeholder="https://" aria-label="{{ __('blocks.fields.url') }}">
    <x-form.media :name="'data[items]['.$i.'][media_id]'" :label="__('blocks.fields.image')" :value="$item['media_id'] ?? null" purpose="block" :library="$media" />
    <button type="button" class="btn btn-danger-ghost btn-sm" data-repeater-remove>{{ __('common.remove') }}</button>
</div>
