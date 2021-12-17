<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="UTF-8">
    <meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset_("storage/images/".config('app.favicon'))}}">
    
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
    <link rel="stylesheet" href="{{ asset_('assets/front/'.config('app.template', 'valexa').'-'.locale_direction().'.css?v='.config('app.version')) }}">
    
    @yield('additional_head_tags')
    
    <script>
      'use strict';
      
      window.props = {
        itemId: null,
        product: {},
        products: {},
        routes: {
          checkout: '{{ route('home.checkout') }}',
          products: '{{ route('home.products.category', '') }}',
          pages: '{{ route('home.page', '') }}',
          payment: '{{ route('home.checkout.payment') }}',
          coupon: '{{ route('home.checkout.validate_coupon') }}',
          productFolder: '{{ route('home.product_folder_async') }}',
          notifRead: '{{ route('home.notifications.read') }}',
          addToCartAsyncRoute: '{{ route('home.add_to_cart_async') }}',
          subscriptionPayment: '{{ config('app.subscriptions.enabled') ? route('home.subscription.payment') : '' }}'
        },
        currentRouteName: '{{ Route::currentRouteName() }}',
        trasactionMsg: '{{ session('transaction_response') }}',
        location: window.location,
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
          offline: {{ var_export(config('payments.offline.enabled') ? true : false) }}
        },
        paymentProcessor: '{{ $payment_processor ?? null }}',
        paymentFees: @json(config('fees')),
        translation: @json(config('translation')),
        currency: {code: '{{ config('payments.currency_code') }}', symbol: '{{ config('payments.currency_symbol') }}'},
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
        usersNotif: '{{ config('app.users_notif', '') }}'
      }
    </script>
    
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
      <div class="ui one column celled middle aligned grid m-0 shadowlessn" id="auth">
        <div class="form column mx-auto">
          <div class="ui fluid card">

            <div class="content center aligned logo">
              <a href="/">
                <img class="ui image mx-auto" src="{{ asset_("storage/images/".config('app.logo')) }}" alt="{{ config('app.name') }}">
              </a>
            </div>

            <div class="content center aligned title">
              <h2>@yield('title')</h2>
            </div>

            @yield('content')
          </div>
        </div>
      </div>
    </div>
  </body>
</html>