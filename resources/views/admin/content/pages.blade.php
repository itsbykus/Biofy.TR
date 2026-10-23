@extends('layouts.admin')

@section('title', __('admin.nav.pages'))

@section('content')
    <x-page-header :title="__('admin.nav.pages')" :description="__('admin.pages.help')">
        <x-slot:actions><a href="{{ route('admin.pages.create') }}" class="btn btn-dark"><x-icon name="plus" />{{ __('admin.pages.create') }}</a></x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('admin.pages.title') }}</th><th>URL</th><th>{{ __('common.status_label') }}</th><th class="hidden md:table-cell">{{ __('profiles.updated') }}</th><th></th></tr></thead>
            <tbody>
                @foreach ($pages as $page)
                    @php($path = \App\Http\Controllers\Admin\ContentController::pagePath($page))
                    <tr>
                        <td class="font-medium">{{ $page->translate('title') }} @if ($page->is_system)<span class="badge badge-gray">{{ __('admin.pages.system') }}</span>@endif</td>
                        <td><a href="{{ url($path) }}" target="_blank" class="font-mono text-xs hover:underline">{{ $path }}</a></td>
                        <td>{{ $page->is_published ? __('common.status.published') : __('common.status.draft') }}</td>
                        <td class="hidden text-xs text-zinc-500 md:table-cell">{{ local_time($page->updated_at) }}</td>
                        <td class="text-right"><a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-secondary btn-sm">{{ __('common.edit') }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
