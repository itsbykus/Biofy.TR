<x-form.input name="data[title]" :label="__('blocks.fields.title')" :value="$data['title'] ?? ''" maxlength="100" required autofocus />
<x-form.input name="data[url]" type="url" :label="__('blocks.fields.url')" :value="$data['url'] ?? ''" placeholder="https://" inputmode="url" required :help="__('blocks.help.url')" />
<x-form.input name="data[description]" :label="__('blocks.fields.description')" :value="$data['description'] ?? ''" maxlength="200" />
<x-form.media name="data[media_id]" :label="__('blocks.fields.thumbnail')" :value="$data['media_id'] ?? null" purpose="block" :library="$media" />
<div class="grid gap-3 sm:grid-cols-2">
    <x-form.checkbox name="data[new_tab]" :label="__('blocks.fields.new_tab')" :checked="$data['new_tab'] ?? true" />
    <x-form.checkbox name="data[highlight]" :label="__('blocks.fields.highlight')" :checked="$data['highlight'] ?? false" />
</div>
