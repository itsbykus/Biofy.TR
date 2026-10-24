<x-form.input name="data[title]" :label="__('blocks.fields.title')" :value="$data['title'] ?? ''" maxlength="100" required autofocus />
<x-form.input name="data[url]" type="url" :label="__('blocks.fields.url')" :value="$data['url'] ?? ''" placeholder="https://" inputmode="url" required :help="__('blocks.help.url')" />
<x-form.input name="data[description]" :label="__('blocks.fields.description')" :value="$data['description'] ?? ''" maxlength="200" />

{{-- Button look: icon on the left (recognised from the URL by default) and an optional background picture. --}}
@php($iconChoice = (string) old('data.icon', $data['icon'] ?? ''))
<div>
    <label for="f-data-icon" class="label">{{ __('blocks.fields.icon') }}</label>
    <select id="f-data-icon" name="data[icon]" class="select" aria-describedby="f-data-icon-desc">
        <option value="" @selected($iconChoice === '')>{{ __('blocks.icon_auto') }}</option>
        <option value="none" @selected($iconChoice === 'none')>{{ __('blocks.icon_none') }}</option>
        <optgroup label="{{ __('blocks.icon_group_brands') }}">
            @foreach (\App\Support\LinkIcons::brandOptions() as $key => $title)
                <option value="brand:{{ $key }}" @selected($iconChoice === 'brand:'.$key)>{{ $title }}</option>
            @endforeach
        </optgroup>
        <optgroup label="{{ __('blocks.icon_group_icons') }}">
            @foreach (\App\Support\LinkIcons::UI as $name)
                <option value="icon:{{ $name }}" @selected($iconChoice === 'icon:'.$name)>{{ __('blocks.icon_names.'.$name) }}</option>
            @endforeach
        </optgroup>
    </select>
    <p id="f-data-icon-desc" class="help">{{ __('blocks.help.icon') }}</p>
    <p class="error" data-error-for="data.icon" hidden></p>
</div>

<x-form.media name="data[media_id]" :label="__('blocks.fields.thumbnail')" :value="$data['media_id'] ?? null" purpose="block" :library="$media" :help="__('blocks.help.thumbnail')" />
<x-form.media name="data[bg_media_id]" :label="__('blocks.fields.button_background')" :value="$data['bg_media_id'] ?? null" purpose="block" :library="$media" :help="__('blocks.help.button_background')" />
<div class="grid gap-3 sm:grid-cols-2">
    <x-form.checkbox name="data[new_tab]" :label="__('blocks.fields.new_tab')" :checked="$data['new_tab'] ?? true" />
    <x-form.checkbox name="data[highlight]" :label="__('blocks.fields.highlight')" :checked="$data['highlight'] ?? false" />
</div>
