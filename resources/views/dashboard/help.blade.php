@extends('layouts.app')

@section('title', __('nav.help'))

@section('content')
    <x-page-header :title="__('nav.help')" :description="__('help.intro')" />

    <div class="grid gap-4 md:grid-cols-2">
        @foreach (['publish', 'blocks', 'themes', 'domain', 'analytics', 'security'] as $topic)
            <details class="card card-pad" @if ($loop->first) open @endif>
                <summary class="cursor-pointer font-semibold">{{ __('help.topics.'.$topic.'.q') }}</summary>
                <p class="mt-2 text-sm whitespace-pre-line text-zinc-600">{{ __('help.topics.'.$topic.'.a') }}</p>
            </details>
        @endforeach
    </div>

    <div class="card card-pad mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-semibold">{{ __('help.more_title') }}</p>
            <p class="muted">{{ __('help.more_text') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('page.help') }}" class="btn btn-secondary">{{ __('help.faq') }}</a>
            <a href="{{ route('contact') }}" class="btn btn-dark">{{ __('help.contact') }}</a>
        </div>
    </div>
@endsection
