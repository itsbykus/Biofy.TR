{{--
    Consent-gated measurement tags. $ids: validated IDs (App\Support\Tracking::valid), $scope: where
    the consent applies ("site" or "p<profile id>"), $variant: "site" (Tailwind) or "profile" (bio page CSS).
    Only the IDs are printed; resources/js/tracking.js loads the providers after "Accept".
--}}
@php
    app(\App\Support\TrackingContext::class)->allow(\App\Support\Tracking::providers($ids));
    $providers = \App\Support\Tracking::providerList($ids);
    $message = $variant === 'profile'
        ? __('tracking.consent_profile', ['providers' => $providers])
        : __('tracking.consent_site', ['site' => $site['name'], 'providers' => $providers]);
@endphp
<script type="application/json" id="biofy-tracking">{!! json_encode(['scope' => $scope, 'ids' => $ids, 'clicks' => $variant === 'profile'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!}</script>
@if ($variant === 'profile')
    <div id="biofy-consent" class="bp-consent" role="dialog" aria-live="polite" aria-label="{{ __('tracking.consent_title') }}" hidden>
        <p>{{ $message }} <a href="{{ rtrim(config('app.url'), '/') }}/cookies" target="_blank" rel="noopener">{{ __('site.cookies.more') }}</a></p>
        <div class="bp-consent-actions">
            <button type="button" data-consent="denied">{{ __('tracking.reject') }}</button>
            <button type="button" data-consent="granted">{{ __('tracking.accept') }}</button>
        </div>
    </div>
@else
    <div id="biofy-consent" class="fixed inset-x-3 bottom-3 z-40 mx-auto max-w-xl rounded-xl border border-zinc-200 bg-white p-4 shadow-xl sm:inset-x-auto sm:right-4" role="dialog" aria-live="polite" aria-label="{{ __('tracking.consent_title') }}" hidden>
        <p class="text-sm font-semibold text-zinc-900">{{ __('tracking.consent_title') }}</p>
        <p class="mt-1 text-sm text-zinc-700">{{ $message }} <a href="{{ route('page.cookies') }}" class="font-medium underline">{{ __('site.cookies.more') }}</a></p>
        {{-- Both choices are equally easy: no pre-ticked or hidden "reject". --}}
        <div class="mt-3 flex justify-end gap-2">
            <button type="button" class="btn btn-secondary btn-sm" data-consent="denied">{{ __('tracking.reject') }}</button>
            <button type="button" class="btn btn-dark btn-sm" data-consent="granted">{{ __('tracking.accept') }}</button>
        </div>
    </div>
@endif
