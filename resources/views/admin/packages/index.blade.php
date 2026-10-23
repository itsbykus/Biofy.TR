@extends('layouts.admin')

@section('title', __('admin.nav.packages'))

@section('content')
    <x-page-header :title="__('admin.nav.packages')" :description="__('admin.packages.help')">
        <x-slot:actions><a href="{{ route('admin.packages.create') }}" class="btn btn-dark"><x-icon name="plus" />{{ __('admin.packages.create') }}</a></x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('admin.packages.name') }}</th><th>{{ __('admin.packages.price') }}</th><th>{{ __('admin.packages.visibility') }}</th><th class="text-right">{{ __('admin.packages.active_users') }}</th><th></th></tr></thead>
            <tbody>
                @foreach ($packages as $package)
                    <tr @class(['opacity-60' => $package->is_archived])>
                        <td>
                            <p class="font-semibold">{{ $package->translate('name') }} <span class="font-mono text-xs font-normal text-zinc-500">{{ $package->key }}</span></p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @if ($package->is_default)<span class="badge badge-green">{{ __('admin.packages.default') }}</span>@endif
                                @if ($package->is_highlighted)<span class="badge badge-yellow">{{ __('site.pricing.popular') }}</span>@endif
                                @if ($package->is_contact_only)<span class="badge badge-blue">{{ __('admin.packages.contact_only') }}</span>@endif
                                @if ($package->customFor)<span class="badge badge-gray">{{ __('admin.packages.custom_for', ['user' => '@'.$package->customFor->username]) }}</span>@endif
                                @if ($package->is_archived)<span class="badge badge-gray">{{ __('common.status.archived') }}</span>@endif
                            </div>
                        </td>
                        <td class="text-sm">{{ $package->is_contact_only ? '—' : ($package->price_monthly !== null ? money($package->price_monthly, $package->currency).' / '.__('site.pricing.month') : '—') }}</td>
                        <td>{{ $package->is_public ? __('admin.packages.public') : __('admin.packages.hidden') }}</td>
                        <td class="text-right tabular-nums">{{ num($package->active_assignments_count) }}</td>
                        <td class="text-right"><a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-secondary btn-sm">{{ __('common.edit') }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="mt-3 text-xs text-zinc-500">{{ __('admin.packages.no_billing') }}</p>
@endsection
