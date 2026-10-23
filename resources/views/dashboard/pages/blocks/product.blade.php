<div class="grid gap-3 sm:grid-cols-2">
    <x-form.input name="data[title]" :label="__('blocks.fields.section_title')" :value="$data['title'] ?? ''" maxlength="100" />
    <x-form.select name="data[layout]" :label="__('blocks.fields.layout')" :value="$data['layout'] ?? 'list'" :options="['list' => __('blocks.layouts.list'), 'grid' => __('blocks.layouts.grid')]" />
</div>
<div data-repeater data-max="12">
    <p class="label">{{ __('blocks.fields.products') }}</p>
    <div class="space-y-3" data-repeater-list>
        @foreach (($data['items'] ?? [['title' => '', 'description' => '', 'price' => '', 'url' => '', 'media_id' => null]]) as $i => $item)
            @include('dashboard.pages.blocks._product-row', ['i' => $i, 'item' => $item])
        @endforeach
    </div>
    <template data-repeater-template>
        @include('dashboard.pages.blocks._product-row', ['i' => '__i__', 'item' => ['title' => '', 'description' => '', 'price' => '', 'url' => '', 'media_id' => null]])
    </template>
    <button type="button" class="btn btn-secondary btn-sm mt-3" data-repeater-add><x-icon name="plus" class="size-3.5" />{{ __('blocks.add_product') }}</button>
    <p class="error" data-error-for="data.items" hidden></p>
</div>
