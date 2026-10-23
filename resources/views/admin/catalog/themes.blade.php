@extends('layouts.admin')

@section('title', __('admin.nav.themes'))

@section('content')
    <x-page-header :title="__('admin.nav.themes')" :description="__('admin.themes.help')" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($themes as $key => $theme)
            <form method="POST" action="{{ route('admin.themes.update', $key) }}" class="card overflow-hidden">
                @csrf @method('PUT')
                <div class="grid grid-cols-[96px_1fr] gap-3 p-3">
                    <div class="overflow-hidden rounded-lg border border-zinc-200">@include('partials.theme-thumb', ['t' => \App\Themes\ThemeSettings::resolve($theme, []), 'key' => 'adm-'.$key])</div>
                    <div class="space-y-2">
                        <p class="font-semibold">{{ $theme['name'][app()->getLocale()] ?? $theme['name']['tr'] }}</p>
                        <p class="text-xs text-zinc-500">{{ $theme['description'][app()->getLocale()] ?? $theme['description']['tr'] }}</p>
                        <x-form.checkbox name="is_active" :label="__('admin.themes.active')" :checked="$theme['row']?->is_active ?? true" :id="'ta-'.$key" />
                        <x-form.checkbox name="is_premium" :label="__('admin.themes.premium')" :checked="$theme['row']?->is_premium ?? $theme['premium']" :id="'tp-'.$key" />
                        <div class="flex items-center gap-2">
                            <label for="ts-{{ $key }}" class="text-xs text-zinc-500">{{ __('admin.packages.sort') }}</label>
                            <input id="ts-{{ $key }}" type="number" name="sort_order" min="0" value="{{ $theme['row']?->sort_order ?? 0 }}" class="input min-h-8 w-20 py-1">
                            <button class="btn btn-dark btn-sm ml-auto">{{ __('common.save') }}</button>
                        </div>
                    </div>
                </div>
            </form>
        @endforeach
    </div>
@endsection
