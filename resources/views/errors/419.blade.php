@extends('errors.layout')
@section('title', '419')
@section('code', '419')
@section('heading', __('errors.419_title'))
@section('message', __('errors.session_expired'))
@section('actions')<a href="{{ url()->previous() }}" class="btn btn-dark">{{ __('errors.try_again') }}</a>@endsection
