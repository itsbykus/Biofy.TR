<div class="space-y-6">
    <section class="card card-pad space-y-4">
        <h2 class="card-title">{{ __('editor.settings.address') }}</h2>
        <form method="POST" action="{{ route('pages.slug', $profile) }}" class="space-y-3" novalidate>
            @csrf @method('PUT')
            <label for="f-slug" class="label">{{ __('editor.settings.slug') }}</label>
            <div class="flex rounded-lg focus-within:ring-2 focus-within:ring-brand">
                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-zinc-300 bg-zinc-50 px-3 text-sm text-zinc-500">{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}/</span>
                <input id="f-slug" name="slug" value="{{ old('slug', $profile->slug) }}" class="input rounded-l-none focus:ring-0" autocapitalize="none" spellcheck="false" minlength="3" maxlength="30" required @error('slug') aria-invalid="true" @enderror>
            </div>
            @error('slug')<p class="error">{{ $message }}</p>@enderror
            @if ($can['slug_redirects'])
                <x-form.checkbox name="keep_redirect" :label="__('editor.settings.keep_redirect')" :checked="true" :help="__('editor.settings.keep_redirect_help', ['days' => setting('profiles.slug_redirect_days')])" />
            @else
                <p class="help">{{ __('editor.settings.redirect_locked') }}</p>
            @endif
            <button class="btn btn-dark" data-confirm="{{ __('editor.settings.slug_confirm') }}">{{ __('editor.settings.change_address') }}</button>
        </form>
    </section>

    <section class="card card-pad">
        <h2 class="card-title">{{ __('editor.settings.qr') }}</h2>
        <div class="mt-4 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <img src="{{ route('pages.qr', ['profile' => $profile, 'format' => 'svg']) }}" alt="{{ __('editor.settings.qr_alt') }}" class="size-36 rounded-lg border border-zinc-200 bg-white p-2">
            <div class="space-y-2">
                <p class="text-sm text-zinc-600">{{ __('editor.settings.qr_help') }}</p>
                <p class="code">{{ $profile->canonicalUrl() }}</p>
                <div class="flex gap-2">
                    <a href="{{ route('pages.qr', ['profile' => $profile, 'format' => 'png']) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-3.5" />PNG</a>
                    <a href="{{ route('pages.qr', ['profile' => $profile, 'format' => 'svg', 'download' => 1]) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-3.5" />SVG</a>
                </div>
            </div>
        </div>
    </section>

    <section class="card card-pad">
        <h2 class="card-title">{{ __('editor.settings.versions') }}</h2>
        <p class="muted mt-1">{{ __('editor.settings.versions_help') }}</p>
        @if ($profile->versions->isEmpty())
            <p class="mt-3 text-sm text-zinc-500">{{ __('editor.settings.no_versions') }}</p>
        @else
            <ul class="mt-3 divide-y divide-zinc-100 text-sm">
                @foreach ($profile->versions as $version)
                    <li class="flex items-center justify-between py-2">
                        <span>#{{ $version->version_no }} · {{ local_time($version->created_at) }}</span>
                        @if ($version->id === $profile->published_version_id)<span class="badge badge-green">{{ __('editor.settings.live') }}</span>@endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card card-pad border-red-200">
        <h2 class="card-title text-red-800">{{ __('editor.settings.danger') }}</h2>
        <div class="mt-4 space-y-5">
            @if ($profile->status !== 'archived')
                <form method="POST" action="{{ route('pages.archive', $profile) }}" data-confirm="{{ __('editor.settings.archive_confirm') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    @csrf
                    <p class="text-sm text-zinc-600">{{ __('editor.settings.archive_help') }}</p>
                    <button class="btn btn-secondary shrink-0"><x-icon name="archive" />{{ __('editor.settings.archive') }}</button>
                </form>
            @endif
            <form method="POST" action="{{ route('pages.destroy', $profile) }}" class="space-y-2" data-confirm="{{ __('editor.settings.delete_confirm') }}">
                @csrf @method('DELETE')
                <p class="text-sm text-zinc-600">{!! __('editor.settings.delete_help', ['slug' => '<strong>'.e($profile->slug).'</strong>']) !!}</p>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <input name="confirm_slug" class="input sm:max-w-xs" placeholder="{{ $profile->slug }}" aria-label="{{ __('editor.settings.type_slug') }}" autocomplete="off">
                    <button class="btn btn-danger shrink-0"><x-icon name="trash-2" />{{ __('editor.settings.delete') }}</button>
                </div>
                @error('confirm_slug')<p class="error">{{ $message }}</p>@enderror
            </form>
        </div>
    </section>
</div>
