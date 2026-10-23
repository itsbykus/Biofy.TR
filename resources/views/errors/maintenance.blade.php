@extends('errors.layout')
@section('title', __('errors.maintenance_title'))
@section('code', '⏳')
@section('heading', __('errors.maintenance_title'))
@section('message', $message)
@section('actions')<a href="{{ route('login') }}" class="btn btn-ghost">{{ __('errors.staff_login') }}</a>@endsection
