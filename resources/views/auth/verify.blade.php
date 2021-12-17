@extends('auth.master')

@section('additional_head_tags')
<title>{{ __('Verify email address') }}</title>
@endsection

@section('title', __('Verify Your Email Address'))

@section('content')
<div class="content">
  @if(session('resent'))
  <div class="ui positive message">
    {{ __('A fresh verification link has been sent to your email address.') }}
  </div>
  @endif

  {{ __('Before proceeding, please check your email for a verification link.') }}
  {{ __('If you did not receive the email') }}, <strong><a onclick="document.getElementById('resend-verification-link').submit()">{{ __('click here to request another') }}</a><strong>
    <form id="resend-verification-link" method="POST" action="{{ route('verification.resend') }}" class="d-none">@csrf</form>
</div>
@endsection