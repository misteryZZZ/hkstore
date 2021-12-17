@extends('errors::minimal')

@section('code', '429')

@section('message')
<h2>{{ __('TOO MANY REQUESTS') }}</h2>
<h3>{{ __("Please try again later.") }}</h3>
@endsection
