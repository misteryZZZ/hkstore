@extends('auth.master')

@section('additional_head_tags')
<title>{{ __('Confirm password') }}</title>
@endsection

@section('title', __('Confirm password'));

@section('content')

<div class="content">
  <p>{{ __('Please confirm your password before continuing.') }}</p>
</div>

<div class="content">
  <form class="ui large form" method="post" action="{{ route('password.confirm') }}">
    @csrf
    
    <div class="field">
      <label>{{ __('Password') }}</label>
      <input type="password" name="password" required autocomplete="current-password">

      @error('password')
      <div class="ui negative message">
        {{ $message }}
      </div>
      @enderror
    </div>

    <div class="field mb-0">
      <button class="ui yellow fluid large circular button" type="submit">{{ __('Confirm password') }}</button>
    </div>
  </form>
</div>

<div class="content center aligned">
  <p>{{ __('Back to login form') }} ?</p>
  <a href="{{ route('login') }}" class="ui blue fluid large circular button">{{ __('Login') }}</a>
</div>

@endsection
