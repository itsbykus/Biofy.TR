@extends('layouts.app')

@section('title', __('nav.overview'))

@section('content')
    <x-page-header :title="__('dashboard.welcome', ['name' => \Illuminate\Support\Str::before(auth()->user()->name, ' ')])" :description="__('dashboard.welcome_text')">
        <x-slot:actions>
            <a href="{{ route('pages.create') }}" class="btn btn-primary"><x-icon name="plus" />{{ __('nav.new_page') }}</a>
        </x-slot:actions>
    </x-page-header>

    @if ($overview)
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat :label="__('analytics.views')" :value="num($overview['totals']['views'])" :hint="__('dashboard.last_30_days')" icon="eye" />
            <x-stat :label="__('analytics.uniques')" :value="num($overview['totals']['uniques'])" :hint="__('analytics.uniques_hint')" icon="users" />
            <x-stat :label="__('analytics.clicks')" :value="num($overview['totals']['clicks'])" :hint="__('dashboard.last_30_days')" icon="mouse-pointer-click" />
            <x-stat :label="__('analytics.ctr')" :value="'%'.num($overview['totals']['ctr'], 1)" :hint="__('analytics.ctr_hint')" icon="activity" />
        </div>
        <div class="card card-pad mt-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="card-title">{{ __('dashboard.traffic') }}</h2>
                <a href="{{ route('analytics.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-900">{{ __('dashboard.see_analytics') }} →</a>
            </div>
            <x-line-chart :series="$overview['series']" :keys="['views', 'clicks']" />
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <section>
            <h2 class="mb-3 text-base font-semibold">{{ __('nav.pages') }}</h2>
            @forelse ($profiles as $profile)
                <div class="card mb-3 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-semibold">{{ $profile->display_name }}</p>
                            <x-status-badge :status="$profile->status" />
                            @if ($profile->has_unpublished_changes && $profile->published_version_id)<span class="badge badge-yellow">{{ __('editor.unpublished_changes') }}</span>@endif
                        </div>
                        <a href="{{ $profile->publicUrl() }}" target="_blank" rel="noopener" class="text-sm text-zinc-500 hover:text-zinc-900">{{ preg_replace('#^https?://#', '', $profile->publicUrl()) }}</a>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-ghost btn-sm" data-copy="{{ $profile->canonicalUrl() }}" data-copied="{{ __('common.copied') }}"><x-icon name="copy" class="size-3.5" />{{ __('common.copy_link') }}</button>
                        <a href="{{ route('pages.edit', $profile) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-3.5" />{{ __('common.edit') }}</a>
                    </div>
                </div>
            @empty
                <x-empty icon="layers" :title="__('dashboard.no_pages_title')" :text="__('dashboard.no_pages_text')">
                    <a href="{{ route('pages.create') }}" class="btn btn-primary">{{ __('nav.new_page') }}</a>
                </x-empty>
            @endforelse
        </section>

        <aside class="space-y-4">
            <div class="card card-pad">
                <p class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ __('dashboard.your_plan') }}</p>
                <p class="mt-1 text-lg font-bold">{{ $package?->translate('name') ?? '—' }}</p>
                <a href="{{ route('plan.show') }}" class="mt-3 inline-flex text-sm font-medium text-zinc-700 underline decoration-brand decoration-2 underline-offset-2">{{ __('dashboard.see_limits') }}</a>
            </div>
            <div class="card card-pad">
                <h3 class="card-title">{{ __('dashboard.checklist') }}</h3>
                @php($first = $profiles->first())
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ([
                        [auth()->user()->hasVerifiedEmail(), __('dashboard.check_verify')],
                        [$first && $first->avatar_media_id, __('dashboard.check_avatar')],
                        [$first && $first->blocks_count > 1, __('dashboard.check_links')],
                        [$first && $first->published_version_id, __('dashboard.check_publish')],
                        [auth()->user()->hasTwoFactorEnabled(), __('dashboard.check_2fa')],
                    ] as [$done, $label])
                        <li class="flex items-center gap-2 {{ $done ? 'text-zinc-400 line-through' : 'text-zinc-700' }}">
                            <x-icon :name="$done ? 'circle-check' : 'square'" class="{{ $done ? 'text-emerald-600' : 'text-zinc-400' }}" />{{ $label }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>
    </div>
@endsection
