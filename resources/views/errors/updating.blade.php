@extends('errors.layout')
@section('title', __('errors.updating_title'))
@section('code', '↻')
@section('heading', __('errors.updating_title'))
@section('message', __('errors.updating_text'))
@section('actions')<a href="{{ route('login') }}" class="btn btn-ghost">{{ __('errors.staff_login') }}</a>@endsection
