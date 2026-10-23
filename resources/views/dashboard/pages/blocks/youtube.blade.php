<x-form.input name="data[url]" type="url" :label="__('blocks.fields.youtube_url')" :value="$data['url'] ?? ''" placeholder="https://www.youtube.com/watch?v=…" required autofocus />
<x-form.input name="data[title]" :label="__('blocks.fields.title')" :value="$data['title'] ?? ''" maxlength="100" />
<p class="help">{{ __('blocks.help.embed_privacy') }}</p>
