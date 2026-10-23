<x-form.input name="data[url]" type="url" :label="__('blocks.fields.music_url')" :value="$data['url'] ?? ''" placeholder="https://open.spotify.com/…" required autofocus :help="__('blocks.help.music')" />
<x-form.input name="data[title]" :label="__('blocks.fields.title')" :value="$data['title'] ?? ''" maxlength="100" />
