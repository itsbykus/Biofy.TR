@extends('layouts.admin')

@section('title', $post->exists ? $post->translate('title') : __('admin.blog.create'))

@section('content')
    <a href="{{ route('admin.blog.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">← {{ __('admin.nav.blog') }}</a>
    <x-page-header class="mt-1" :title="$post->exists ? $post->translate('title') : __('admin.blog.create')">
        <x-slot:actions>
            @if ($post->exists)
                @if ($post->isVisible())
                    <a href="{{ $post->url() }}" target="_blank" class="btn btn-secondary"><x-icon name="external-link" />{{ __('common.view') }}</a>
                @endif
                <form method="POST" action="{{ route('admin.blog.destroy', $post) }}" data-confirm="{{ __('admin.blog.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost">{{ __('common.delete') }}</button></form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $post->exists ? route('admin.blog.update', $post) : route('admin.blog.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_20rem]" novalidate>
        @csrf
        @if ($post->exists) @method('PUT') @endif

        <div class="card card-pad space-y-5">
            <x-form.translatable name="title" :label="__('admin.blog.title')" :value="old('title', $post->title ?? [])" :required-tr="true" />
            <x-form.translatable name="excerpt" :label="__('admin.blog.excerpt')" :value="old('excerpt', $post->excerpt ?? [])" :textarea="true" :rows="2" :help="__('admin.blog.excerpt_help')" />
            <x-form.translatable name="body" :label="__('admin.blog.body')" :value="old('body', $post->body ?? [])" :textarea="true" :rows="20" :help="__('admin.pages.markdown_help')" />
        </div>

        <div class="space-y-6">
            <div class="card card-pad space-y-5">
                <x-form.checkbox name="is_published" :label="__('admin.blog.publish')" :checked="old('is_published', $post->is_published)" />
                <x-form.input name="published_at" type="datetime-local" :label="__('admin.blog.published_at')" :value="old('published_at', $post->published_at?->setTimezone(local_timezone())->format('Y-m-d\TH:i'))" :help="__('admin.blog.published_at_help')" />
                <x-form.input name="slug" :label="__('admin.blog.slug')" :value="old('slug', $post->slug)" :help="__('admin.blog.slug_help')" />
                <button class="btn btn-dark w-full">{{ __('common.save') }}</button>
            </div>

            <div class="card card-pad space-y-3" data-file-preview data-pending-text="{{ __('admin.settings.media_pending') }}">
                <span class="label" id="f-cover-label">{{ __('admin.blog.cover') }}</span>
                <div class="grid aspect-video place-items-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                    <img data-file-preview-img src="{{ $post->cover?->url() }}" alt="" class="size-full object-cover" @if (! $post->cover) hidden @endif>
                    <x-icon name="image" class="size-6 text-zinc-400" data-file-preview-empty :hidden="(bool) $post->cover" />
                </div>
                <label class="btn btn-secondary btn-sm">
                    <x-icon name="upload" class="size-3.5" />{{ $post->cover ? __('admin.settings.media_replace') : __('admin.settings.media_choose') }}
                    <input type="file" name="cover" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="sr-only" data-file-preview-input aria-labelledby="f-cover-label">
                </label>
                <p class="text-xs font-medium text-amber-700" data-file-preview-name hidden></p>
                @if ($post->cover)<label class="flex items-center gap-2 text-xs text-zinc-600"><input type="checkbox" name="cover_remove" value="1" class="checkbox">{{ __('common.remove') }}</label>@endif
                <p class="help">{{ __('admin.blog.cover_help') }}</p>
                @error('cover')<p class="error">{{ $message }}</p>@enderror
            </div>

            {{-- SEO: search engine title/description, social image, indexing and canonical address. --}}
            <div class="card card-pad space-y-5" data-seo-preview data-base="{{ url('/blog') }}/">
                <h2 class="card-title">{{ __('admin.blog.seo') }}</h2>
                <div class="rounded-xl border border-zinc-200 bg-white p-3" aria-hidden="true">
                    <p class="text-[11px] font-semibold tracking-wide text-zinc-400 uppercase">{{ __('admin.blog.seo_preview') }}</p>
                    <p class="mt-1 truncate text-xs text-emerald-700" data-seo-url>{{ url('/blog/'.($post->slug ?: '…')) }}</p>
                    <p class="truncate text-[15px] leading-snug font-medium text-blue-700" data-seo-title>{{ $post->exists ? $post->seoTitle() : __('admin.blog.title') }}</p>
                    <p class="line-clamp-2 text-xs text-zinc-600" data-seo-desc>{{ $post->exists ? $post->seoDescription() : '' }}</p>
                </div>
                <x-form.translatable name="seo_title" :label="__('admin.blog.seo_title')" :value="old('seo_title', $post->seo_title ?? [])" :help="__('admin.blog.seo_title_help')" />
                <x-form.translatable name="meta_description" :label="__('admin.blog.meta')" :value="old('meta_description', $post->meta_description ?? [])" :textarea="true" :rows="3" :help="__('admin.blog.meta_help')" />
                <div data-file-preview data-pending-text="{{ __('admin.settings.media_pending') }}" class="space-y-2">
                    <span class="label" id="f-seo-image-label">{{ __('admin.blog.seo_image') }}</span>
                    <div class="grid aspect-[1200/630] place-items-center overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50">
                        <img data-file-preview-img src="{{ $post->seoImage?->url() }}" alt="" class="size-full object-cover" @if (! $post->seoImage) hidden @endif>
                        <x-icon name="image" class="size-5 text-zinc-400" data-file-preview-empty :hidden="(bool) $post->seoImage" />
                    </div>
                    <label class="btn btn-secondary btn-sm"><x-icon name="upload" class="size-3.5" />{{ $post->seoImage ? __('admin.settings.media_replace') : __('admin.settings.media_choose') }}
                        <input type="file" name="seo_image" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="sr-only" data-file-preview-input aria-labelledby="f-seo-image-label"></label>
                    <p class="text-xs font-medium text-amber-700" data-file-preview-name hidden></p>
                    @if ($post->seoImage)<label class="flex items-center gap-2 text-xs text-zinc-600"><input type="checkbox" name="seo_image_remove" value="1" class="checkbox">{{ __('common.remove') }}</label>@endif
                    <p class="help">{{ __('admin.blog.seo_image_help') }}</p>
                    @error('seo_image')<p class="error">{{ $message }}</p>@enderror
                </div>
                <x-form.input name="canonical_url" type="url" :label="__('admin.blog.canonical')" :value="old('canonical_url', $post->canonical_url)" placeholder="https://" :help="__('admin.blog.canonical_help')" />
                <x-form.checkbox name="seo_noindex" :label="__('admin.blog.noindex')" :checked="old('seo_noindex', $post->seo_noindex)" :help="__('admin.blog.noindex_help')" />
            </div>
        </div>
    </form>
@endsection
