<div class="ui unstackable secondary menu top attached py-0" id="top-menu">

  <div class="menu-wrapper">

    <a class="item header logo" href="{{ route('home') }}">
      <img class="ui image" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
    </a>

    <form class="item search search-form" method="get" action="{{ route('home.products.q') }}">
      <div class="ui icon input">
        <input type="text" name="q" placeholder="{{ __('Search') }}...">
        <i class="search link icon"></i>
      </div>
    </form>

    <div class="right menu pr-1"> 
      <a class="item search-icon" @click="toggleMobileSearchBar">
        <i class="search icon"></i>
      </a>

      <div class="item ui dropdown categories">
        <div class="toggler">
          {{ __('Categories') }}
        </div>

        <div class="menu">
          @foreach(config('categories.category_parents', []) as $category)
          <a href="{{ route('home.products.category', ['category_slug' => $category->slug]) }}" class="item capitalize">
            {{ $category->name }}
          </a>
          @endforeach
        </div>
      </div>

      @if(config('app.blog.enabled'))
      <a href="{{ route('home.blog') }}" class="item blog">
        {{ __('Blog') }}
      </a>
      @endif
      
      @if(!auth_is_admin())
      <a href="{{ route('home.favorites') }}" class="item collection" title="Collection">
        {{ __('Collection') }}
      </a>
      @endif
      
      @if(config('app.subscriptions.enabled'))
      <a href="{{ route('home.subscriptions') }}" class="item help">
        {{ __('Pricing') }}
      </a>
      @endif
      
      @if(!auth_is_admin())
      <div class="item notifications dropdown toggler">
        <div><i class="bell outline icon"></i><span v-cloak>({{ count(config('notifications', [])) }})</span></div>

        <div class="menu">
          <div>
           
            <div class="ui unstackable items">
              @if(config('notifications'))
              <div class="items-wrapper">
                @foreach(config('notifications') as $notif)
                <a class="item mx-0"
                   data-id="{{ $notif->id }}"
                   data-href="{{ route('home.product', ['id' => $notif->product_id, 'slug' => $notif->slug . ($notif->for == 1 ? '#support' : ($notif->for == 2 ? '#reviews' : ''))]) }}">

                  <div class="image" style="background-image: url({{ asset_("storage/".($notif->for == 0 ? 'covers' : 'avatars')."/{$notif->image}") }})"></div>

                  <div class="content pl-1">
                    <p>{!! __($notif->text, ['product_name' => "<strong>{$notif->name}</strong>"]) !!}</p>
                    <time>{{ \Carbon\Carbon::parse($notif->updated_at)->diffForHumans() }}</time>
                  </div>

                </a>
                @endforeach
              </div>

              @else
              
              <div class="item message mx-0">
                <div class="ui w-100 large borderless shadowless rounded-corner message p-1">
                  {{ __('You have 0 new notifications') }}
                </div>
              </div>
              
              @endif

              @auth
              <a href="{{ route('home.notifications') }}" class="item mx-0 all">{{ __('View all') }}</a>
              @endauth
            </div>
            
          </div>
        </div>
      </div>
      @endif

      @guest
      <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="item">
        {{ __('Account') }}
      </a>
      @endguest

      <div class="item cart dropdown toggler">
        <div><i class="shopping cart icon"></i><span v-cloak>(@{{ cartItems }})</span></div>

        <div class="menu" v-if="Object.keys(cart).length">
          <div>
            <div class="ui unstackable items">
              
              <div class="items-wrapper">
                <div class="item mx-0" v-for="product in cart">
                  <div class="image" :style="'background-image: url('+ product.cover +')'"></div>
                  <div class="content pl-1">
                    <strong :title="product.name"><a :href="product.url">@{{ product.name }}</a></strong> 
                    <div class="subcontent mt-1">
                      <div class="price">
                        @{{ price(product.price, true) }}
                      </div>
                      <div class="remove" :disabled="couponRes.status">
                        <i class="trash alternate outline icon mx-0" @click="removeFromCart(product.id)"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <a href="{{ route('home.checkout') }}" class="item mx-0 checkout">{{ __('Checkout') }}</a>

            </div>
          </div>
        </div>

        <div class="menu" v-else>
          <div class="ui unstackable items">
            <div class="item p-1-hf">
              <div class="ui message borderless shadowless rounded-corner w-100 left aligned p-1">
                {{ __('Your cart is empty') }}
              </div>
            </div>
          </div>
        </div>
      </div>

      @auth
      <div class="item ui dropdown user">
          <img src="{{ asset_("storage/avatars/". if_null(auth()->user()->avatar, 'default.jpg')) }}" class="ui avatar image mx-0">
      
          <div class="left menu">
            @if(auth_is_admin())
              <a class="item" href="{{ route('admin') }}">
                <i class="circle blue icon"></i>
                {{ __('Administration') }}
              </a>

              <a class="item" href="{{ route('profile.edit') }}">
                  <i class="user outline icon"></i>
                  {{ __('Profile') }}
              </a>

              <a class="item" href="{{ route('transactions') }}">
                  <i class="shopping cart icon"></i>
                  {{ __('Transactions') }}
              </a>

              <div class="item">
                <i class="cog icon"></i>
                {{ __('Settings') }}
                <div class="menu settings left d-block w-100">
                  <a href="{{ route('settings', ['settings_name' => 'general']) }}" class="item d-block w-100">{{ __('General') }}</a>
                  <a href="{{ route('settings', ['settings_name' => 'search_engines']) }}" class="item d-block w-100">{{ __('Search engines') }}</a>
                  <a href="{{ route('settings', ['settings_name' => 'payments']) }}" class="item d-block w-100">{{ __('Payments') }}</a>
                  <a href="{{ route('settings', ['settings_name' => 'social_login']) }}" class="item d-block w-100">{{ __('Social Login') }}</a>
                  <a href="{{ route('settings', ['settings_name' => 'mailer']) }}" class="item d-block w-100">{{ __('Mailer') }}</a>
                  <a href="{{ route('settings', ['settings_name' => 'files_host']) }}" class="item d-block w-100">{{ __('Files host') }}</a>
                  <a href="{{ route('settings', ['settings_name' => 'adverts']) }}" class="item d-block w-100">{{ __('Ads') }}</a>
                </div>
              </div>
              <div class="item">
                <i class="file code outline icon"></i>
                {{ __('Products') }}
                <div class="menu left d-block w-100">
                    <a href="{{ route('products') }}" class="item d-block w-100">{{ __('List') }}</a>
                    <a href="{{ route('products.create') }}" class="item d-block w-100">{{ __('Create') }}</a>
                </div>
              </div>
              <div class="item">
                <i class="sticky note outline icon"></i>
                {{ __('Pages') }}
                <div class="menu left d-block w-100">
                    <a href="{{ route('pages') }}" class="item d-block w-100">{{ __('List') }}</a>
                    <a href="{{ route('pages.create') }}" class="item d-block w-100">{{ __('Create') }}</a>
                </div>
              </div>
              <div class="item">
                <i class="file alternate outline icon"></i>
                {{ __('Posts') }}
                <div class="menu left d-block w-100">
                    <a href="{{ route('posts') }}" class="item d-block w-100">{{ __('List') }}</a>
                    <a href="{{ route('posts.create') }}" class="item d-block w-100">{{ __('Create') }}</a>
                </div>
              </div>
              <div class="item">
                <i class="tags icon"></i>
                {{ __('Categories') }}
                <div class="menu left d-block w-100">
                    <a href="{{ route('categories') }}" class="item d-block w-100">{{ __('List') }}</a>
                    <a href="{{ route('categories.create') }}" class="item d-block w-100">{{ __('Create') }}</a>
                </div>
              </div>
              <div class="item">
                <i class="question circle icon"></i>
                {{ __('Faq') }}
                <div class="menu left d-block w-100">
                    <a href="{{ route('faq') }}" class="item d-block w-100">{{ __('List') }}</a>
                    <a href="{{ route('faq.create') }}" class="item d-block w-100">{{ __('Create') }}</a>
                </div>
              </div>
              <a class="item" href="{{ route('support') }}">
                  <i class="comments outline icon"></i>
                  {{ __('Support') }}
              </a>
            @else
              @if(auth_is_affiliate())
              <div class="item header earnings">
                  {{ __('Earnings : :value', ['value' => price(config('affiliate_earnings', 0), false)]) }}
              </div>
              @endif 

              <a class="item" href="{{ route('home.profile') }}">
                  <i class="user outline icon"></i>
                  {{ __('Profile') }}
              </a>

              <a class="item" href="{{ route('home.favorites') }}">
                  <i class="heart outline icon"></i>
                  {{ __('Collection') }}
              </a>

              <a class="item" href="{{ route('home.notifications') }}">
                  <i class="bell outline icon"></i>
                  {{ __('Notifications') }}
              </a>

              <a class="item" href="{{ route('home.user_subscriptions') }}">
                  <i class="circle outline icon"></i>
                  {{ __('Subscriptions') }}
              </a>

              <a class="item" href="{{ route('home.purchases') }}">
                  <i class="cloud download icon"></i>
                  {{ __('Purchases') }}
              </a>

              <a class="item" href="{{ route('home.invoices') }}">
                  <i class="sticky note outline icon"></i>
                  {{ __('Invoices') }}
              </a>
            @endif

            <div class="ui divider my-0"></div>

            <a class="item logout w-100 mx-0" @click="logout">
                <i class="sign out alternate icon"></i>
                {{ __('Sign out') }}
            </a>

          </div>
      </div>
      @endauth
      
      <a class="item px-1 mobile-only mr-0" @click="toggleMobileMenu">
        <i class="bars large icon mx-0"></i>
      </a>
    </div>

  </div>

  <div class="border bottom"></div>
  <div class="border bottom"></div>
