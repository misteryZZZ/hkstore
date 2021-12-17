<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		{!! config('app.google_analytics') !!}

		<meta charset="UTF-8">
		<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="icon" href="{{ asset_("storage/images/".config('app.favicon'))}}">
		
		@include(view_path('partials.meta_data'))

		<!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
		
		<!-- jQuery -->  
		<script type="application/javascript" src="{{ asset_('assets/jquery/jquery-3.5.1.min.js') }}"></script>

		<style>
			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Regular.ttf');
			  font-weight: 400;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Medium.ttf');
			  font-weight: 500;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-SemiBold.ttf');
			  font-weight: 600;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Bold.ttf');
			  font-weight: 700;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-ExtraBold.ttf');
			  font-weight: 800;
			  font-style: normal;
			}

			@font-face {
			  font-family: 'Valexa';
			  src: url('/assets/fonts/Poppins/Poppins-Black.ttf');
			  font-weight: 900;
			  font-style: normal;
			}	
		</style>

    <!-- Semantic-UI -->
    <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
    <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

    <!-- Spacing CSS -->
		<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

		<!-- App CSS -->
		<link rel="stylesheet" href="{{ asset_('assets/front/affiliate-'.locale_direction().'.css') }}">

		<!-- Search engines verification -->
		<meta name="google-site-verification" content="{{ config('app.google') }}">
		<meta name="msvalidate.01" content="{{ config('app.bing') }}">
		<meta name="yandex-verification" content="{{ config('app.yandex') }}">
    
	</head>

	<body dir="{{ locale_direction() }}">
		
		<div class="ui main fluid container {{ str_ireplace('.', '_', \Route::currentRouteName()) }}">
			<div class="panel" style="-webkit-mask-image: url('{{ asset('storage/images/affiliate-program.svg')  }}');">
				<div class="ui header">
					<a href="/" class="logo">
            <img class="ui image" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
					</a>

					{{ __('Affiliate Program') }}

					<div class="sub header mt-1">
						{{ __('Refer new customers to :app_name and receive :commission% of their purchases', ['app_name' => config('app.name'), 'commission' => config('affiliate.commission', 0)]) }}
					</div>
				</div>	
			</div>

			<div class="section first">
				<div class="image">
					<img src="{{ asset('storage/images/affiliate-1.png')  }}">
				</div>
				<div class="content ml-1">
					<div class="header">{{ __('Spread the word & start making money') }}</div>
					<div class="description">{{ __('Refer new customers to :app_name using your affiliate link and you will receive :commission% of any purchase. Our affiliate tracking cookie lasts :expire days. You will receive commission from all customers that sign up within :expire days after clicking on your affiliate links.', ['app_name' => config('app.name'), 'commission' => config('affiliate.commission', 0), 'expire' => config('affiliate.expire', 0)]) }}</div>
				</div>
			</div>

			<div class="section second">
				<div class="wrapper">
					<div class="content mr-1">
						<div class="header">{{ __('Creating your affiliate links') }}</div>
						<div class="description">
							<p>{{ __('To be able to use our affiliate program. You will need to link to :app_name using your affiliate name from your profile page.', ['app_name' => config('app.name')]) }}</p>
							<p>{{ __("Add ?r=AFFILIATE_NAME to any :app_name link, replace AFFILIATE_NAME with your affiliate name and that's it.", ['app_name' => config('app.name')]) }}</p>
							<div class="examples">
								<div class="title">{{ __('Examples') }}</div>
								<div><span>{{ __('Homepage') }}</span> : {{ env('APP_URL') }}<span>?r=AFFILIATE_NAME</span></div>
								<div><span>{{ __('Item') }}</span> : {{ env('APP_URL') }}/item/62/amaze-ball<span>?r=AFFILIATE_NAME</span></div>
								<div><span>{{ __('Category') }}</span> : {{ env('APP_URL') }}/items/category/graphics<span>?r=AFFILIATE_NAME</span></div>
							</div>
						</div>
					</div>
					<div class="image">
						<img src="{{ asset('storage/images/affiliate-2.png')  }}">
					</div>
				</div>
			</div>

			<div class="section third">
				<div class="image">
					<img src="{{ asset('storage/images/affiliate-3.png')  }}">
				</div>
				<div class="content ml-1">
					<div class="header">{{ __('Social Sharing') }}</div>
					<div class="description">{{ __("On every item's page there are share buttons to share an item across several social networks including Facebook, Pinterest, Twitter and more. Just click on any share button and you will receive commission on every referred sale.") }}</div>
				</div>
			</div>

			<div class="section fourth">
				<div class="wrapper">
					<div class="content mr-1">
						<div class="header">{{ __('Cash out your earnings') }}</div>
						<div class="description">
							{!! config('affiliate.cashout_description') !!}
						</div>
					</div>
					<div class="image">
						<img src="{{ asset('storage/images/affiliate-4.png')  }}">
					</div>
				</div>
			</div>

			<footer id="footer" class="ui doubling stackable four columns grid mt-0 mx-auto px-0">
				<div class="row first">
					<div class="column">
						<img class="ui image mx-auto" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
						<p class="mt-1">
							{{ config('app.description') }}
						</p>
					</div>
					<div class="column">
						<h4>{{ __('Featured Categories') }}</h4>
						<ul class="p-0">
							@foreach (config('popular_categories', []) as $p_category)
							<li><a href="{{ route('home.products.category', $p_category->slug) }}">{{ $p_category->name }}</a></li>
							@endforeach
						</ul>
					</div>
					<div class="column">
						<h4>{{ __('Additional Resources') }}</h4>
						<ul class="p-0">
							<li><a href="{{ route('home.support') }}">{{ __('Contact') }}</a></li>
							<li><a href="{{ route('home.support') }}">{{ __('FAQ') }}</a></li>
							@foreach (collect(config('pages', []))->where('deletable', 1) as $page)
							<li><a href="{{ route('home.page', $page['slug']) }}">{{ $page['name'] }}</a></li>
							@endforeach
						</ul>
					</div>
					<div class="column">
						<h4>{{ __('Newsletter') }}</h4>
						<form class="ui big  form newsletter" action="{{ route('home.newsletter', ['redirect' => url()->current()]) }}" method="post">
							@csrf
							<p>{{ __('Subscribe to our newsletter to receive news, updates, free stuff and new releases') }}.</p>

							@if(session('newsletter_subscription_msg'))
							<div class="ui fluid small message inverted p-1-hf">
								{{ session('newsletter_subscription_msg') }}
							</div>
							@endif

							<div class="ui icon input fluid">
								<input type="text" name="email" placeholder="email...">
								<i class="paper plane outline link icon"></i>
							</div>
						</form>
						<div class="social-icons mx-auto justify-content-center mt-1">
							@if(config('app.facebook'))
							<a class="ui big circular teal small icon button" href="{{ config('app.facebook') }}">
								<i class="facebook icon"></i>
							</a>
							@endif

							@if(config('app.twitter'))
							<a class="ui big circular teal small icon button" href="{{ config('app.twitter') }}">
								<i class="twitter icon"></i>
							</a>
							@endif

							@if(config('app.pinterest'))
							<a class="ui big circular teal small icon button" href="{{ config('app.pinterest') }}">
								<i class="pinterest icon"></i>
							</a>
							@endif

							@if(config('app.youtube'))
							<a class="ui big circular teal small icon button" href="{{ config('app.youtube') }}">
								<i class="youtube icon"></i>
							</a>
							@endif

							@if(config('app.tumblr'))
							<a class="ui big circular teal small icon button mr-0" href="{{ config('app.tumblr') }}">
								<i class="tumblr icon"></i>
							</a>
							@endif
						</div>
					</div>
				</div>

				<div class="row last">
					<div class="sixteen wide column">
						<div class="ui secondary stackable menu mb-0">
							@if(count(config('langs') ?? []) > 1)
					    <div class="item ui top dropdown languages">
					      <div class="text capitalize">{{ __(config('laravellocalization.supportedLocales.'.session('locale', 'en').'.name')) }}</div>
					    
					      <div class="left menu rounded-corner">
					      	<div class="header">{{ __('Languages') }}</div>
					      	<div class="wrapper">
						        @foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
						        <a class="item" data-value="{{ $locale_code }}">
						          {{ $supported_locale['native'] ?? '' }}
						        </a>
						        @endforeach
						      </div>
					      </div>
					    </div>
					    @endif

				      @foreach(collect(config('pages', []))->where('deletable', 0) as $page)
							<a href="{{ route('home.page', $page['slug']) }}" class="item">{{ __($page['name']) }}</a>
							@endforeach
							
							@if(config('app.blog.enabled'))
							<a class="item" href="{{ route('home.blog') }}">{{ __('Blog') }}</a>
							@endif
							
							<a class="item" href="{{ route('home.support') }}">{{ __('Help') }}</a>
						</div>

						<div class="ui secondary stackable menu mt-0">
							<span class="item">{{ config('app.name') }} © {{ date('Y') }} {{ __('All right reserved') }}</span>
						</div>
					</div>
				</div>

				@auth
					<form id="logout-form" action="{{ route('logout', ['redirect' => url()->full()]) }}" method="POST" class="d-none">@csrf</form>
				@endauth

				<form action="{{ route('set_locale') }}" method="post" class="d-none" id="set-locale">
					<input type="hidden" name="redirect" value="{{ url()->full() }}">
					<input type="hidden" name="locale" v-model="locale">
				</form>

				<script type="application/javascript">
					'use strict';
					
					$(function()
					{
						$('.ui.dropdown.languages').dropdown({
							action: function(text, value) 
							{
					      $('#set-locale input[name=locale]').val(value);
					      $('#set-locale').submit();
					    }
						})
					})
				</script>
			</footer>
		</div>

	</body>
</html>