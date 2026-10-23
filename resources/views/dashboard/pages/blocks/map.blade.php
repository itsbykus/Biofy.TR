<x-form.input name="data[label]" :label="__('blocks.fields.label')" :value="$data['label'] ?? ''" maxlength="100" :placeholder="__('profile.directions')" />
<x-form.input name="data[address]" :label="__('blocks.fields.address')" :value="$data['address'] ?? ''" maxlength="250" required autofocus />
<div class="grid gap-3 sm:grid-cols-2">
    <x-form.input name="data[lat]" :label="__('blocks.fields.lat')" :value="$data['lat'] ?? ''" inputmode="decimal" placeholder="41.0082" />
    <x-form.input name="data[lon]" :label="__('blocks.fields.lon')" :value="$data['lon'] ?? ''" inputmode="decimal" placeholder="28.9784" />
</div>
<x-form.checkbox name="data[show_map]" :label="__('blocks.fields.show_map')" :checked="$data['show_map'] ?? false" :help="__('blocks.help.map')" />
