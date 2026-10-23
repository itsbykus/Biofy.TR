@props(['name', 'label' => null])
<svg {{ $attributes->merge(['class' => 'size-4 shrink-0']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" @if($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif>{!! \App\Support\Icons::ui($name) !!}</svg>
