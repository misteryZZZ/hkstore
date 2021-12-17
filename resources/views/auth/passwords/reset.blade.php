@extends('auth.master')

@section('additional_head_tags')
<title>{{ __('Reset Password') }}</title>
@endsection
@section('title', __('Reset Password'));


@section('content')

<div class="content">
  <form class="ui large form" method="POST" action="{{ route('password.update') }}">
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">
    
    <div class="field @error('email') error @enderror">
      <label>{{ __('E-Mail Address') }}</label>

      <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

      @error('email')
        <div class="ui small negative message">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>

    <div class="field @error('password') error @enderror">
      <label>{{ __('Password') }}</label>

      <input id="password" type="password" name="password" value="{{ $password ?? old('password') }}" required autocomplete="new-password" autofocus>

      @error('password')
        <div class="ui negative message">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>
    
    <div class="field">
      <label>{{ __('Confirm password') }}</label>
      <input type="password" name="password_confirmation" required autocomplete="new-password">
    </div>

    <div class="field">
      <button class="ui yellow circular large button fluid">{{ __('Reset Password') }}</button>
    </div>
  </form>
</div>

@endsection
