@extends('layouts.admin')

@section('title', __('admin.nav.templates'))

@section('content')
    <x-page-header :title="__('admin.nav.templates')" :description="__('admin.templates.help')" />

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('admin.templates.template') }}</th><th>Türkçe</th><th>English</th></tr></thead>
            <tbody>
                @foreach ($keys as $key)
                    <tr>
                        <td class="font-medium">{{ __('admin.templates.names.'.$key) }}</td>
                        @foreach (['tr', 'en'] as $locale)
                            @php($row = ($templates[$key] ?? collect())->firstWhere('locale', $locale))
                            <td><a href="{{ route('admin.templates.edit', [$key, $locale]) }}" class="btn btn-secondary btn-sm">{{ __('common.edit') }}</a> @if ($row?->is_customized)<span class="badge badge-blue">{{ __('admin.templates.customized') }}</span>@endif</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
