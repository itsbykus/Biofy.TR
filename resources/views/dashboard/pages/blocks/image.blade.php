<x-form.media name="data[media_id]" :label="__('blocks.fields.image')" :value="$data['media_id'] ?? null" purpose="block" :library="$media" />
<x-form.input name="data[alt]" :label="__('blocks.fields.alt')" :value="$data['alt'] ?? ''" maxlength="150" :help="__('blocks.help.alt')" />
<x-form.input name="data[caption]" :label="__('blocks.fields.caption')" :value="$data['caption'] ?? ''" maxlength="200" />
<x-form.input name="data[url]" type="url" :label="__('blocks.fields.image_link')" :value="$data['url'] ?? ''" placeholder="https://" />
