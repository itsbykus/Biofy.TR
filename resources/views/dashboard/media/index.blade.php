@extends('layouts.app')

@section('title', __('nav.media'))

@section('content')
    <x-page-header :title="__('nav.media')" :description="__('media.help')">
        <x-slot:actions>
            <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="purpose" value="block">
                <label class="btn btn-primary">
                    <x-icon name="upload" />{{ __('media.upload') }}
                    <input type="file" name="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" data-autosubmit-file required>
                </label>
            </form>
        </x-slot:actions>
    </x-page-header>

    @php($pct = $storage['quota'] === PHP_INT_MAX ? 0 : min(100, round($storage['used'] / max(1, $storage['quota']) * 100)))
    <div class="card mb-5 p-4">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium">{{ __('media.storage') }}</span>
            <span class="tabular-nums text-zinc-600">{{ human_bytes($storage['used']) }} / {{ $storage['quota'] === PHP_INT_MAX ? __('common.unlimited') : human_bytes($storage['quota']) }}</span>
        </div>
        <svg class="meter mt-2" viewBox="0 0 100 2" preserveAspectRatio="none" role="img" aria-label="{{ $pct }}%"><rect x="0" y="0" height="2" width="{{ $pct }}" /></svg>
        <p class="help">{{ __('media.rules') }}</p>
    </div>

    @if ($files->isEmpty())
        <x-empty icon="images" :title="__('media.empty_title')" :text="__('media.empty_text')" />
    @else
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach ($files as $file)
                <li class="card overflow-hidden">
                    <img src="{{ $file->url() }}" alt="{{ $file->original_name }}" loading="lazy" class="aspect-square w-full bg-zinc-100 object-cover">
                    <div class="space-y-1 p-2 text-xs">
                        <p class="truncate font-medium" title="{{ $file->original_name }}">{{ $file->original_name }}</p>
                        <p class="text-zinc-500">{{ $file->width }}×{{ $file->height }} · {{ human_bytes($file->size) }}</p>
                        <div class="flex items-center justify-between">
                            @if (isset($inUse[$file->id]))
                                <span class="badge badge-green">{{ __('media.in_use_badge') }}</span>
                            @else
                                <span class="badge badge-gray">{{ __('media.unused') }}</span>
                                <form method="POST" action="{{ route('media.destroy', $file) }}" data-confirm="{{ __('media.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm btn-icon" aria-label="{{ __('common.delete') }}"><x-icon name="trash-2" class="size-3.5" /></button></form>
                            @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="mt-5">{{ $files->links() }}</div>
    @endif
@endsection

@push('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.querySelector('[data-autosubmit-file]')?.addEventListener('change', (e) => e.target.form.submit());
    </script>
@endpush
