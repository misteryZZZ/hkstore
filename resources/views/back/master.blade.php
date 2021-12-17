<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="UTF-8">
		<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="icon" href="{{ asset_("storage/images/".config('app.favicon')) }}">
		
		<title>@yield('title')</title>

		<style>
			@if(locale_direction() === 'ltr')
			@font-face {
		    font-family: 'Valexa';
		    src: url("/assets/fonts/Glegoo/Glegoo-Regular.ttf");
		    font-weight: 400;
		    font-style: normal;
			}

			@font-face {
		    font-family: 'Valexa';
		    src: url("/assets/fonts/Glegoo/Glegoo-Bold.ttf");
		    font-weight: 700;
		    font-style: normal;
			}
			@else
			@font-face {
		    font-family: 'Valexa';
		    src: url("/assets/fonts/Almarai/Almarai-Regular.ttf");
		    font-weight: 400;
		    font-style: normal;
			}

			@font-face {
		    font-family: 'Valexa';
		    src: url("/assets/fonts/Almarai/Almarai-Bold.ttf");
		    font-weight: 700;
		    font-style: normal;
			}
			@endif
		</style>

		<!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

		<!-- jQuery -->  
		<script type="application/javascript" src="{{ asset_('assets/jquery/jquery-3.5.1.min.js') }}"></script>
		
    <!-- Semantic-UI -->
    <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
    <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

    <!-- Spacing CSS -->
		<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

		<!-- App CSS -->
		<link rel="stylesheet" href="{{ asset_('assets/admin/app-'.locale_direction().'.css') }}">
		
		<!-- App Javascript -->
		<script type="application/javascript" src="{{ asset_('assets/admin/app.js') }}"></script>
		
		<script type="application/javascript">
			"use strict";

			window.translation = @json(config('translation', JSON_UNESCAPED_UNICODE));
		</script>

		@yield('additional_head_tags')
	</head>
	

	<body dir="{{ locale_direction() }}" vhidden>

		<div class="ui main fluid container">
			<div class="ui celled grid m-0 shadowless">
				<div class="row" id="content">
					
					<div class="l-side-wrapper column">
						<div class="ui header p-0">
							<a href="{{ route('admin') }}">
								<img class="ui image mx-auto" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="logo">
							</a>
						</div>

						<div class="ui vertical fluid menu togglable">

							<a class="item parent" href="{{ route('admin') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/dashboard.png') }}">
								{{ __('Dashboard') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('products') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/product.png') }}">
								{{ __('Products') }}
								<i class="circle outline icon mx-0"></i>
							</a>
							
							@if(config('app.subscriptions.enabled'))
							<a class="item parent" href="{{ route('subscriptions') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/product.png') }}">
								{{ __('Pricing table') }}
								<i class="circle outline icon mx-0"></i>
							</a>
							@endif

							<a class="item parent" href="{{ route('categories') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/categories.png') }}">
								{{ __('Categories') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('licenses') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/licenses.png') }}">
								{{ __('Licenses') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('transactions') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/cart.png') }}">
								{{ __('Transactions') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('users_subscriptions') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/subscriptions.png') }}">
								{{ __('Users subscriptions') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('coupons') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/coupons.png') }}">
								{{ __('Coupons') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							@if(config('app.blog.enabled'))
							<a class="item parent" href="{{ route('posts') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/posts.png') }}">
								{{ __('Posts') }}
								<i class="circle outline icon mx-0"></i>
							</a>
							@endif

							<a class="item parent" href="{{ route('pages') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/pages.png') }}">
								{{ __('Pages') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('keys') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/key.png') }}">
								{{ __('Keys') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('comments') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/comments.png') }}">
								{{ __('Comments') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('users') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/user.png') }}">
								{{ __('Users') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('reviews') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/reviews.png') }}">
								{{ __('Reviews') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('payment_links') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/dollar.png') }}">
								{{ __('Payment links') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('affiliate.balances') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/dollar.png') }}">
								{{ __('Affiliate Cashouts') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('subscribers') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/emails.png') }}">
								{{ __('Newsletter') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('faq') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/question-mark.png') }}">
								{{ __('FAQ') }}
								<i class="circle outline icon mx-0"></i>
							</a>
							
							<a class="item parent logout" href="{{ route('support') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/help.png') }}">
								{{ __('Support messages') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" href="{{ route('searches') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/searches.png') }}">
								{{ __('Searches') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<div class="dropdown active">
								<div class="item parent">
									<img src="{{ asset_('assets/images/left_menu_icons/settings.png') }}">
									{{ __('Settings') }}
									<i class="circle outline icon mx-0"></i>
								</div>
								<div class="children settings">
									<a class="item" href="{{ url('admin/settings/general') }}"><span>{{ __('General') }}</span></a>
									<a class="item" href="{{ url('admin/settings/mailer') }}"><span>{{ __('Mailer') }}</span></a>
									<a class="item" href="{{ url('admin/settings/payments') }}"><span>{{ __('Payments') }}</span></a>
									<a class="item" href="{{ url('admin/settings/files_host') }}"><span>{{ __('Storage') }}</span></a>
									<a class="item" href="{{ url('admin/settings/affiliate') }}"><span>{{ __('Affiliate') }}</span></a>
									<a class="item" href="{{ url('admin/settings/social_login') }}"><span>{{ __('Social Login') }}</span></a>
									<a class="item" href="{{ url('admin/settings/search_engines') }}"><span>{{ __('Search engines') }}</span></a>
									<a class="item" href="{{ url('admin/settings/adverts') }}"><span>{{ __('Ads') }}</span></a>
									<a class="item" href="{{ url('admin/settings/chat') }}"><span>{{ __('Chat') }}</span></a>
									<a class="item" href="{{ url('admin/settings/captcha') }}"><span>{{ __('Captcha') }}</span></a>
									<a class="item" href="{{ url('admin/settings/translations') }}"><span>{{ __('Translations') }}</span></a>
									<a class="item" href="{{ url('admin/settings/database') }}"><span>{{ __('Database') }}</span></a>
									<a class="item" href="{{ url('admin/settings/cache') }}"><span>{{ __('Cache') }}</span></a>
									<a class="item" href="{{ url('admin/settings/bulk_upload') }}"><span>{{ __('Bulk upload') }}</span></a>
								</div>
							</div>
							
							<a class="item parent logout" href="{{ route('licenses_validation_form') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/certificate.png') }}">
								{{ __('Validate licenses') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent logout" href="{{ route('profile.edit') }}">
								<img src="{{ asset_('assets/images/left_menu_icons/user.png') }}">
								{{ __('Profile') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent" id="report-errors">
								<img src="{{ asset_('assets/images/left_menu_icons/report.png') }}">
								{{ __('Report errors') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<a class="item parent logout">
								<img src="{{ asset_('assets/images/left_menu_icons/logout.png') }}">
								{{ __('Logout') }}
								<i class="circle outline icon mx-0"></i>
							</a>

							<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>

						</div>
					</div>

					<div id="vertical-divider"></div>

					<div class="r-side-wrapper column">
						<div class="ui unstackable secondary menu px-1" id="top-menu">
							<a class="item ui large button capitalize" href="{{ route('home') }}">{{ __('Home') }}</a>

							<div class="right menu">
							  <div class="item ui dropdown admin-notifications">
							    <div class="text bold">
							      <i class="bell outline icon mx-0"></i>
							      <span>({{ $admin_notifications->total() }})</span>
							    </div>

							    <div class="left menu rounded-corner">
							      @foreach($admin_notifications ?? [] as $admin_notif)
							      <a class="item" data-id="{{ $admin_notif->item_id }}" data-table="{{ $admin_notif->table }}">
							        <div class="header">
							          <span>{{ $admin_notif->user }}</span>
							          <span>{{ $admin_notif->created_at->diffForHumans() }}</span>
							        </div>
							        <div class="content">
							          {{ __($admin_notif->content) }}
							        </div>
							      </a>
							      @endforeach

							      <a href="{{ route('admin_notifs') }}" class="item all">{{ __('View all') }}</a>
							    </div>
							  </div>

							  @if(count(config('langs', [])) > 1)
							  <div class="item ui dropdown languages">
							    <div class="text bold">
							      <i class="globe icon mx-0"></i>
							      {{ __(mb_ucfirst(session('locale', config('app.locale')))) }}
							    </div>

							    <div class="left menu rounded-corner">
							      @foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
							      <div class="item" data-locale="{{ $locale_code }}">
							        {{ $supported_locale['native'] ?? '' }}
							      </div>
							      @endforeach
							    </div>
							  </div>
							  @endif

							  <div class="item ui dropdown user">
							    <span class="default text capitalize">{{ auth()->user()->name }}</span>
							    <img src="{{ asset_("storage/avatars/".(auth()->user()->avatar ?? 'default.png'))."?v=".time() }}" class="ui image avatar ml-1">

							    <div class="left menu rounded-corner">
							      <a class="item" href="{{ route('profile.edit') }}">
							        <i class="user outline icon"></i>
							        {{ __('Profile') }}
							      </a>
							      <div class="item">
							        <i class="cog icon"></i>
							        {{ __('Settings') }}
							        <div class="menu settings left rounded-corner">
							            <a href="{{ route('settings', ['settings_name' => 'general']) }}" class="item">{{ __('General') }}</a>
							            <a href="{{ route('settings', ['settings_name' => 'search_engines']) }}" class="item">{{ __('Search engines') }}</a>
							            <a href="{{ route('settings', ['settings_name' => 'payments']) }}" class="item">{{ __('Payments') }}</a>
							            <a href="{{ route('settings', ['settings_name' => 'social_login']) }}" class="item">{{ __('Social Login') }}</a>
							            <a href="{{ route('settings', ['settings_name' => 'mailer']) }}" class="item">{{ __('Mailer') }}</a>
							            <a href="{{ route('settings', ['settings_name' => 'files_host']) }}" class="item">{{ __('Files host') }}</a>
							        </div>
							      </div>
							      <a class="item" href="{{ route('admin') }}">
							        <i class="chart area icon"></i>
							        {{ __('Dashboard') }}
							      </a>
							      <a class="item logout">
							        <i class="sign out alternate icon"></i>
							        {{ __('Logout') }}
							      </a>
							    </div>
							  </div>

								<a class="header item mobile-only" id="mobile-menu-toggler">
									<i class="bars large icon mx-0"></i>
								</a>
							</div>
						</div>

						<div class="ui text menu breadcrumb mb-0">
							<div class="item header">
								@yield('title')
							</div>
						</div>

						<div class="ui hidden divider mt-0"></div>

						@yield('content')

						<footer class="row">
							<div class="ui secondary unstackable menu m-0">
								<span class="item header">{{ config('app.name') }} © {{ date('Y') }} {{ __('All right reserved') }}</span>
							</div>						

							<form action="{{ route('set_locale') }}" method="post" class="d-none" id="set-locale">
								<input type="hidden" name="redirect" value="{{ url()->full() }}">
								<input type="hidden" name="locale">
							</form>
						</footer>
					</div>
				</div>

			</div>
		</div>

		<div id="cover" class="d-none"></div>
	</body>
</html>
