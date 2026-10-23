@extends('layouts.app')

@section('title', __('nav.links'))

@section('content')
    <x-page-header :title="__('nav.links')" :description="__('links.help')" />

    @if ($blocks->isEmpty())
        <x-empty icon="link" :title="__('links.empty_title')" :text="__('links.empty_text')">
            <a href="{{ route('pages.index') }}" class="btn btn-primary">{{ __('nav.pages') }}</a>
        </x-empty>
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>{{ __('links.link') }}</th><th class="hidden sm:table-cell">{{ __('profiles.page') }}</th><th class="text-right">{{ __('links.clicks_30d') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($blocks as $block)
                        @php($type = $registry->get($block->type))
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-icon :name="$type->icon()" class="text-zinc-500" />
                                    <span class="font-medium">{{ $type->summary($block->data ?? []) ?: $type->name() }}</span>
                                    @unless ($block->is_visible)<span class="badge badge-gray">{{ __('editor.hidden') }}</span>@endunless
                                </div>
                                @if (! empty($block->data['url']))<p class="mt-0.5 max-w-md truncate text-xs text-zinc-500">{{ $block->data['url'] }}</p>@endif
                            </td>
                            <td class="hidden text-zinc-600 sm:table-cell">/{{ $block->profile->slug }}</td>
                            <td class="text-right font-semibold tabular-nums">{{ num($clicks[$block->id] ?? 0) }}</td>
                            <td class="text-right"><a href="{{ route('pages.edit', $block->profile) }}" class="btn btn-ghost btn-sm">{{ __('common.edit') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