</div>

<form id="mobile-top-search" method="get" action="{{ route('home.products.q') }}" class="ui form">
    <input type="text" name="q" value="{{ request()->query('q') }}" placeholder="{{ __('Search') }}...">
</form>

<div id="mobile-menu" class="ui vertical menu">
  <div class="wrapper">
    <div class="body" v-if="menu.mobile.type === null">

      <a href="{{ route('home') }}" class="item">
        <i class="home icon"></i>
        {{ __('Home') }}
      </a>

      <a class="item" @click="setSubMenu($event, '', true, 'categories')">
        <i class="tags icon"></i>
        {{ __('Categories') }}
      </a>
      
      @if(config('app.subscriptions.enabled'))
      <a href="{{ route('home.subscriptions') }}" class="item">
        <i class="dollar sign icon"></i>
        {{ __('Pricing') }}
      </a>
      @endif

      @if(config('app.blog.enabled'))
      <a href="{{ route('home.blog') }}" class="item">
        <i class="bold icon"></i>
        {{ __('Blog') }}
      </a>
      @endif

      <a href="{{ route('home.favorites') }}" class="item">
        <i class="heart outline icon"></i>
        {{ __('Collection') }}
      </a>

      <a class="item" @click="setSubMenu($event, '', true, 'pages')">
        <i class="file alternate outline icon"></i>
        {{ __('Pages') }}
      </a>
      
      @guest
      <a href="{{ route('login') }}" class="item">
        <i class="user outline icon"></i>
        {{ __('Account') }}
      </a>
      @endguest

      @auth
      @if(auth_is_admin())
      <a href="{{ route('profile.edit') }}" class="item">
        <i class="user outline icon"></i>
        {{ __('Profile') }}
      </a>
      <a class="item" href="{{ route('admin') }}">
        <i class="chart pie icon"></i>
        {{ __('Dashboard') }}
      </a>
      @else
      <a href="{{ route('home.profile') }}" class="item">
        <i class="user outline icon"></i>
        {{ __('Profile') }}
      </a>

      <a href="{{ route('home.purchases') }}" class="item">
        <i class="cloud download icon"></i>
        {{ __('Purchases') }}
      </a>
      @endif
      @endauth
      
      <a href="{{ route('home.page', 'privacy-policy') }}" class="item">
        <i class="circle outline icon"></i>
        {{ __('Privacy policy') }}
      </a>

      <a href="{{ route('home.page', 'terms-and-conditions') }}" class="item">
        <i class="circle outline icon"></i>
        {{ __('Terms and conditions') }}
      </a>

      <a href="{{ route('home.support') }}" class="item">
        <i class="question circle outline icon"></i>
        {{ __('Support') }}
      </a>

      <a class="item" @click="setSubMenu($event, '', true, 'languages')">
        <i class="globe icon"></i>
        {{ __('Language') }}
      </a>
    </div>

    <div class="sub-body" v-else>
      <div class="item" @click="mainMenuBack">
        <i class="arrow alternate circle left blue icon"></i>
        {{ __('Back') }}
      </div>

      <div v-if="menu.mobile.type === 'categories'">
        <div v-if="menu.mobile.selectedCategory === null">
          <a class="item" v-for="category in menu.mobile.submenuItems" @click="setSubMenu($event, category.id, true, 'subcategories')">
            <i class="circle outline icon"></i>
            @{{ category.name }}
          </a>
        </div>
      </div>

      <div v-else-if="menu.mobile.type === 'subcategories'">
        <a class="item" v-for="subcategory in menu.mobile.submenuItems"
           :href="setProductsRoute(menu.mobile.selectedCategory.slug+'/'+subcategory.slug)">
          <i class="circle outline icon"></i>
          @{{ subcategory.name }}
        </a>
      </div>

      <div v-else-if="menu.mobile.type === 'pages'">
        <a class="item" v-for="page in menu.mobile.submenuItems"
           :href="setPageRoute(page['slug'])">
          <i class="circle outline icon"></i>
          @{{ page['name'] }}
        </a>
      </div>

      <div v-else-if="menu.mobile.type === 'languages'">
        @foreach(\LaravelLocalization::getSupportedLocales() as $locale_code => $supported_locale)
        <a class="item" href="{{ \LaravelLocalization::getLocalizedURL($locale_code) }}">
          {{ $supported_locale['native'] ?? '' }}
        </a>
        @endforeach
      </div>
    </div>
  </div>
