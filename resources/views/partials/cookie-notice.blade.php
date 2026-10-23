@if ($site['cookie_notice'] ?? true)
    {{-- Only strictly-necessary cookies are used: this is an information notice, not a consent wall. --}}
    <div id="cookie-notice" class="fixed inset-x-3 bottom-3 z-40 mx-auto max-w-xl rounded-xl border border-zinc-200 bg-white p-4 shadow-xl sm:inset-x-auto sm:right-4" role="region" aria-label="{{ __('site.cookies.title') }}" hidden>
        <p class="text-sm text-zinc-700">{{ __('site.cookies.text') }} <a href="{{ route('page.cookies') }}" class="font-medium underline">{{ __('site.cookies.more') }}</a></p>
        <div class="mt-3 flex justify-end"><button type="button" class="btn btn-dark btn-sm">{{ __('site.cookies.ok') }}</button></div>
    </div>
@endif
