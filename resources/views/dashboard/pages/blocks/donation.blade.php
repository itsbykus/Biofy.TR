<x-form.input name="data[title]" :label="__('blocks.fields.title')" :value="$data['title'] ?? ''" maxlength="100" required autofocus :placeholder="__('blocks.placeholders.donation_title')" />
<x-form.input name="data[url]" type="url" :label="__('blocks.fields.url')" :value="$data['url'] ?? ''" placeholder="https://" required />
<x-form.input name="data[description]" :label="__('blocks.fields.description')" :value="$data['description'] ?? ''" maxlength="200" />
<x-form.select name="data[platform]" :label="__('blocks.fields.platform')" :value="$data['platform'] ?? 'other'" :options="collect(\App\Blocks\Types\DonationBlock::PLATFORMS)->mapWithKeys(fn ($p) => [$p => __('blocks.donation_platforms.'.$p)])->all()" />
<p class="help">{{ __('blocks.help.donation') }}</p>
