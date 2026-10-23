@extends('layouts.site')

@section('title', __('site.pricing.title'))
@section('description', __('site.pricing.description'))

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6">
        <div class="text-center">
            <h1 class="text-4xl font-black tracking-tight">{{ __('site.pricing.title') }}</h1>
            <p class="mx-auto mt-3 max-w-2xl text-lg text-zinc-600">{{ __('site.pricing.description') }}</p>
        </div>
        <div class="mt-12">
            @include('site.partials.pricing-cards')
        </div>

        @php($comparable = $packages->reject->is_contact_only)
        @if ($comparable->count() > 1)
            <h2 class="mt-16 mb-4 text-xl font-bold">{{ __('site.pricing.compare') }}</h2>
            <div class="overflow-x-auto rounded-2xl border border-zinc-200">
                <table class="table">
                    <thead><tr><th>{{ __('plans.feature') }}</th>@foreach ($comparable as $package)<th class="text-center">{{ $package->translate('name') }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($features as $feature)
                            <tr>
                                <td>{{ $feature->translate('name') }}</td>
                                @foreach ($comparable as $package)
                                    @php($value = $package->featureValue($feature->key) ?? $feature->default_value)
                                    <td class="text-center">
                                        @if ($feature->isBoolean())
                                            <x-icon :name="$value === '1' ? 'check' : 'minus'" class="inline {{ $value === '1' ? 'text-emerald-600' : 'text-zinc-300' }}" :label="$value === '1' ? __('common.yes') : __('common.no')" />
                                        @else
                                            {{ limit_label((int) $value) }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
