@extends('errors.layout')
@section('title', '500')
@section('code', '500')
@section('heading', __('errors.500_title'))
@section('message', __('errors.server'))
@section('actions')<a href="{{ url('/') }}" class="btn btn-dark">{{ __('errors.home') }}</a>@endsection
