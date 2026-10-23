@props(['name', 'label', 'value' => null, 'purpose' => 'block', 'library' => [], 'help' => null])
@php
    $current = $value ? collect($library)->firstWhere('id', (int) $value) : null;
    $currentUrl = $current?->url();
    $id = 'm-'.preg_replace('/[^a-z0-9]+/i', '-', $name);
@endphp
<div {{ $attributes->merge(['class' => '']) }} data-media-field data-purpose="{{ $purpose }}" data-upload-url="{{ route('media.store') }}">
    <span class="label" id="{{ $id }}-label">{{ $label }}</span>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    <div class="flex items-center gap-3">
        <div class="grid size-16 shrink-0 place-items-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
            <img data-media-preview src="{{ $currentUrl }}" alt="" class="size-full object-cover" @if (! $currentUrl) hidden @endif>
            <x-icon name="image" class="size-5 text-zinc-400" data-media-empty :hidden="(bool) $currentUrl" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label class="btn btn-secondary btn-sm">
                <x-icon name="upload" class="size-3.5" />{{ __('media.upload') }}
                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" data-media-upload aria-labelledby="{{ $id }}-label">
            </label>
            @if (count($library))
                <details class="relative">
                    <summary class="btn btn-ghost btn-sm list-none">{{ __('media.library') }}</summary>
                    <div class="absolute left-0 z-20 mt-1 grid max-h-64 w-72 grid-cols-4 gap-1.5 overflow-auto rounded-lg border border-zinc-200 bg-white p-2 shadow-lg">
                        @foreach ($library as $file)
                            <button type="button" class="aspect-square overflow-hidden rounded-md ring-1 ring-zinc-200 hover:ring-ink" data-media-pick="{{ $file->id }}" data-url="{{ $file->url() }}" aria-label="{{ $file->original_name }}">
                                <img src="{{ $file->url() }}" alt="" loading="lazy" class="size-full object-cover">
                            </button>
                        @endforeach
                    </div>
                </details>
            @endif
            <button type="button" class="btn btn-ghost btn-sm" data-media-clear>{{ __('common.remove') }}</button>
        </div>
    </div>
    @if ($help)<p class="help">{{ $help }}</p>@endif
    <p class="error" data-error-for="{{ trim(str_replace(['[', ']'], ['.', ''], $name), '.') }}" hidden></p>
</div>
