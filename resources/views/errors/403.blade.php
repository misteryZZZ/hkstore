@extends('errors::minimal')

@section('code', '403')

@section('message')
<h2>{{ __('FORBIDDEN') }}</h2>
<h3>{{ $exception->getMessage() }}</h3>
@endsection