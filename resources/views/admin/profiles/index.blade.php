@extends('layouts.admin')

@section('title', __('admin.nav.profiles'))

@section('content')
    <x-page-header :title="__('admin.nav.profiles')" :description="__('admin.profiles.help')" />

    <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <x-form.input name="q" :label="__('common.search')" :value="request('q')" class="flex-1" />
        <x-form.select name="status" :label="__('common.status_label')" :value="request('status')" :placeholder="__('common.all')" :options="collect(['draft', 'published', 'suspended', 'archived'])->mapWithKeys(fn ($s) => [$s => __('common.status.'.$s)])->all()" />
        <button class="btn btn-dark">{{ __('common.filter') }}</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('profiles.page') }}</th><th>{{ __('admin.profiles.owner') }}</th><th>{{ __('common.status_label') }}</th><th>{{ __('admin.profiles.moderation') }}</th></tr></thead>
            <tbody>
                @forelse ($profiles as $profile)
                    <tr>
                        <td><a href="{{ url('/'.$profile->slug) }}" target="_blank" rel="noopener" class="font-semibold hover:underline">/{{ $profile->slug }}</a><p class="text-xs text-zinc-500">{{ $profile->display_name }}</p></td>
                        <td><a href="{{ route('admin.users.show', $profile->user) }}" class="hover:underline">{{ '@'.$profile->user->username }}</a></td>
                        <td><x-status-badge :status="$profile->status" />@if ($profile->verified_at)<span class="badge badge-blue">{{ __('profile.verified') }}</span>@endif</td>
                        <td>@include('admin.profiles.partials.status-form')</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-zinc-500">{{ __('admin.profiles.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $profiles->links() }}</div>
@endsection
