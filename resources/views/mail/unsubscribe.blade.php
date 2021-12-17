<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="UTF-8">
	<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex,nofollow">
	<link rel="icon" href="{{ asset_("storage/images/".config('app.favicon'))}}">
	<title>{{ __('Unsubscribe from our newsletter') }}</title>

	@if(session('unsubscribed'))
	<meta http-equiv="refresh" content="3;url={{ route('home') }}">
	@endif

	<!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
	
	<!-- jQuery -->  
	<script type="application/javascript" src="{{ asset_('assets/jquery/jquery-3.5.1.min.js') }}"></script>
	
	<style>
		{!! load_font() !!}
	</style>

  <!-- Semantic-UI -->
  <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
  <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

  <!-- Spacing CSS -->
	<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

  
	<!-- App CSS -->
	<link rel="stylesheet" href="{{ asset_('assets/front/default-'.locale_direction().'.css?v='.config('app.version')) }}">

	<!-- Search engines verification -->
	<meta name="google-site-verification" content="{{ config('app.google') }}">
	<meta name="msvalidate.01" content="{{ config('app.bing') }}">
	<meta name="yandex-verification" content="{{ config('app.yandex') }}">

  <style>
    body, html {
      height: 100vh !important;
    }
    
    .main.container {
      height: 100%;
      display: contents;
      padding-top: 0 !important;
    }

    .grid {
      min-height: 100%;
    }

    .form.column {
      width: 400px !important;
    }
  </style>
</head>

<body dir="{{ locale_direction() }}">
  <div class="ui main fluid container pt-0" id="app">
    <div class="ui one column celled middle aligned grid m-0 shadowless newsletter-unsubscribe" id="auth">
      <div class="form column mx-auto">
        <div class="ui fluid card">

        	@if(!session('unsubscribed'))
          <div class="content center aligned logo">
            <a href="/">{{ config('app.name') }}</a>
          </div>

          <div class="content center aligned title">
            <h2>{{ __('Unsubscribe from our newsletter') }}</h2>
          </div>
          @endif

         	<div class="content">
					  @if(session('unsubscribed'))
					  <div class="ui small positive message">
					    <div>{{ __('You have been unsubscribed from our newsletter.') }}</div>
					  </div>

					  <div class="ui small message">
					  	<div>{{ __('You will be redirected to the homepage in 3 seconds.') }}</div>
					  </div>
					 	
					 	@else

				   <form class="ui large form" method="post" action="{{ route('home.unsubscribe_from_newsletter') }}">
				   	@csrf
				   	<div class="field">
				   		<label>{{ __('Email address') }}</label>
				   		<input type="email" name="newsletter_email" value="{{ old('newsletter_email') }}" required>
				   	</div>
				   	<div class="field right aligned">
				   		<button class="ui yellow circular button">{{ __('Submit') }}</button>
				   	</div>
				   </form>

				    @endif
					</div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>