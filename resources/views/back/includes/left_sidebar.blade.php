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