@extends('auth.master')

@section('additional_head_tags')
<title>{{ __('Register') }}</title>
@include(view_path('partials.meta_data'))

@if(captcha_is_enabled('register') && captcha_is('google'))
{!! google_captcha_js() !!}
@endif
@endsection


@section('title', __('Create an account'))

@section('content')
<div class="content">
  <form class="ui large form" method="post" action="{{ route('register') }}">
    @csrf 

    <div class="two fields">
      <div class="field  @error('firstname') error @enderror">
        <label>{{ __('First name') }}</label>
        <input type="text" name="firstname" placeholder="..." value="{{ old('firstname') }}" required autofocus>
      </div>

      <div class="field @error('lastname') error @enderror">
        <label>{{ __('Last name') }}</label>
        <input type="text" name="lastname" placeholder="..." value="{{ old('lastname') }}" required >
      </div>
    </div>
    
    <div class="two fields">
      <div class="field @error('name') error @enderror">
        <label>{{ __('Username') }}</label>
        <input type="text" name="name" placeholder="..." value="{{ old('name') }}" required>
        
        @error('name')
        <div class="ui negative message">
          {{ $message }}
        </div>
        @enderror
      </div>
      
      <div class="field @error('email') error @enderror">
        <label>{{ __('E-Mail Address') }}</label>
        <input type="email" name="email" placeholder="..." value="{{ old('email') }}" required>

        @error('email')
        <div class="ui negative message">
          {{ $message }}
        </div>
        @enderror
      </div>
    </div>
    
    <div class="field @error('password') error @enderror">
      <label>{{ __('Password') }}</label>
      <input type="password" name="password" placeholder="..." value="{{ old('password') }}" required>

      @error('password')
      <div class="ui negative message">
        {{ $message }}
      </div>
      @enderror
    </div>
    
    <div class="field">
      <label>{{ __('Confirm password') }}</label>
      <input type="password" name="password_confirmation" placeholder="..." value="{{ old('password_confirmation') }}" required>
    </div>

    @error('captcha')
      <div class="ui negative message">
        <strong>{{ $message }}</strong>
      </div>
    @enderror

    @error('g-recaptcha-response')
      <div class="ui negative message">
        <strong>{{ $message }}</strong>
      </div>
    @enderror

    @if(captcha_is_enabled('register'))
    <div class="field d-flex justify-content-center">
      {!! render_captcha() !!}

      @if(captcha_is('mewebstudio'))
        <input type="text" name="captcha" value="{{ old('captcha') }}" class="ml-1">
      @endif
    </div>
    @endif

    <div class="field mb-0">
      <button class="ui yellow fluid large circular button" type="submit">{{ __('Create an account') }}</button>
    </div>
  </form>
</div>

<div class="content center aligned">
  <p>{{ __('Have an account') }} ?</p>
  <a href="{{ route('login') }}" class="ui blue fluid large circular button">{{ __('Login') }}</a>
</div>

@endsection
