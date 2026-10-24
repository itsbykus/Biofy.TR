@extends('layouts.admin')

@section('title', __('admin.nav.shortlinks'))

@section('content')
    <x-page-header :title="__('admin.nav.shortlinks')" :description="__('admin.shortlinks.help')" />

    <form method="GET" class="mb-4 flex max-w-md gap-2">
        <input name="q" value="{{ $search }}" class="input" placeholder="{{ __('admin.shortlinks.search') }}" aria-label="{{ __('admin.shortlinks.search') }}">
        <button class="btn btn-secondary"><x-icon name="search" /></button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('admin.shortlinks.link') }}</th><th>{{ __('admin.shortlinks.target') }}</th><th>{{ __('admin.shortlinks.owner') }}</th><th class="text-right">{{ __('shortlinks.clicks') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($links as $link)
                    <tr>
                        <td class="whitespace-nowrap">
                            <a href="{{ $link->shortUrl() }}" target="_blank" rel="noopener" class="font-mono text-xs font-semibold hover:underline">/s/{{ $link->code }}</a>
                            @unless ($link->is_active)<span class="badge badge-red">{{ __('shortlinks.disabled') }}</span>@endunless
                            <p class="text-[11px] text-zinc-500">{{ local_time($link->created_at) }}</p>
                        </td>
                        <td class="max-w-sm"><p class="truncate text-xs" title="{{ $link->target_url }}">{{ $link->target_url }}</p></td>
                        <td class="text-xs">@if ($link->user)<a href="{{ route('admin.users.show', $link->user) }}" class="hover:underline">{{ '@'.$link->user->username }}</a>@else — @endif</td>
                        <td class="text-right tabular-nums">{{ num($link->clicks) }}</td>
                        <td class="text-right whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.shortlinks.toggle', $link) }}" class="inline">@csrf<button class="btn btn-secondary btn-sm">{{ $link->is_active ? __('admin.shortlinks.disable') : __('admin.shortlinks.enable') }}</button></form>
                            <form method="POST" action="{{ route('admin.shortlinks.destroy', $link) }}" class="inline" data-confirm="{{ __('shortlinks.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm">{{ __('common.delete') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-sm text-zinc-500">{{ __('shortlinks.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $links->links() }}</div>
@endsection
