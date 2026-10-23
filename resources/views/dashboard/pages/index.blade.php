@extends('layouts.app')

@section('title', __('nav.pages'))

@section('content')
    <x-page-header :title="__('nav.pages')" :description="__('profiles.index_help', ['limit' => limit_label($limit)])">
        <x-slot:actions>
            @if ($canCreate)
                <a href="{{ route('pages.create') }}" class="btn btn-primary"><x-icon name="plus" />{{ __('nav.new_page') }}</a>
            @else
                <a href="{{ route('plan.show') }}" class="btn btn-secondary">{{ __('profiles.limit_reached_short') }}</a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($profiles->isEmpty())
        <x-empty icon="layers" :title="__('dashboard.no_pages_title')" :text="__('dashboard.no_pages_text')">
            <a href="{{ route('pages.create') }}" class="btn btn-primary">{{ __('nav.new_page') }}</a>
        </x-empty>
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>{{ __('profiles.page') }}</th><th>{{ __('common.status_label') }}</th><th class="hidden md:table-cell">{{ __('profiles.blocks') }}</th><th class="hidden md:table-cell">{{ __('profiles.updated') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($profiles as $profile)
                        <tr>
                            <td>
                                <p class="font-semibold">{{ $profile->display_name }}</p>
                                <a href="{{ $profile->publicUrl() }}" target="_blank" rel="noopener" class="text-xs text-zinc-500 hover:underline">/{{ $profile->slug }}</a>
                            </td>
                            <td><x-status-badge :status="$profile->status" /></td>
                            <td class="hidden tabular-nums md:table-cell">{{ $profile->blocks_count }}</td>
                            <td class="hidden text-zinc-500 md:table-cell">{{ local_time($profile->updated_at) }}</td>
                            <td class="text-right">
                                @if ($profile->status === 'archived')
                                    <form method="POST" action="{{ route('pages.restore', $profile) }}">@csrf<button class="btn btn-secondary btn-sm">{{ __('profiles.restore') }}</button></form>
                                @else
                                    <a href="{{ route('pages.edit', $profile) }}" class="btn btn-dark btn-sm">{{ __('common.edit') }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
