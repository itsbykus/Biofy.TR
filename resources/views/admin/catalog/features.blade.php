@extends('layouts.admin')

@section('title', __('admin.nav.features'))

@section('content')
    <x-page-header :title="__('admin.nav.features')" :description="__('admin.features.help')" />

    <div class="alert alert-info mb-4"><x-icon name="info" class="mt-0.5" /><p>{{ __('admin.features.resolution') }}</p></div>

    <div class="space-y-3">
        @foreach ($features as $feature)
            <details class="card">
                <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2 px-5 py-3">
                    <span><span class="font-semibold">{{ $feature->translate('name') }}</span> <span class="font-mono text-xs text-zinc-500">{{ $feature->key }}</span></span>
                    <span class="flex gap-1.5">
                        <span class="badge badge-gray">{{ $feature->type }}</span>
                        <span class="badge badge-gray">{{ __('admin.features.default') }}: {{ $feature->isBoolean() ? ($feature->default_value === '1' ? __('common.yes') : __('common.no')) : limit_label((int) $feature->default_value) }}</span>
                        @unless ($feature->platform_enabled)<span class="badge badge-red">{{ __('admin.features.platform_disabled') }}</span>@endunless
                        @if ($feature->platform_cap !== null)<span class="badge badge-yellow">{{ __('admin.features.cap') }}: {{ limit_label($feature->platform_cap) }}</span>@endif
                    </span>
                </summary>
                <form method="POST" action="{{ route('admin.features.update', $feature) }}" class="grid gap-4 border-t border-zinc-100 p-5 md:grid-cols-2">
                    @csrf @method('PUT')
                    <x-form.translatable name="name" :label="__('admin.features.name')" :value="$feature->name" :required-tr="true" class="md:col-span-2" />
                    <x-form.translatable name="description" :label="__('admin.features.description')" :value="$feature->description ?? []" class="md:col-span-2" />
                    @if ($feature->isBoolean())
                        <x-form.select name="default_value" :label="__('admin.features.default')" :value="$feature->default_value" :options="['1' => __('common.yes'), '0' => __('common.no')]" />
                    @else
                        <x-form.input name="default_value" type="number" min="-1" :label="__('admin.features.default')" :value="$feature->default_value" :help="__('admin.packages.unlimited_hint')" />
                        <x-form.input name="platform_cap" type="number" min="-1" :label="__('admin.features.cap')" :value="$feature->platform_cap" :help="__('admin.features.cap_help')" />
                    @endif
                    <x-form.checkbox name="platform_enabled" :label="__('admin.features.enabled')" :checked="$feature->platform_enabled" :help="__('admin.features.enabled_help')" class="md:col-span-2" />
                    <div class="md:col-span-2"><button class="btn btn-dark">{{ __('common.save') }}</button></div>
                </form>
            </details>
        @endforeach
    </div>
@endsection