</div>

<div id="mobile-menu-2" class="ui secondary menu">
  <a href="/" class="item">
    <div class="icon">
      <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
        <g id="icon">
          <polyline points="3.5,9.4 3.5,18.5 8.5,18.5 8.5,12.5 12.5,12.5 12.5,18.5 17.5,18.5 17.5,9.4" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <polygon points="18.35,10 10.5,3.894 2.65,10 1.5,8.522 10.5,1.523 19.5,8.522" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
        </g>
      </svg>
    </div>
    <div class="text">{{ __('Home') }}</div>
  </a>
  <div class="ui dropdown item">
    <div>
      <div class="icon">
        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
          <g id="icon">
            <path d="M18.7,18.5H2.3c-0.442,0,-0.8,-0.358,-0.8,-0.8V3.3c0,-0.442,0.358,-0.8,0.8,-0.8h16.4c0.442,0,0.8,0.358,0.8,0.8v14.4C19.5,18.142,19.142,18.5,18.7,18.5z" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
            <path d="M1.5,6.5h18M7.5,2.5v16M13.5,2.5v16M1.5,10.5h18M1.5,14.5h18" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          </g>
        </svg>
      </div>
      <div class="text">{{ __('Categories') }}</div>
    </div>  
    <div class="menu">
      @foreach(config('categories.category_parents', []) as $category)
      <a href="{{ route('home.products.category', ['category_slug' => $category->slug]) }}" class="item capitalize">
        {{ $category->name }}
      </a>
      @endforeach
    </div>
  </div>
  <a href="{{ !\Auth::check() ? route('login', ['redirect' => route('home.notifications')]) : route('home.notifications') }}" class="item">
    <div class="icon">
      <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
        <g id="icon">
          <path d="M3.788,7.39c0.295,-0.804,0.755,-1.562,1.38,-2.224c0.614,-0.65,1.329,-1.145,2.099,-1.486M1.553,6.558c0.403,-1.096,1.029,-2.131,1.881,-3.033c0.837,-0.886,1.813,-1.562,2.862,-2.026" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round" opacity="0.5"/>
          <path d="M13.787,3.68c0.77,0.34,1.485,0.836,2.099,1.486c0.625,0.662,1.084,1.42,1.38,2.224M14.756,1.499c1.05,0.464,2.026,1.14,2.862,2.026c0.852,0.902,1.479,1.936,1.881,3.033" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round" opacity="0.5"/>
          <path d="M12.553,17.57c0,1.169,-0.971,1.93,-2.095,1.93c-1.124,0,-2.065,-0.762,-2.065,-1.93" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <path d="M17.697,16.069c-1.06,-1.144,-2.85,-3.623,-2.85,-5.534c0,-1.806,-1.203,-3.346,-2.891,-3.942c0.014,-0.086,0.023,-0.173,0.023,-0.264c0,-0.845,-0.663,-1.529,-1.48,-1.529s-1.48,0.685,-1.48,1.529c0,0.07,0.006,0.138,0.015,0.206c-1.78,0.548,-3.068,2.131,-3.068,3.999c0,2.676,-1.766,4.307,-2.757,5.582c-1,1.039,5.23,1.5,7.244,1.5S18.757,17.212,17.697,16.069z" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
        </g>
      </svg>
    </div>
    <div class="text">{{ __('Notifications') }}</div>
  </a>
  <a href="{{ route('home.checkout') }}" class="item">
    <div class="icon">
      <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
        <g id="icon">
          <path d="M6.8814,13.2403C8.5,13.2403,16.896,12.5585,17.5,12.5c1.0278,-0.0995,1.5,-0.6501,1.5,-1.1231l0.5,-4.1848c0,-0.4087,-0.3061,-0.7533,-0.7119,-0.8017L4.718,4.664" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <path d="M9.0901,18.095c0,0.776,-0.629,1.405,-1.405,1.405s-1.405,-0.629,-1.405,-1.405s0.629,-1.405,1.405,-1.405S9.0901,17.319,9.0901,18.095zM16.5503,16.69c-0.776,0,-1.405,0.629,-1.405,1.405s0.629,1.405,1.405,1.405s1.405,-0.629,1.405,-1.405S17.3263,16.69,16.5503,16.69z" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <path d="M18.5,16.5H6.1995c-0.2305,0,-0.3654,-0.2483,-0.4381,-0.3729c-0.1566,-0.2684,-0.1542,-0.6537,0.006,-0.937c0.0279,-0.0493,0.9105,-1.5099,0.9105,-1.5099c0.2964,-0.4944,0.2739,-1.3885,0.0808,-1.9675c-0.0348,-0.1046,-2.4411,-8.314,-2.4825,-8.4381C4.1905,3.0177,3.9036,2.5,3.5313,2.5C3.2765,2.5,1.5,2.4874,1.5,2.4874" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <path d="M6.1512,14.5993" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
        </g>
      </svg>
      <span v-cloak>(@{{ cartItems }})</span>
    </div>
    <div class="text">{{ __('Cart') }}</div>
  </a>
  <a href="{{ !\Auth::check() ? route('login', ['redirect' => url()->current()]) : (auth_is_admin() ? route('admin') : route('home.profile')) }}" class="item">
    <div class="icon">
      @if(auth_is_admin())
      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
        <g id="icon">
          <path d="M6.6799,18h7.6395c0.8564,0,1.6805,-0.3252,2.3077,-0.9083c1.5863,-1.475,2.6396,-3.5153,2.8386,-5.8006c0.475,-5.4535,-4.0687,-10.1129,-9.5323,-9.7736C5.2268,1.8099,1.5,5.7197,1.5,10.5c0,2.6233,1.1226,4.9842,2.9135,6.6292C5.03,17.6955,5.8428,18,6.6799,18z" fill="none" stroke="#3D73AD" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <circle cx="10.5" cy="14" r="1.5" fill="none" stroke="#3D73AD" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <line x1="11.497" y1="12.6" x2="15.1" y2="7.7" fill="none" stroke="#3D73AD" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
          <path d="M3.5,10.5c0,-3.866,3.134,-7,7,-7s7,3.134,7,7" fill="none" stroke="#3D73AD" stroke-width="0.9" stroke-linecap="round" stroke-miterlimit="1" stroke-linejoin="round"/>
        </g>
      </svg>
      @else
      <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="21px" height="21px" viewBox="0 0 21 21" enable-background="new 0 0 21 21" xml:space="preserve">
        <g id="icon">
          <circle cx="10.5" cy="10.5" r="9" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-miterlimit="1"/>
          <path d="M17.2253,16.3995c-0.4244,-1.183,-1.3275,-1.4113,-4.1682,-2.5227c-0.4969,-0.2068,-0.6372,-0.52,-0.6,-0.9446c0.9599,-1.2714,1.5383,-3.3359,1.5383,-4.7737c0,-2.2266,-0.6858,-3.641,-3.4947,-3.641S7.0624,5.9259,7.0624,8.1526c0,1.4378,0.5761,3.4895,1.536,4.7609c0.0372,0.4246,-0.1042,0.7236,-0.6011,0.9304c-2.8435,1.1126,-3.746,1.3626,-4.17,2.556" fill-rule="evenodd" fill="none" stroke="#FFFFFF" stroke-width="1" stroke-linecap="round" stroke-miterlimit="1"/>
        </g>
      </svg>
      @endif
    </div>
    <div class="text">{{ auth_is_admin() ? __('Dashboard') : __('Account') }}</div>
  </a>
</div>