@props(['name', 'label' => null, 'options' => [], 'value' => null, 'help' => null, 'placeholder' => null, 'id' => null])
@php
    $id = $id ?? 'f-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
    $dotKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $error = $errors->first($dotKey);
    $selected = (string) old($dotKey, $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->except('class')->merge(['class' => 'select']) }}
        @if ($error) aria-invalid="true" @endif @if ($help || $error) aria-describedby="{{ $id }}-desc" @endif>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <p id="{{ $id }}-desc" class="{{ $error ? 'error' : 'help' }}" @if (! $help && ! $error) hidden @endif>{{ $error ?: $help }}</p>
    <p class="error" data-error-for="{{ $dotKey }}" hidden></p>
</div>
