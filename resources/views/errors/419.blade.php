@extends('errors::minimal')

@section('code', '419')

@section('message')
<h2>{{ __('PAGE EXPIRED') }}</h2>
<h3>{{ __("Sorry, your session has expired. Please refresh and try again.") }}</h3>
@endsection