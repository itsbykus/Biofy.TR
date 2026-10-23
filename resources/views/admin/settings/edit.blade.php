@extends('layouts.admin')

@section('title', __('admin.nav.settings'))

@section('content')
    <x-page-header :title="__('admin.nav.settings')" :description="__('admin.settings.help')" />

    <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
        <nav class="flex gap-1 overflow-x-auto lg:flex-col" aria-label="{{ __('admin.nav.settings') }}">
            @foreach ($groups as $g)
                <a href="{{ route('admin.settings.edit', $g) }}" class="nav-link whitespace-nowrap" @if ($g === $group) aria-current="page" @endif>{{ __('admin.settings.groups.'.$g) }}</a>
            @endforeach
        </nav>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.settings.update', $group) }}" enctype="multipart/form-data" class="card card-pad space-y-5" novalidate>
                @csrf @method('PUT')
                <h2 class="card-title">{{ __('admin.settings.groups.'.$group) }}</h2>
                @if (__('admin.settings.group_help.'.$group) !== 'admin.settings.group_help.'.$group)
                    <p class="muted -mt-3">{{ __('admin.settings.group_help.'.$group) }}</p>
                @endif

                @foreach ($fields as $key => $field)
                    @php
                        $input = str_replace('.', '__', $key);
                        $label = setting_label($key);
                        $help = setting_label($key, 'help_text');
                    @endphp

                    @switch($field['type'])
                        @case('bool')
                            <x-form.checkbox :name="$input" :label="$label" :checked="$field['value']" :help="$help" />
                            @break
                        @case('translatable')
                            <x-form.translatable :name="$input" :label="$label" :value="$field['value'] ?? []" :textarea="in_array($key, ['site.description', 'home.hero_subtitle', 'maintenance.message', 'domains.instructions', 'site.footer_text', 'seo.default_description'], true)" :rows="2" :help="$help" />
                            @break
                        @case('secret')
                            <div>
                                <label class="label" for="f-{{ $input }}">{{ $label }}</label>
                                <input id="f-{{ $input }}" type="password" name="{{ $input }}" class="input" autocomplete="new-password" placeholder="{{ $field['has_secret'] ? __('admin.settings.secret_saved') : __('admin.settings.secret_empty') }}">
                                <p class="help">{{ $field['has_secret'] ? __('admin.settings.secret_keep') : ($help ?? '') }}</p>
                                @if ($field['has_secret'])
                                    <label class="mt-1 flex items-center gap-2 text-xs text-zinc-600"><input type="checkbox" name="{{ $input }}__clear" value="1" class="checkbox">{{ __('admin.settings.secret_clear') }}</label>
                                @endif
                            </div>
                            @break
                        @case('media')
                            <div>
                                <span class="label">{{ $label }}</span>
                                <div class="flex items-center gap-4">
                                    <div class="grid size-16 place-items-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                                        @if ($field['media'])<img src="{{ $field['media']->url() }}" alt="" class="max-h-full max-w-full object-contain">@else<x-icon name="image" class="text-zinc-400" />@endif
                                    </div>
                                    <div class="space-y-1">
                                        <input type="file" name="{{ $input }}" accept="image/png,image/jpeg,image/webp" class="text-sm" aria-label="{{ $label }}">
                                        @if ($field['media'])<label class="flex items-center gap-2 text-xs text-zinc-600"><input type="checkbox" name="{{ $input }}__remove" value="1" class="checkbox">{{ __('common.remove') }}</label>@endif
                                    </div>
                                </div>
                                @if ($help)<p class="help">{{ $help }}</p>@endif
                            </div>
                            @break
                        @case('color')
                            <div>
                                <label class="label" for="f-{{ $input }}">{{ $label }}</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" value="{{ $field['value'] }}" data-color-sync="#f-{{ $input }}" class="h-10 w-12 rounded-lg border border-zinc-300 p-1" aria-label="{{ $label }}">
                                    <input id="f-{{ $input }}" name="{{ $input }}" value="{{ old($input, $field['value']) }}" class="input max-w-36 font-mono uppercase">
                                </div>
                                @if ($help)<p class="help">{{ $help }}</p>@endif
                            </div>
                            @break
                        @case('json')
                            @if ($key === 'site.social_links')
                                <fieldset>
                                    <legend class="label">{{ $label }}</legend>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach (['instagram', 'x', 'youtube', 'tiktok', 'linkedin', 'facebook', 'github', 'telegram'] as $platform)
                                            <x-form.input :name="$input.'['.$platform.']'" :value="$field['value'][$platform] ?? ''" :placeholder="__('social.platforms.'.$platform).' URL'" :aria-label="__('social.platforms.'.$platform)" />
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif
                            @break
                        @default
                            @if ($field['options'])
                                <x-form.select :name="$input" :label="$label" :value="$field['value']" :options="$field['options']" :help="$help" />
                            @else
                                <x-form.input :name="$input" :type="$field['type'] === 'int' ? 'number' : 'text'" :label="$label" :value="$field['value']" :help="$help" />
                            @endif
                    @endswitch
                @endforeach

                @if ($group === 'oauth')
                    <div class="rounded-lg bg-zinc-50 p-4 text-sm">
                        <p class="font-medium">{{ __('admin.settings.google_redirect') }}</p>
                        <p class="code mt-1 inline-block">{{ $googleRedirect }}</p>
                        <p class="help mt-2">{{ __('admin.settings.google_setup') }}</p>
                    </div>
                @endif

                <div class="flex justify-end"><button class="btn btn-dark">{{ __('common.save') }}</button></div>
            </form>

            @if ($group === 'mail')
                <div class="card card-pad flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-zinc-600">{{ __('admin.settings.mail_test_help') }}</p>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.settings.mail.check') }}">@csrf<button class="btn btn-secondary">{{ __('admin.settings.check_connection') }}</button></form>
                        <form method="POST" action="{{ route('admin.settings.mail.test') }}">@csrf<button class="btn btn-dark">{{ __('admin.settings.send_test') }}</button></form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
