@props(['platform', 'label' => null])
@php($brand = \App\Support\Icons::brand($platform))
@if ($brand)
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" @if ($label === '') aria-hidden="true" focusable="false" @else role="img" aria-label="{{ $label ?? $brand['title'] }}" @endif><path d="{{ $brand['path'] }}" @if(! empty($brand['evenodd'])) fill-rule="evenodd" @endif/></svg>
@else
<x-icon :name="$platform === 'email' ? 'mail' : 'globe'" {{ $attributes }} :label="$label" />
@endif
