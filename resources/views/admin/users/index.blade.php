@extends('layouts.admin')

@section('title', __('admin.nav.users'))

@section('content')
    <x-page-header :title="__('admin.nav.users')" :description="__('admin.users.help', ['n' => num($users->total())])" />

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6 lg:items-end">
        <x-form.input name="q" :label="__('common.search')" :value="request('q')" :placeholder="__('admin.users.search_placeholder')" class="lg:col-span-2" />
        <x-form.select name="status" :label="__('common.status_label')" :value="request('status')" :placeholder="__('common.all')" :options="['active' => __('common.status.active'), 'suspended' => __('common.status.suspended'), 'deletion' => __('admin.users.deletion_requested')]" />
        <x-form.select name="package" :label="__('admin.users.package')" :value="request('package')" :placeholder="__('common.all')" :options="$packages->mapWithKeys(fn ($p) => [$p->id => $p->translate('name')])->all()" />
        <x-form.select name="verified" :label="__('admin.users.email_verified')" :value="request('verified')" :placeholder="__('common.all')" :options="['yes' => __('common.yes'), 'no' => __('common.no')]" />
        <div class="flex gap-2"><button class="btn btn-dark flex-1">{{ __('common.filter') }}</button><a href="{{ route('admin.users.index') }}" class="btn btn-ghost">{{ __('common.reset') }}</a></div>
    </form>

    @php($sortLink = fn ($col) => request()->fullUrlWithQuery(['sort' => $col, 'dir' => $sort === $col && $direction === 'desc' ? 'asc' : 'desc']))
    <div class="card overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th><a href="{{ $sortLink('name') }}" class="hover:text-zinc-900">{{ __('auth.name') }}</a></th>
                    <th class="hidden md:table-cell"><a href="{{ $sortLink('email') }}" class="hover:text-zinc-900">{{ __('auth.email') }}</a></th>
                    <th>{{ __('admin.users.package') }}</th>
                    <th>{{ __('common.status_label') }}</th>
                    <th class="hidden lg:table-cell"><a href="{{ $sortLink('created_at') }}" class="hover:text-zinc-900">{{ __('admin.users.registered') }}</a></th>
                    <th class="hidden lg:table-cell"><a href="{{ $sortLink('last_login_at') }}" class="hover:text-zinc-900">{{ __('admin.users.last_login') }}</a></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="font-semibold hover:underline">{{ $user->name }}</a>
                            <p class="text-xs text-zinc-500">{{ '@'.$user->username }} · {{ trans_choice('admin.users.pages_count', $user->profiles_count, ['count' => $user->profiles_count]) }}</p>
                            @foreach ($user->roles as $role)<span class="badge badge-blue mt-1">{{ $role->name }}</span>@endforeach
                        </td>
                        <td class="hidden md:table-cell">{{ $user->email }} @unless ($user->email_verified_at)<span class="badge badge-yellow">{{ __('admin.users.unverified') }}</span>@endunless</td>
                        <td>{{ $user->packageAssignments->first()?->package?->translate('name') ?? __('admin.users.default_package') }}</td>
                        <td>
                            <x-status-badge :status="$user->status" />
                            @if ($user->deletion_requested_at)<span class="badge badge-red">{{ __('admin.users.deletion_requested') }}</span>@endif
                        </td>
                        <td class="hidden text-xs text-zinc-500 lg:table-cell">{{ local_time($user->created_at, 'd.m.Y') }}</td>
                        <td class="hidden text-xs text-zinc-500 lg:table-cell">{{ $user->last_login_at ? local_time($user->last_login_at) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-zinc-500">{{ __('admin.users.none') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
