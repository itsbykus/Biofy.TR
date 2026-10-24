@extends('layouts.admin')

@section('title', __('admin.nav.blog'))

@section('content')
    <x-page-header :title="__('admin.nav.blog')" :description="__('admin.blog.help')">
        <x-slot:actions>
            <a href="{{ route('blog.index') }}" target="_blank" class="btn btn-secondary"><x-icon name="external-link" />{{ __('admin.blog.view_blog') }}</a>
            <a href="{{ route('admin.blog.create') }}" class="btn btn-dark"><x-icon name="plus" />{{ __('admin.blog.create') }}</a>
        </x-slot:actions>
    </x-page-header>

    @if ($posts->isEmpty())
        <x-empty icon="file-text" :title="__('admin.blog.empty')" :text="__('admin.blog.empty_text')">
            <a href="{{ route('admin.blog.create') }}" class="btn btn-dark"><x-icon name="plus" />{{ __('admin.blog.create') }}</a>
        </x-empty>
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>{{ __('admin.blog.title') }}</th><th>{{ __('common.status_label') }}</th><th class="hidden md:table-cell">{{ __('admin.blog.published_at') }}</th><th class="hidden lg:table-cell">{{ __('admin.blog.author') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($posts as $post)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $post->translate('title') }}</p>
                                <p class="font-mono text-xs text-zinc-500">/blog/{{ $post->slug }}</p>
                            </td>
                            <td>
                                @if ($post->isVisible())
                                    <span class="badge badge-green">{{ __('common.status.published') }}</span>
                                @elseif ($post->is_published)
                                    <span class="badge badge-blue">{{ __('admin.blog.scheduled') }}</span>
                                @else
                                    <span class="badge badge-gray">{{ __('common.status.draft') }}</span>
                                @endif
                            </td>
                            <td class="hidden text-xs text-zinc-500 md:table-cell">{{ local_time($post->published_at) }}</td>
                            <td class="hidden text-xs text-zinc-500 lg:table-cell">{{ $post->author?->name ?? '—' }}</td>
                            <td class="text-right whitespace-nowrap">
                                @if ($post->isVisible())
                                    <a href="{{ $post->url() }}" target="_blank" class="btn btn-ghost btn-sm">{{ __('common.view') }}</a>
                                @endif
                                <a href="{{ route('admin.blog.edit', $post) }}" class="btn btn-secondary btn-sm">{{ __('common.edit') }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $posts->links() }}</div>
    @endif
@endsection
