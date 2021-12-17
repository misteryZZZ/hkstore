<div class="row first">
	<div class="column">
		<img class="ui image mx-auto" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
		<p class="m-0">
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
		<form class="ui large form newsletter" action="{{ route('home.newsletter', ['redirect' => url()->current()]) }}" method="post">
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
			<a class="ui circular white inverted icon button" href="{{ config('app.facebook') }}">
				<i class="large facebook icon"></i>
			</a>
			@endif

			@if(config('app.twitter'))
			<a class="ui circular white inverted icon button" href="{{ config('app.twitter') }}">
				<i class="large twitter icon"></i>
			</a>
			@endif

			@if(config('app.pinterest'))
			<a class="ui circular white inverted icon button" href="{{ config('app.pinterest') }}">
				<i class="large pinterest icon"></i>
			</a>
			@endif

			@if(config('app.youtube'))
			<a class="ui circular white inverted icon button" href="{{ config('app.youtube') }}">
				<i class="large youtube icon"></i>
			</a>
			@endif

			@if(config('app.tumblr'))
			<a class="ui circular white inverted icon button mr-0" href="{{ config('app.tumblr') }}">
				<i class="large tumblr icon"></i>
			</a>
			@endif

		</div>
	</div>
</div>

<div class="row last">
	<div class="sixteen wide column">
		<div class="ui secondary stackable menu mb-0">
			@if(count(config('langs') ?? []) > 1)
	    <div class="item ui top scrolling dropdown languages">
	      <div class="text capitalize">{{ __(config('laravellocalization.supportedLocales.'.session('locale', 'en').'.name')) }}</div>
	    
	      <div class="left menu">
	      	<div class="header">{{ __('Languages') }}</div>
	      	<div class="wrapper">
		        @foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
		        <a class="item" @click="setLocale('{{ $locale_code }}')">
		          {{ $supported_locale['native'] ?? '' }}
		        </a>
		        @endforeach
		      </div>
	      </div>
	    </div>
	    @endif

	    @if(config('app.affiliate.enabled'))
			<a href="{{ route('home.affiliate') }}" class="item">{{ __('Affiliate Program') }}</a>
			@endif

      @foreach(collect(config('pages', []))->where('deletable', 0) as $page)
			<a href="{{ route('home.page', $page['slug']) }}" class="item">{{ __($page['name']) }}</a>
			@endforeach
			
			@if(count(config('payments.currencies') ?? []) > 1)
			<span class="item ui top dropdown currencies">
				<span class="text uppercase">{{ session('currency', config('payments.currency_code')) }}</span>

				<span class="menu">
					<div class="header">{{ __('Currency') }}</div>
					<div class="wrapper">
						@foreach(config('payments.currencies') as $code => $currency)
						<a href="{{ route('set_currency', ['code' => $code, 'redirect' => url()->full()]) }}" class="item">{{ $code }}</a>
						@endforeach
					</div>
				</span>
			</span>
			@endif
			
			@if(config('app.blog.enabled'))
			<a class="item" href="{{ route('home.blog') }}">{{ __('Blog') }}</a>
			@endif
			
			<a class="item" href="{{ route('home.support') }}">{{ __('Help') }}</a>
			
			@if(config('payments.guest_checkout') && !\Auth::check())
			<a class="item" href="{{ route('home.guest') }}">{{ __('Guest section') }}</a>
			@endif

			@if(auth_is_admin())
			<span class="item ui top dropdown templates">
				<span class="text uppercase">{{ __('Template') }}</span>

				<span class="menu">
					@foreach(['valexa', 'tendra', 'default'] as $template)
					<a href="{{ route('set_template', ['template' => $template, 'redirect' => url()->full()]) }}" class="item">{{ ucfirst($template) }}</a>
					@endforeach
				</span>
			</span>
			@endif
			
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