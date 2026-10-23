@extends('errors.layout')
@section('title', '403')
@section('code', '403')
@section('heading', __('errors.403_title'))
@section('message', __('errors.403_text'))
@section('actions')<a href="{{ url('/') }}" class="btn btn-dark">{{ __('errors.home') }}</a>@endsection
