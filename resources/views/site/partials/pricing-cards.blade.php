{{-- Pricing comes from the packages table (managed in the admin panel). There is no checkout in v1. --}}
<div @class(['grid gap-5', 'md:grid-cols-2' => $packages->count() === 2, 'md:grid-cols-3' => $packages->count() >= 3])>
    @foreach ($packages as $package)
        <article @class(['relative flex flex-col rounded-2xl border p-6', 'border-ink bg-ink text-white' => $package->is_highlighted, 'border-zinc-200 bg-white' => ! $package->is_highlighted])>
            @if ($package->is_highlighted)
                <span class="absolute -top-3 left-6 rounded-full bg-brand px-3 py-1 text-xs font-bold text-ink">{{ __('site.pricing.popular') }}</span>
            @endif
            <h3 class="text-lg font-bold">{{ $package->translate('name') }}</h3>
            @if ($package->translate('description'))
                <p @class(['mt-1 text-sm', 'text-zinc-300' => $package->is_highlighted, 'text-zinc-500' => ! $package->is_highlighted])>{{ $package->translate('description') }}</p>
            @endif

            @if ($package->is_contact_only)
                <p class="mt-6 text-xl leading-snug font-semibold">{{ $package->translate('contact_message') ?: setting_text('pricing.premium_message') }}</p>
                <div class="mt-auto pt-6">
                    <a href="{{ safe_internal_url($package->contact_url ?: '/contact?package='.$package->key) }}" @class(['btn w-full', 'btn-primary' => true])>{{ __('site.pricing.contact_us') }}</a>
                </div>
            @else
                <p class="mt-6 flex items-baseline gap-1">
                    @if ((float) $package->price_monthly === 0.0)
                        <span class="text-4xl font-black tracking-tight">{{ __('site.pricing.free') }}</span>
                    @elseif ($package->price_monthly !== null)
                        <span class="text-4xl font-black tracking-tight">{{ money($package->price_monthly, $package->currency) }}</span>
                        <span @class(['text-sm', 'text-zinc-300' => $package->is_highlighted, 'text-zinc-500' => ! $package->is_highlighted])>/ {{ __('site.pricing.month') }}</span>
                    @else
                        <span class="text-2xl font-bold">{{ __('site.pricing.on_request') }}</span>
                    @endif
                </p>
                @if ($package->price_yearly && (float) $package->price_yearly > 0)
                    <p @class(['text-xs', 'text-zinc-300' => $package->is_highlighted, 'text-zinc-500' => ! $package->is_highlighted])>{{ __('site.pricing.yearly', ['price' => money($package->price_yearly, $package->currency)]) }}</p>
                @endif
                <ul class="mt-6 space-y-2.5 text-sm">
                    @foreach ($package->translateList('marketing_features') as $line)
                        <li class="flex gap-2"><x-icon name="check" class="mt-0.5 {{ $package->is_highlighted ? 'text-brand' : 'text-emerald-600' }}" />{{ $line }}</li>
                    @endforeach
                </ul>
                <div class="mt-auto pt-8">
                    @auth
                        @if ((float) $package->price_monthly === 0.0)
                            <a href="{{ route('dashboard') }}" class="btn btn-secondary w-full">{{ __('nav.dashboard') }}</a>
                        @else
                            <a href="{{ route('contact', ['package' => $package->key]) }}" class="btn w-full {{ $package->is_highlighted ? 'btn-primary' : 'btn-dark' }}">{{ __('site.pricing.request') }}</a>
                        @endif
                    @else
                        <a href="{{ (float) $package->price_monthly === 0.0 ? route('register') : route('contact', ['package' => $package->key]) }}" class="btn w-full {{ $package->is_highlighted ? 'btn-primary' : 'btn-dark' }}">
                            {{ (float) $package->price_monthly === 0.0 ? __('auth.get_started') : __('site.pricing.request') }}
                        </a>
                    @endauth
                </div>
            @endif
        </article>
    @endforeach
</div>
<p class="mt-6 text-center text-xs text-zinc-500">{{ __('site.pricing.no_payment_note') }}</p>
