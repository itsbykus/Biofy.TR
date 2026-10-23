@extends('errors.layout')
@section('title', '404')
@section('code', '404')
@section('heading', __('errors.404_title'))
@section('message', __('errors.404_text'))
@section('actions')<a href="{{ url('/') }}" class="btn btn-dark">{{ __('errors.home') }}</a>@endsection
