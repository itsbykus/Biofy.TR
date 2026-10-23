<x-form.input name="data[title]" :label="__('blocks.fields.title')" :value="$data['title'] ?? ''" maxlength="100" required autofocus :placeholder="__('blocks.placeholders.calendar_title')" />
<x-form.input name="data[url]" type="url" :label="__('blocks.fields.booking_url')" :value="$data['url'] ?? ''" placeholder="https://calendly.com/…" required />
<x-form.input name="data[description]" :label="__('blocks.fields.description')" :value="$data['description'] ?? ''" maxlength="200" />
