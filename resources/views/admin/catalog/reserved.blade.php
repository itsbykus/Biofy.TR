@extends('layouts.admin')

@section('title', __('admin.nav.reserved'))

@section('content')
    <x-page-header :title="__('admin.nav.reserved')" :description="__('admin.reserved.help')" />

    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="card card-pad">
            <h2 class="card-title">{{ __('admin.reserved.custom') }}</h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                @forelse ($words as $word)
                    <li class="badge badge-gray gap-2 py-1">
                        <span class="font-mono">{{ $word->word }}</span>
                        <form method="POST" action="{{ route('admin.reserved.destroy', $word) }}">@csrf @method('DELETE')<button class="text-zinc-500 hover:text-red-700" aria-label="{{ __('common.remove') }}"><x-icon name="x" class="size-3" /></button></form>
                    </li>
                @empty
                    <li class="muted">{{ __('admin.reserved.none') }}</li>
                @endforelse
            </ul>
            <h2 class="card-title mt-8">{{ __('admin.reserved.system') }}</h2>
            <p class="help">{{ __('admin.reserved.system_help') }}</p>
            <p class="mt-3 font-mono text-xs leading-6 text-zinc-500">{{ implode(', ', $system) }}</p>
        </section>

        <form method="POST" action="{{ route('admin.reserved.store') }}" class="card card-pad h-fit space-y-3">
            @csrf
            <x-form.textarea name="words" :label="__('admin.reserved.add')" rows="4" :help="__('admin.reserved.add_help')" required />
            <button class="btn btn-dark w-full">{{ __('common.add') }}</button>
        </form>
    </div>
@endsection
