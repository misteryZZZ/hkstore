@extends('auth.master')

@section('additional_head_tags')
<title>{{ __('Reset Password') }}</title>
@endsection

@section('title', __('Reset Password'));

@section('content')
<div class="content">
  @if (session('status'))
    <div class="ui positive message">
        {{ session('status') }}
    </div>
   @endif

  <form class="ui large form" method="POST" action="{{ route('password.email') }}">
    @csrf
    
    <div class="field @error('email') error @enderror">
      <label>{{ __('E-Mail Address') }}</label>

      <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

      @error('email')
        <div class="ui negative message">
          <strong>{{ $message }}</strong>
        </div>
      @enderror
    </div>
    
    <div class="field">
      <button type="submit" class="ui yellow large circular fluid button">
        {{ __('Send Password Reset Link') }}
      </button>
    </div>
  </form>
 
</div>
@endsection
