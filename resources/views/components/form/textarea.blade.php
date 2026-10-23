@props(['name', 'label' => null, 'value' => null, 'help' => null, 'rows' => 3, 'id' => null])
@php
    $id = $id ?? 'f-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
    $dotKey = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $error = $errors->first($dotKey);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($attributes->get('required'))<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->except('class')->merge(['class' => 'textarea']) }}
        @if ($error) aria-invalid="true" @endif @if ($help || $error) aria-describedby="{{ $id }}-desc" @endif>{{ old($dotKey, $value) }}</textarea>
    <p id="{{ $id }}-desc" class="{{ $error ? 'error' : 'help' }}" @if (! $help && ! $error) hidden @endif>{{ $error ?: $help }}</p>
    <p class="error" data-error-for="{{ $dotKey }}" hidden></p>
</div>
