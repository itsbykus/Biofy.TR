@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'errorKey' => null, 'id' => null])
@php
    $id = $id ?? 'f-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
    $dotKey = $errorKey ?? trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $error = $errors->first($dotKey);
    $old = old($dotKey, $value);
@endphp
<div {{ $attributes->only('class')->merge(['class' => '']) }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($attributes->get('required'))<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ $old }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'input']) }}
        @if ($error) aria-invalid="true" @endif
        @if ($help || $error) aria-describedby="{{ $id }}-desc" @endif>
    <p id="{{ $id }}-desc" class="{{ $error ? 'error' : 'help' }}" @if (! $help && ! $error) hidden @endif>{{ $error ?: $help }}</p>
    <p class="error" data-error-for="{{ $dotKey }}" hidden></p>
</div>
