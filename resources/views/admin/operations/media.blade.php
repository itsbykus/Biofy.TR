@extends('layouts.admin')

@section('title', __('admin.nav.media'))

@section('content')
    <x-page-header :title="__('admin.nav.media')" :description="__('admin.media.help', ['size' => human_bytes($totalBytes)])" />

    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <div>
            <form method="GET" class="card mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
                <x-form.input name="user" :label="__('auth.username')" :value="request('user')" />
                <x-form.select name="purpose" :label="__('admin.media.purpose')" :value="request('purpose')" :placeholder="__('common.all')" :options="collect(array_keys(config('biofy.uploads.targets')))->mapWithKeys(fn ($p) => [$p => $p])->all()" />
                <button class="btn btn-dark">{{ __('common.filter') }}</button>
            </form>
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-5">
                @forelse ($files as $file)
                    @php($used = $inUse($file))
                    <li class="card overflow-hidden">
                        <img src="{{ $file->url() }}" alt="" loading="lazy" class="aspect-square w-full bg-zinc-100 object-cover">
                        <div class="space-y-1 p-2 text-xs">
                            <p class="truncate">{{ $file->user ? '@'.$file->user->username : __('admin.media.platform') }} · {{ $file->purpose }}</p>
                            <p class="text-zinc-500">{{ $file->width }}×{{ $file->height }} · {{ human_bytes($file->size) }}</p>
                            <div class="flex items-center justify-between">
                                <span class="badge {{ $used ? 'badge-green' : 'badge-gray' }}">{{ $used ? __('media.in_use_badge') : __('media.unused') }}</span>
                                @unless ($used)
                                    <form method="POST" action="{{ route('admin.media.destroy', $file) }}" data-confirm="{{ __('media.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost btn-sm btn-icon" aria-label="{{ __('common.delete') }}"><x-icon name="trash-2" class="size-3.5" /></button></form>
                                @endunless
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="muted col-span-full">{{ __('media.empty_title') }}</li>
                @endforelse
            </ul>
            <div class="mt-4">{{ $files->links() }}</div>
        </div>
        <aside class="card card-pad h-fit">
            <h2 class="card-title mb-3">{{ __('admin.media.per_user') }}</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($perUser as $row)
                    <li class="flex justify-between gap-2"><a href="{{ route('admin.users.show', $row->user_id) }}" class="truncate hover:underline">{{ '@'.($row->user?->username ?? '?') }}</a><span class="tabular-nums text-zinc-600">{{ human_bytes($row->bytes) }} · {{ $row->files }}</span></li>
                @endforeach
            </ul>
        </aside>
    </div>
@endsection
