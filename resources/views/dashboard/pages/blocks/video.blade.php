<x-form.input name="data[url]" type="url" :label="__('blocks.fields.video_url')" :value="$data['url'] ?? ''" placeholder="https://www.youtube.com/watch?v=… / https://vimeo.com/…" required autofocus />
<x-form.input name="data[title]" :label="__('blocks.fields.title')" :value="$data['title'] ?? ''" maxlength="100" />
<p class="help">{{ __('blocks.help.embed_privacy') }}</p>
