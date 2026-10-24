@extends('layouts.app')

@section('title', __('nav.qr'))

@section('content')
    <x-page-header :title="__('nav.qr')" :description="__('qr.help')" />

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        {{-- GET form: the preview is rendered on the server, nothing is stored. --}}
        <form method="GET" action="{{ route('qr.show') }}" class="card card-pad space-y-5" novalidate>
            <x-form.textarea name="content" :label="__('qr.content')" :value="$content" rows="3" :maxlength="\App\Services\QrCodeService::MAX_LENGTH" required :help="__('qr.content_help')" />
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (['fg' => $fg, 'bg' => $bg] as $field => $value)
                    <div>
                        <label class="label" for="f-{{ $field }}">{{ __('qr.'.$field) }}</label>
                        <div class="flex items-center gap-2">
                            <input type="color" value="{{ $value }}" data-color-sync="#f-{{ $field }}" class="h-10 w-12 rounded-lg border border-zinc-300 p-1" aria-label="{{ __('qr.'.$field) }}">
                            <input id="f-{{ $field }}" name="{{ $field }}" value="{{ old($field, $value) }}" class="input max-w-36 font-mono uppercase" pattern="#[0-9A-Fa-f]{6}">
                        </div>
                        @error($field)<p class="error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <p class="help">{{ __('qr.contrast_help') }}</p>
            <div class="flex justify-end"><button class="btn btn-dark"><x-icon name="qr-code" />{{ __('qr.generate') }}</button></div>
        </form>

        <aside class="card card-pad flex flex-col items-center gap-4 text-center">
            @if ($preview)
                <div class="w-full max-w-[18rem] overflow-hidden rounded-2xl border border-zinc-200 [&>svg]:h-auto [&>svg]:w-full">{!! $preview !!}</div>
                <p class="w-full truncate text-xs text-zinc-500" title="{{ $content }}">{{ $content }}</p>
                @php($query = ['content' => $content, 'fg' => $fg, 'bg' => $bg])
                <div class="flex flex-wrap justify-center gap-2">
                    <a href="{{ route('qr.download', ['png'] + $query) }}" class="btn btn-dark btn-sm"><x-icon name="download" class="size-3.5" />PNG</a>
                    <a href="{{ route('qr.download', ['svg'] + $query) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-3.5" />SVG</a>
                </div>
            @else
                <span class="grid size-16 place-items-center rounded-2xl bg-brand-soft text-brand-ink"><x-icon name="qr-code" class="size-7" /></span>
                <p class="text-sm text-zinc-500">{{ __('qr.empty') }}</p>
            @endif
        </aside>
    </div>
@endsection
