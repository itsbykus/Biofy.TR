@extends('layouts.site')

@section('title', __('site.features.title'))
@section('description', __('site.features.description'))

@section('content')
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <h1 class="text-4xl font-black tracking-tight">{{ __('site.features.title') }}</h1>
        <p class="mt-3 max-w-2xl text-lg text-zinc-600">{{ __('site.features.description') }}</p>

        <div class="mt-12 grid gap-10 md:grid-cols-2">
            @foreach (['editor', 'themes', 'analytics', 'domains', 'privacy', 'team'] as $item)
                <div class="flex gap-4">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand text-ink"><x-icon :name="['editor' => 'pencil', 'themes' => 'palette', 'analytics' => 'chart-line', 'domains' => 'globe', 'privacy' => 'shield-check', 'team' => 'code'][$item]" class="size-5" /></span>
                    <div>
                        <h2 class="font-semibold">{{ __('site.features.items.'.$item.'.title') }}</h2>
                        <p class="mt-1 text-sm text-zinc-600">{{ __('site.features.items.'.$item.'.text') }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <h2 class="mt-16 text-2xl font-bold tracking-tight">{{ __('site.features.blocks_title') }}</h2>
        <p class="mt-2 text-zinc-600">{{ __('site.features.blocks_text') }}</p>
        <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($blockGroups as $group => $types)
                <div class="rounded-2xl border border-zinc-200 p-5">
                    <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ __('blocks.groups.'.$group) }}</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach ($types as $type)
                            <li class="flex items-center gap-2 text-sm"><x-icon :name="$type->icon()" class="text-zinc-500" />{{ $type->name() }} @if ($type->isAdvanced())<span class="badge badge-yellow">Pro</span>@endif</li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>
@endsection
