<form method="POST" action="{{ route('pages.details', $profile) }}" data-editor-form class="space-y-6" novalidate>
    @csrf @method('PUT')
    <input type="hidden" name="revision" value="{{ $profile->draft_revision }}">

    <section class="card card-pad space-y-4">
        <h2 class="card-title">{{ __('editor.profile.identity') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.media name="avatar_media_id" :label="__('editor.profile.avatar')" :value="$profile->avatar_media_id" purpose="avatar" :library="$media" :help="__('editor.profile.avatar_help')" />
            <x-form.media name="banner_media_id" :label="__('editor.profile.banner')" :value="$profile->banner_media_id" purpose="banner" :library="$media" :help="__('editor.profile.banner_help')" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="display_name" :label="__('editor.profile.display_name')" :value="$profile->display_name" maxlength="80" required />
            <x-form.input name="title" :label="__('editor.profile.title')" :value="$profile->title" maxlength="120" :placeholder="__('editor.profile.title_placeholder')" />
        </div>
        <x-form.textarea name="bio_short" :label="__('editor.profile.bio_short')" :value="$profile->bio_short" rows="2" maxlength="300" :help="__('editor.profile.bio_short_help')" />
        <x-form.textarea name="bio_long" :label="__('editor.profile.bio_long')" :value="$profile->bio_long" rows="4" maxlength="3000" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.select name="badge" :label="__('editor.profile.badge')" :value="$profile->badge" :placeholder="__('common.none')" :options="collect($badges)->mapWithKeys(fn ($b) => [$b => __('profile.badges.'.$b)])->all()" />
            <x-form.select name="language" :label="__('editor.profile.language')" :value="$profile->language" :options="['tr' => 'Türkçe', 'en' => 'English']" :help="__('editor.profile.language_help')" />
        </div>
    </section>

    <section class="card card-pad space-y-4">
        <h2 class="card-title">{{ __('editor.profile.contact') }}</h2>
        <p class="muted">{{ __('editor.profile.contact_help') }}</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="contact_email" type="email" :label="__('editor.profile.contact_email')" :value="$profile->contact_email" />
            <x-form.input name="contact_phone" type="tel" :label="__('editor.profile.contact_phone')" :value="$profile->contact_phone" placeholder="+90 …" />
        </div>
        <x-form.input name="location" :label="__('editor.profile.location')" :value="$profile->location" maxlength="120" />
    </section>

    <div class="flex justify-end"><button class="btn btn-dark"><x-icon name="save" />{{ __('common.save') }}</button></div>
</form>
