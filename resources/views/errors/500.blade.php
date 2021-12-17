@extends('errors::minimal')

@section('code', '500')

@section('message')
<h2>{{ __('INTERNAL SERVER ERROR') }}</h2>
<h3>{!! __('The server encountered an internal error or a misconfiguration <br> and was unable to complete your request.') !!}</h3>
@endsection