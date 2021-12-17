@extends('errors::minimal')

@section('code', '401')

@section('message')
<h2>{{ __('AUTHORIZATION REQUIRED') }}</h2>
<h3>{{ __('Sorry, your request could not be processed.') }}</h3>
@endsection
