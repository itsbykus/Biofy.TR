<div {{ $attributes->merge(['class' => 'flex items-center gap-1 text-xs font-semibold']) }} role="group" aria-label="{{ __('common.language') }}">
    @foreach (config('biofy.locales') as $locale)
        <a href="{{ route('lang.switch', $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}"
           @class(['rounded px-1.5 py-1 uppercase', 'bg-zinc-900 text-white' => app()->getLocale() === $locale, 'text-zinc-500 hover:text-zinc-900' => app()->getLocale() !== $locale])
           @if (app()->getLocale() === $locale) aria-current="true" @endif>{{ $locale }}</a>
    @endforeach
</div>
