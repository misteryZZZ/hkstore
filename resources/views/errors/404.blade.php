@extends('errors::minimal')

@section('code', '404')

@section('message')
<h2>{{ __('PAGE NOT FOUND') }}</h2>
<h3>{{ __("We can't seem to find the page you are looking for.") }}</h3>
@endsection