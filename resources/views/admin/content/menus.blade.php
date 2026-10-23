@extends('layouts.admin')

@section('title', __('admin.nav.menus'))

@section('content')
    <x-page-header :title="__('admin.nav.menus')" :description="__('admin.menus.help')" />

    <form method="POST" action="{{ route('admin.menus.update') }}" class="grid gap-6 lg:grid-cols-2" novalidate>
        @csrf @method('PUT')
        @foreach (['header' => $header, 'footer' => $footer] as $menu => $items)
            <section class="card card-pad" data-repeater data-max="{{ $menu === 'header' ? 12 : 20 }}">
                <h2 class="card-title">{{ __('admin.menus.'.$menu) }}</h2>
                <div class="mt-4 space-y-2" data-repeater-list>
                    @foreach ($items as $i => $item)
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_1.2fr_auto]" data-repeater-item>
                            <input class="input" name="{{ $menu }}[{{ $i }}][label_tr]" value="{{ $item['label']['tr'] ?? '' }}" placeholder="Türkçe" aria-label="{{ __('admin.menus.label') }} (TR)">
                            <input class="input" name="{{ $menu }}[{{ $i }}][label_en]" value="{{ $item['label']['en'] ?? '' }}" placeholder="English" aria-label="{{ __('admin.menus.label') }} (EN)">
                            <input class="input font-mono text-xs" name="{{ $menu }}[{{ $i }}][url]" value="{{ $item['url'] }}" placeholder="/pricing" aria-label="URL">
                            <button type="button" class="btn btn-danger-ghost btn-icon" data-repeater-remove aria-label="{{ __('common.remove') }}"><x-icon name="trash-2" class="size-3.5" /></button>
                        </div>
                    @endforeach
                </div>
                <template data-repeater-template>
                    <div class="grid gap-2 sm:grid-cols-[1fr_1fr_1.2fr_auto]" data-repeater-item>
                        <input class="input" name="{{ $menu }}[__i__][label_tr]" placeholder="Türkçe" aria-label="{{ __('admin.menus.label') }} (TR)">
                        <input class="input" name="{{ $menu }}[__i__][label_en]" placeholder="English" aria-label="{{ __('admin.menus.label') }} (EN)">
                        <input class="input font-mono text-xs" name="{{ $menu }}[__i__][url]" placeholder="/pricing" aria-label="URL">
                        <button type="button" class="btn btn-danger-ghost btn-icon" data-repeater-remove aria-label="{{ __('common.remove') }}"><x-icon name="trash-2" class="size-3.5" /></button>
                    </div>
                </template>
                <button type="button" class="btn btn-secondary btn-sm mt-3" data-repeater-add><x-icon name="plus" class="size-3.5" />{{ __('admin.menus.add') }}</button>
            </section>
        @endforeach
        <div class="lg:col-span-2">
            <p class="help mb-3">{{ __('admin.menus.url_help') }}</p>
            <button class="btn btn-dark">{{ __('common.save') }}</button>
        </div>
    </form>
@endsection
