@extends('layouts.auth')

@section('title', __('auth.resume_title'))
@section('heading', __('auth.resume_title'))
@section('subheading', __('auth.resume_help'))

@push('head')
    <meta name="referrer" content="no-referrer">
@endpush

@section('content')
    {{-- Re-sends the form that was waiting for the identity check (see ConfirmIdentity). --}}
    <form method="POST" action="{{ $url }}" class="space-y-4" data-auto-submit>
        @csrf
        @if ($method !== 'POST')
            <input type="hidden" name="_method" value="{{ $method }}">
        @endif
        @foreach ($fields as [$name, $value])
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <button class="btn btn-dark w-full">{{ __('auth.resume_continue') }}</button>
    </form>
@endsection
