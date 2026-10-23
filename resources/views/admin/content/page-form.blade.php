@extends('layouts.admin')

@section('title', $page->exists ? $page->translate('title') : __('admin.pages.create'))

@section('content')
    <a href="{{ route('admin.pages.index') }}" class="text-xs font-medium text-zinc-500 hover:text-zinc-900">← {{ __('admin.nav.pages') }}</a>
    <x-page-header class="mt-1" :title="$page->exists ? $page->translate('title') : __('admin.pages.create')">
        <x-slot:actions>
            @if ($page->exists && ! $page->is_system)
                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="{{ __('admin.pages.delete_confirm') }}">@csrf @method('DELETE')<button class="btn btn-danger-ghost">{{ __('common.delete') }}</button></form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" class="card card-pad space-y-5" novalidate>
        @csrf
        @if ($page->exists) @method('PUT') @endif
        @unless ($page->is_system)
            <x-form.input name="slug" :label="__('admin.pages.slug')" :value="$page->slug" required :help="__('admin.pages.slug_help')" />
        @endunless
        <x-form.translatable name="title" :label="__('admin.pages.title')" :value="$page->title ?? []" :required-tr="true" />
        <x-form.translatable name="meta_description" :label="__('admin.pages.meta')" :value="$page->meta_description ?? []" />
        <x-form.translatable name="body" :label="__('admin.pages.body')" :value="$page->body ?? []" :textarea="true" :rows="18" :help="__('admin.pages.markdown_help')" />
        <x-form.checkbox name="is_published" :label="__('admin.pages.published')" :checked="$page->is_published" />
        <div class="flex justify-end"><button class="btn btn-dark">{{ __('common.save') }}</button></div>
    </form>
@endsection
