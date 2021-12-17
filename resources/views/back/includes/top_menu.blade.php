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