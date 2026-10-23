@props(['name', 'label', 'checked' => false, 'help' => null, 'value' => '1', 'id' => null])
@php
    $id = $id ?? 'f-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
    $dotKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $isChecked = old($dotKey) !== null ? (bool) old($dotKey) : (bool) $checked;
@endphp
<div {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-3']) }}>
    {{-- Hidden field so an unchecked box submits "0" --}}
    <input type="hidden" name="{{ $name }}" value="0">
    <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="{{ $value }}" class="checkbox mt-0.5" @checked($isChecked) {{ $attributes->except('class') }}
        @if ($help) aria-describedby="{{ $id }}-desc" @endif>
    <div>
        <label for="{{ $id }}" class="text-sm font-medium text-zinc-800">{{ $label }}</label>
        @if ($help)<p id="{{ $id }}-desc" class="help">{{ $help }}</p>@endif
        <p class="error" data-error-for="{{ $dotKey }}" hidden></p>
    </div>
</div>
