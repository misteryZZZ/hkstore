@extends('auth.master')

@section('additional_head_tags')
<title>{{ __('Login') }}</title>
@include(view_path('partials.meta_data'))

@if(captcha_is_enabled() && captcha_is('google'))
{!! google_captcha_js() !!}
@endif
@endsection

@section('title', __('Login to your account'))


@section('content')
<div class="content">
  @if(array_filter(array_column(config('services'), 'enabled')))
  <div class="ui floating dropdown right labeled icon fluid large basic button">
    <div class="text">{{ __('With your social account') }}</div>
    <i class="dropdown icon"></i>
    <div class="menu">
      @if(config('services.facebook.enabled'))
      <a href="{{ secure_url('login/facebook') }}" class="item">
        <i class="facebook icon"></i>
        Facebook
      </a>
      @endif

      @if(config('services.google.enabled'))
      <a href="{{ secure_url('login/google') }}" class="item">
        <i class="google icon"></i>
        Google
      </a>
      @endif
      
      @if(config('services.github.enabled'))
      <a href="{{ secure_url('login/github') }}" class="item">
        <i class="github icon"></i>
        Github
      </a>
      @endif

      @if(config('services.twitter.enabled'))
      <a href="{{ secure_url('login/twitter') }}" class="item">
        <i class="twitter icon"></i>
        Twitter
      </a>
      @endif

      @if(config('services.linkedin.enabled'))
      <a href="{{ secure_url('login/linkedin') }}" class="item">
        <i class="linkedin icon"></i>
        Linkedin
      </a>
      @endif

      @if(config('services.vkontakte.enabled'))
      <a href="{{ secure_url('login/vkontakte') }}" class="item">
        <i class="vk icon"></i>
        Vkontakte (VK)
      </a>
      @endif
    </div>
  </div>

  <div class="ui horizontal divider">{{ __('Or') }}</div>
  @endif
  
  <form class="ui large form" action="{{ route('login', ['redirect' => request()->redirect ?? '/']) }}" method="post">
    @csrf 

    <div class="field">
      <label>Email</label>
      <input type="email" placeholder="..." name="email" value="{{ old('email', session('email')) }}" required autocomplete="email" autofocus>

      @error('email')
        <div class="ui negative message">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>

    <div class="field">
      <label>{{ __('Password') }}</label>
      <input type="password" placeholder="..." name="password" required autocomplete="current-password">

      @error('password')
        <div class="ui negative message">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>

    <div class="field">
      <div class="ui checkbox">
        <input type="checkbox" name="remember" id="remember">
        <label class="checkbox" for="remember">{{ __('Remember me') }}</label>
      </div>
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

    @if(captcha_is_enabled('login'))
    <div class="field d-flex justify-content-center">
      {!! render_captcha() !!}

      @if(captcha_is('mewebstudio'))
        <input type="text" name="captcha" value="{{ old('captcha') }}" class="ml-1">
      @endif
    </div>
    @endif

    <div class="field mb-0">
      <button class="ui yellow large fluid circular button" type="submit">{{ __('Login') }}</button>
    </div>

    <div class="field">
      <div class="ui text menu my-0">
        <a class="item right aligned" href="{{ route('password.request') }}">{{ __('Forgot password') }}</a>
      </div>
    </div>
  </form>
</div>

<div class="content center aligned">
  <p>{{ __('Don\'t have an account') }} ?</p>
  <a href="{{ route('register') }}" class="ui fluid large blue circular button">{{ __('Create an account') }}</a>
</div>

<script>
    'use strict';
    
    $(function()
    {
        $('.ui.dropdown').dropdown();
    })
</script>
@endsection
