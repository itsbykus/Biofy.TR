<x-form.input name="data[text]" :label="__('blocks.fields.text')" :value="$data['text'] ?? ''" maxlength="120" required autofocus />
<x-form.select name="data[size]" :label="__('blocks.fields.size')" :value="$data['size'] ?? 'md'" :options="['md' => __('blocks.sizes.md'), 'lg' => __('blocks.sizes.lg')]" />
