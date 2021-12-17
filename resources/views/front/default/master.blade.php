{{-- DEfAULT --}}

<!DOCTYPE html>
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

		<!-- Countdown -->
		<script type="application/javascript" src="{{ asset_('assets/jquery.countdown.min.js') }}"></script>

		<!-- Marquee -->
		<script type="application/javascript" src="{{ asset_('assets/jquery.marquee.min.js') }}"></script>
		
		<!-- Js-Cookie -->
		<script type="application/javascript" src="{{ asset_('assets/js.cookie.min.js') }}"></script>

		<style>
			{!! load_font() !!}
		</style>

    <!-- Semantic-UI -->
    <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
    <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

    <!-- Spacing CSS -->
		<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

    
		<!-- App CSS -->
		<link rel="stylesheet" href="{{ asset_('assets/front/default-'.locale_direction().'.css') }}">

		{{-- Wavesurfer --}}  
		<script src="{{ asset_('assets/wavesurfer.min.js') }}"></script>

		<!-- Search engines verification -->
		<meta name="google-site-verification" content="{{ config('app.google') }}">
		<meta name="msvalidate.01" content="{{ config('app.bing') }}">
		<meta name="yandex-verification" content="{{ config('app.yandex') }}">
        
		<script>
			'use strict';
			
			window.props = {
				appName: '{{ config('app.name') }}',
				itemId: null,
	      product: {},
	      products: {},
	      direction: '{{ locale_direction() }}',
	      routes: {
	      	checkout: '{{ route('home.checkout') }}',
	      	products: '{{ route('home.products.category', '') }}',
	      	pages: '{{ route('home.page', '') }}',
	      	payment: '{{ route('home.checkout.payment') }}',
	      	savePayment: '{{ route('home.checkout.save') }}',
	      	coupon: '{{ route('home.checkout.validate_coupon') }}',
	      	productFolder: '{{ route('home.product_folder_async') }}',
	      	notifRead: '{{ route('home.notifications.read') }}',
	      	addToCartAsyncRoute: '{{ route('home.add_to_cart_async') }}',
	      	subscriptionPayment: '{{ config('app.subscriptions.enabled') ? route('home.subscription.payment') : '' }}',
	      },
	      currentRouteName: '{{ Route::currentRouteName() }}',
	      location: window.location,
	      trasactionMsg: '{{ session('transaction_response') }}',
	      paymentProcessors: {
	      	paypal: {{ var_export(config('payments.paypal.enabled') ? true : false) }},
	      	stripe: {{ var_export(config('payments.stripe.enabled') ? true : false) }},
					skrill: {{ var_export(config('payments.skrill.enabled') ? true : false) }},
					razorpay: {{ var_export(config('payments.razorpay.enabled') ? true : false) }},
					iyzico: {{ var_export(config('payments.iyzico.enabled') ? true : false) }},
					coingate: {{ var_export(config('payments.coingate.enabled') ? true : false) }},
					midtrans: {{ var_export(config('payments.midtrans.enabled') ? true : false) }},
					paystack: {{ var_export(config('payments.paystack.enabled') ? true : false) }},
					adyen: {{ var_export(config('payments.adyen.enabled') ? true : false) }},
					instamojo: {{ var_export(config('payments.instamojo.enabled') ? true : false) }},
					coinpayments: {{ var_export(config('payments.coinpayments.enabled') ? true : false) }},
					offline: {{ var_export(config('payments.offline.enabled') ? true : false) }},
					payhere: {{ var_export(config('payments.payhere.enabled') ? true : false) }},
					spankpay: {{ var_export(config('payments.spankpay.enabled') ? true : false) }},
					omise: {{ var_export(config('payments.omise.enabled') ? true : false) }},
					paymentwall: {{ var_export(config('payments.paymentwall.enabled') ? true : false) }},
					authorize_net: {{ var_export(config('payments.authorize_net.enabled') ? true : false) }},
					sslcommerz: {{ var_export(config('payments.sslcommerz.enabled') ? true : false) }},
					flutterwave: {{ var_export(config('payments.flutterwave.enabled') ? true : false) }},
	      },
	      paymentProcessor: '{{ $payment_processor ?? null }}',
	      paymentFees: @json(config('fees')),
	      minimumPayments: @json(config('mimimum_payments')),
	      translation: @json(config('translation')),
	      currency: {
	      	code: '{{ config('payments.currency_code') }}', 
	      	symbol: '{{ config('payments.currency_symbol') }}', 
	      	position: '{{ config('payments.currency_position', 'left') }}'
	      },
	      activeScreenshot: null,
	      subcategories: {!! collect(config('categories.category_children', []))->toJson() !!},
	      categories: {!! collect(config('categories.category_parents', []))->toJson() !!},
	      pages: {!! collect(config('pages', []))->where('deletable', 1)->toJson() !!},
	      workingWithFolders: @if(isFolderProcess()) true @else false @endif,
	      removeItemConfirmMsg: '{{ __('Are you sure you want to remove this item ?') }}',
	      exchangeRate: {{ config('payments.exchange_rate', 1) }},
	      userCurrency: '{{ currency('code') }}',
	      currencies: @json(config('payments.currencies') ?? [], JSON_UNESCAPED_UNICODE),
	      currencyDecimals: {{ config('payments.currencies.'.currency('code').'.decimals') ?? 2 }},
	      usersNotif: '{{ config('app.users_notif', '') }}',
	      userNotifs: @json(config('notifications') ?? []),
	    }

	    window.isMasonry = '{{ config('app.masonry_layout') ? '1' : '0' }}' === '1';

			@if(cache('peaks', []))
			window.peaks = @json(cache('peaks', []));
			@endif
		</script>

		<style>
			@if(config('app.cookie.background'))
			#cookies {
				background: {{ config('app.cookie.background') }} !important;
			}
			@endif

			@if(config('app.cookie.color'))
			#cookies * {
				color: {{ config('app.cookie.color') }} !important;
			}
			@endif

			@if(config('app.cookie.button_bg'))
			#cookies button {
				background: {{ config('app.cookie.button_bg') }} !important;
			}
			@endif
		</style>

		@yield('additional_head_tags')
	</head>
	<body dir="{{ locale_direction() }}" vhidden>
		
		<div class="ui main fluid container {{ str_ireplace('.', '_', \Route::currentRouteName()) }}" id="app">
			<div class="ui celled grid m-0 shadowless">

				<div class="row">
					@include('front.default.partials.top_menu')
				</div>

				<div class="row">
					@yield('top-search')
				</div>
				
				<div class="row my-1" id="body">
					@yield('body')
				</div>
				
				<div id="blur" @click="toggleMobileMenu" v-if="!menu.mobile.hidden"></div>

				@if(config('app.recently_viewed_items'))
				<div id="recently-viewed-items" v-if="Object.keys(recentlyViewedItems).length > 0">
					<div class="title">
						{{ __('Recently viewed items') }}
					</div>
					<div class="items">
						<div :title="viewedItem.name" class="item" v-for="viewedItem, k in recentlyViewedItems">
							<span class="remove" @click="removeRecentViewedItem(k)"><i class="close icon mx-0"></i></span>
							<a :href="'/item/'+viewedItem.id+'/'+viewedItem.slug" class="image" :style="'background-image: url('+ viewedItem.cover +')'"></a>
						</div>
					</div>
				</div>
				@endif
				
				<footer id="footer" class="ui doubling stackable four columns grid mt-0 mx-auto px-0">
					@include('front.default.partials.footer')
				</footer>

				@if(config('app.cookie.text'))
				<div v-cloak>
					<div id="cookies" class="ui segment fluid" v-if="!cookiesAccepted">
						<div class="content">{!! config('app.cookie.text') !!}</div>
						<div class="button"><button class="ui rounded button" @click="acceptCookies" type="button">{{ __('I agree') }}</button></div>
					</div>
				</div>
				@endif
			</div>

			<div class="ui tiny modal" id="user-message">
			  <div class="content bold">
			    <p>@{{ userMessage }}</p>
			  </div>
			</div>
		</div>

		<div class="ui dimmer" id="main-dimmer">
			<div class="ui text loader">{{ __('Processing') }}</div>
		</div>

		<!-- App JS -->
	  <script type="application/javascript" src="{{ asset_('assets/front/default.js') }}"></script>
		
		@if(session('user_message'))
		<script>
			'use strict';

			$(function()
			{
				app.userMessage = "{!! session('user_message') !!}";

				Vue.nextTick(function()
  			{
  				$('#user-message').modal('show')
  			});
			})
		</script>
	  @endif

	  @if(config('chat.twak.enabled'))
	  	<!-- start twak.to JS code-->
			<script type="text/javascript">
			var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
			(function(){
			var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
			s1.async=true;
			s1.src='https://embed.tawk.to/{{ config('chat.twak.property_id') }}/default';
			s1.charset='UTF-8';
			s1.setAttribute('crossorigin','*');
			s0.parentNode.insertBefore(s1,s0);
			})();
			</script>
			<!-- start twak.to JS code-->
	  @elseif(config('chat.gist.enabled'))
		  <!-- start Gist JS code-->
			<script>
			    (function(d,h,w){var gist=w.gist=w.gist||[];gist.methods=['trackPageView','identify','track','setAppId'];gist.factory=function(t){return function(){var e=Array.prototype.slice.call(arguments);e.unshift(t);gist.push(e);return gist;}};for(var i=0;i<gist.methods.length;i++){var c=gist.methods[i];gist[c]=gist.factory(c)}s=d.createElement('script'),s.src="https://widget.getgist.com",s.async=!0,e=d.getElementsByTagName(h)[0],e.appendChild(s),s.addEventListener('load',function(e){},!1),gist.setAppId("{{ config('chat.gist.workspace_id') }}"),gist.trackPageView()})(document,'head',window);
			</script>
			<!-- end Gist JS code-->
		@elseif(config('chat.other.enabled'))
			{!! config('chat.other.code') !!}
	  @endif

	  @yield('post_js')
	</body>
</html>