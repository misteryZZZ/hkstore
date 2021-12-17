@extends('errors::minimal')

@section('code', '503')

@section('message')
<h2>{{ __('SERVICE UNAVAILABLE') }}</h2>
<h3>{{ __($exception->getMessage() ?? 'The server is temporarily busy, please try again later.') }}</h3>
@endsection