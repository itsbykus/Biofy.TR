@extends('layouts.app')

@section('title', __('nav.themes'))

@section('content')
    <x-page-header :title="__('nav.themes')" :description="__('themes.gallery_help')" />

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($themes as $key => $theme)
            @php($locked = $theme['is_premium'] && ! $canPremium)
            <div class="card overflow-hidden">
                @include('partials.theme-thumb', ['t' => $theme['preview'], 'key' => $key])
                <div class="space-y-2 p-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold">{{ $theme['name'][app()->getLocale()] ?? $theme['name']['tr'] }}</p>
                        @if ($theme['is_premium'])<span class="badge badge-yellow">Pro</span>@endif
                    </div>
                    <p class="text-xs text-zinc-500">{{ $theme['description'][app()->getLocale()] ?? $theme['description']['tr'] }}</p>
                    @if ($profiles->isNotEmpty() && ! $locked)
                        <form method="POST" action="{{ route('pages.theme', $profiles->first()) }}" class="flex gap-1.5" data-theme-apply>
                            @csrf @method('PUT')
                            <input type="hidden" name="theme_key" value="{{ $key }}">
                            @if ($profiles->count() > 1)
                                <select class="select min-h-8 py-1 text-xs" aria-label="{{ __('profiles.page') }}" data-theme-target>
                                    @foreach ($profiles as $profile)
                                        <option value="{{ route('pages.theme', $profile) }}">/{{ $profile->slug }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button class="btn btn-dark btn-sm shrink-0">{{ __('themes.apply') }}</button>
                        </form>
                    @elseif ($locked)
                        <a href="{{ route('plan.show') }}" class="btn btn-secondary btn-sm w-full">{{ __('themes.requires_upgrade_short') }}</a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.querySelectorAll('[data-theme-target]').forEach((select) => {
            select.addEventListener('change', () => { select.form.action = select.value; });
        });
    </script>
@endpush
