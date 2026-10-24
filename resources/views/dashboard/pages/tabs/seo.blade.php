<form method="POST" action="{{ route('pages.seo', $profile) }}" data-editor-form class="space-y-6" novalidate>
    @csrf @method('PUT')
    <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">

    <section class="card card-pad space-y-4">
        <h2 class="card-title">{{ __('editor.seo.basic') }}</h2>
        <x-form.input name="seo_title" :label="__('editor.seo.title')" :value="$profile->seo_title" maxlength="120" :placeholder="$profile->display_name.' | '.setting('site.name')" :help="__('editor.seo.title_help')" />
        <x-form.textarea name="seo_description" :label="__('editor.seo.description')" :value="$profile->seo_description" rows="3" maxlength="300" :help="__('editor.seo.description_help')" />
    </section>

    <section class="card card-pad space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="card-title">{{ __('editor.seo.advanced') }}</h2>
            @unless ($can['seo_advanced'])<span class="badge badge-yellow">Pro</span>@endunless
        </div>
        @if ($can['seo_advanced'])
            <x-form.media name="seo_image_media_id" :label="__('editor.seo.image')" :value="$profile->seo_image_media_id" purpose="seo" :library="$media" :help="__('editor.seo.image_help')" />
            <x-form.checkbox name="seo_noindex" :label="__('editor.seo.noindex')" :checked="$profile->seo_noindex" :help="__('editor.seo.noindex_help')" />
        @else
            <p class="text-sm text-zinc-500">{{ __('editor.seo.advanced_locked') }} <a href="{{ route('plan.show') }}" class="font-medium underline">{{ __('plans.see_plans') }}</a></p>
        @endif
    </section>

    <div class="flex justify-end"><button class="btn btn-dark"><x-icon name="save" />{{ __('common.save') }}</button></div>
</form>

{{-- Measurement tags: saved on their own (a page setting, not draft content). --}}
<section class="card card-pad mt-6 space-y-4" id="tracking">
    <div class="flex items-center justify-between">
        <h2 class="card-title">{{ __('tracking.title') }}</h2>
        @unless ($can['tracking'])<span class="badge badge-yellow">Pro</span>@endunless
    </div>
    <p class="text-sm text-zinc-600">{{ __('tracking.page_help') }}</p>
    @if ($can['tracking'])
        <form method="POST" action="{{ route('pages.tracking', $profile) }}" data-editor-form class="space-y-4" novalidate>
            @csrf @method('PUT')
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (\App\Support\Tracking::FIELDS as $field => [, , $example])
                    <x-form.input :name="'tracking['.$field.']'" :label="__('tracking.fields.'.$field)" :value="$profile->tracking[$field] ?? ''" :placeholder="$example" maxlength="40" autocapitalize="characters" spellcheck="false" />
                @endforeach
            </div>
            <p class="help">{{ __('tracking.page_consent_help') }}</p>
            <div class="flex justify-end"><button class="btn btn-dark"><x-icon name="save" />{{ __('common.save') }}</button></div>
        </form>
    @else
        <p class="text-sm text-zinc-500">{{ __('tracking.locked') }} <a href="{{ route('plan.show') }}" class="font-medium underline">{{ __('plans.see_plans') }}</a></p>
    @endif
</section>
