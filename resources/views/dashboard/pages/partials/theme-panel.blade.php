@php
    $s = $themeSettings;
    // Every plan (ThemeSettings tier "basic").
    $basicGroups = [
        'text' => ['font', 'align'],
        'buttons' => ['button_style', 'button_radius', 'button_border_width', 'button_border_color', 'button_shadow'],
        'social' => ['icon_style', 'icon_shape', 'icon_color', 'icon_custom_color'],
    ];
    // profile.advanced_customization (tier "advanced").
    $fieldGroups = [
        'background' => ['bg_type', 'bg_gradient_from', 'bg_gradient_to', 'bg_gradient_angle', 'bg_image_media_id', 'bg_overlay'],
        'typography' => ['heading_font', 'font_size'],
        'layout' => ['card_bg', 'card_style', 'avatar_size', 'avatar_shape', 'banner_style', 'page_width', 'spacing', 'animation'],
    ];
@endphp
<div class="space-y-6">
    {{-- Theme gallery: choosing a theme starts from its own design. --}}
    <section>
        <h2 class="mb-3 text-sm font-semibold">{{ __('themes.choose') }}</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            @foreach ($themes as $key => $theme)
                @php($preview = \App\Themes\ThemeSettings::resolve($theme, []))
                @php($locked = $theme['is_premium'] && ! $can['premium_themes'])
                <form method="POST" action="{{ route('pages.theme', $profile) }}" data-editor-form>
                    @csrf @method('PUT')
                    <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
                    <input type="hidden" name="theme_key" value="{{ $key }}">
                    <button class="theme-swatch w-full text-left disabled:cursor-not-allowed" @if ($profile->theme_key === $key) aria-current="true" @endif @disabled($locked)>
                        @include('partials.theme-thumb', ['t' => $preview, 'key' => $key])
                        <span class="flex items-center justify-between gap-2 bg-white px-3 py-2 text-xs font-semibold">
                            {{ $theme['name'][app()->getLocale()] ?? $theme['name']['tr'] }}
                            @if ($theme['is_premium'])<span class="badge badge-yellow">{{ $locked ? '🔒 ' : '' }}Pro</span>@endif
                        </span>
                    </button>
                </form>
            @endforeach
        </div>
        @unless ($can['premium_themes'])
            <p class="mt-3 text-xs text-zinc-500">{{ __('themes.premium_locked') }} <a class="font-medium underline" href="{{ route('plan.show') }}">{{ __('plans.see_plans') }}</a></p>
        @endunless
    </section>

    <form method="POST" action="{{ route('pages.theme', $profile) }}" data-editor-form class="space-y-5" novalidate>
        @csrf @method('PUT')
        <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">
        <input type="hidden" name="theme_key" value="{{ $profile->theme_key }}">

        <section class="card card-pad space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="card-title">{{ __('themes.colors') }}</h2>
                <button name="reset" value="1" class="btn btn-ghost btn-sm" data-confirm="{{ __('themes.reset_confirm') }}"><x-icon name="rotate-ccw" class="size-3.5" />{{ __('themes.reset') }}</button>
            </div>
            <x-form.select name="settings[mode]" :label="__('themes.fields.mode')" :value="$s['mode']" :options="['light' => __('themes.options.light'), 'dark' => __('themes.options.dark')]" :help="__('themes.mode_help')" />
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (['bg_color', 'text_color', 'muted_color', 'button_bg', 'button_text'] as $field)
                    @include('dashboard.pages.partials.theme-field', ['field' => $field])
                @endforeach
            </div>
        </section>

        @foreach ($basicGroups as $group => $fields)
            <details class="card" @if ($loop->first) open @endif>
                <summary class="cursor-pointer px-5 py-4 text-sm font-semibold">{{ __('themes.groups.'.$group) }}</summary>
                <div class="grid gap-4 border-t border-zinc-100 p-5 sm:grid-cols-2">
                    @foreach ($fields as $field)
                        @include('dashboard.pages.partials.theme-field', ['field' => $field])
                    @endforeach
                    @if ($group === 'social')
                        <p class="help sm:col-span-2">{{ __('themes.icon_color_help') }}</p>
                    @endif
                </div>
            </details>
        @endforeach

        @if ($can['advanced_customization'])
            @foreach ($fieldGroups as $group => $fields)
                <details class="card">
                    <summary class="cursor-pointer px-5 py-4 text-sm font-semibold">{{ __('themes.groups.'.$group) }} <span class="badge badge-yellow ml-1">Pro</span></summary>
                    <div class="grid gap-4 border-t border-zinc-100 p-5 sm:grid-cols-2">
                        @foreach ($fields as $field)
                            @include('dashboard.pages.partials.theme-field', ['field' => $field])
                        @endforeach
                    </div>
                </details>
            @endforeach
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-5 text-sm text-zinc-600">
                <p class="font-semibold text-zinc-900">{{ __('themes.advanced_locked_title') }}</p>
                <p class="mt-1">{{ __('themes.advanced_locked_text') }}</p>
                <a href="{{ route('plan.show') }}" class="btn btn-primary btn-sm mt-3">{{ __('plans.see_plans') }}</a>
            </div>
        @endif

        <div class="flex justify-end"><button class="btn btn-dark"><x-icon name="save" />{{ __('themes.save') }}</button></div>
    </form>
</div>
