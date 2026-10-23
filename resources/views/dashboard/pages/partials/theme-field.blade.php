@php
    $def = $themeFields[$field];
    $name = 'settings['.$field.']';
    $value = $themeSettings[$field] ?? null;
    $id = 'tf-'.$field;
@endphp
@if ($def['type'] === 'color')
    <div>
        <label for="{{ $id }}" class="label">{{ __('themes.fields.'.$field) }}</label>
        <div class="flex items-center gap-2">
            <input type="color" class="h-10 w-12 cursor-pointer rounded-lg border border-zinc-300 bg-white p-1" value="{{ $value }}" data-color-sync="#{{ $id }}" aria-label="{{ __('themes.fields.'.$field) }}">
            <input id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" class="input font-mono uppercase" pattern="#[0-9A-Fa-f]{6}" maxlength="7">
        </div>
    </div>
@elseif ($def['type'] === 'enum')
    @php($options = $field === 'font' || $field === 'heading_font'
        ? collect($fonts)->map(fn ($f) => $f['label'])->all()
        : collect($def['options'])->mapWithKeys(fn ($o) => [$o => __('themes.options.'.$o)])->all())
    <x-form.select :name="$name" :id="$id" :label="__('themes.fields.'.$field)" :value="$value" :options="$options" />
@elseif ($def['type'] === 'int')
    <div>
        <label for="{{ $id }}" class="label">{{ __('themes.fields.'.$field) }} <span class="font-normal text-zinc-500">({{ $def['min'] }}–{{ $def['max'] }})</span></label>
        <input id="{{ $id }}" type="number" name="{{ $name }}" value="{{ $value }}" min="{{ $def['min'] }}" max="{{ $def['max'] }}" class="input">
    </div>
@elseif ($def['type'] === 'media')
    <x-form.media :name="$name" :label="__('themes.fields.'.$field)" :value="$value" purpose="banner" :library="$media" :help="__('themes.bg_image_help')" />
@endif
