{{-- TENDRA --}}

@extends(view_path('master'))

@section('additional_head_tags')
<meta name="robots" content="noindex,nofollow">

@if(config('payments.stripe.enabled'))
	<script src="https://js.stripe.com/v3/"></script>
	<script>
		'use strict';
		var stripe = Stripe('{{ config('payments.stripe.client_id') }}');
	</script>
@endif

@if(config('payments.payhere.enabled'))
	<script type="text/javascript" src="https://www.payhere.lk/lib/payhere.js"></script>
@endif

@if(config('payments.omise.enabled'))
<script type="text/javascript" src="https://cdn.omise.co/omise.js"></script>

<script>
	'use strict';
	window.omisePublicKey = '{{ config('payments.omise.public_key') }}';
</script>
@endif

@if(config('payments.paytr.enabled'))
<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
<style>
	#paytr-modal {
		border-radius: 1rem;
		max-width: 480px;
		width: 100%;
		max-height: 90vh;
		overflow: auto;
	}
</style>
@endif

@if(config('payments.spankpay.enabled'))
<script type="text/javascript" src="{{ asset_("assets/front/spankpay.js") }}"></script>
@endif
@endsection


@section('pre_js')
@if(config('payments.authorize_net.enabled'))
<script type="text/javascript" src="https://js{{ config('payments.authorize_net.mode') == 'live' ? '' : 'test' }}.authorize.net/v3/AcceptUI.js" charset="utf-8" defer=""></script>

<script src="https://js{{ config('payments.authorize_net.mode') == 'live' ? '' : 'test' }}.authorize.net/v1/Accept.js"></script>
@endif
@endsection

@section('body')
	
<div vhidden v-if="trasactionMsg === 'processing'">
	<div class="ui active dimmer">
    <div class="ui small text loader">{{ __('Processing') }}</div>
  </div>
</div>

<div vhidden class="ui shadowless celled grid my-0" :class="{hasItems: cartItems>0}" id="checkout-page">
	<!-- CART ITEMS -->
	<div class="column left">
		<div class="ui fluid card">
			<div class="content cart-title" :class="{empty: !cartItems}">
				<div class="header">{{ __('Shopping cart') }}</div>
	      <div class="sub header mt-1">
	        <p>{!! __('You have :cartItems item(s) in your shopping cart', ['cartItems' => '<strong>@{{ cartItems }}</strong>']) !!}</p>
	      </div>
			</div>

			<div class="content cart-items">
				<div class="cart-item" v-for="prd in cart">
					<div class="image">
						<a :href="prd.url" :style="'background-image: url(' + prd.cover + ')'"></a>
					</div>
					<div class="name">
						<a class="d-block" :href="prd.url">@{{ prd.name }}</a>
						<div class="license">@{{ __(prd.license_name) }}</div>
					</div>
					<div class="price">
						<span>@{{ price(prd.price, true) }}</span>
					</div>
					<div class="delete">
						<i class="close icon link mx-0" @click="removeFromCart(prd.id)" :disabled="couponRes.status"></i>
					</div>
				</div>

			  <div class="ui fluid circular-corner message empty-cart-msg" v-if="cartItems == 0">
			  	{{ __('Your cart is empty') }}
			  </div>
			</div>
		</div>
	</div>

	<!-- PAYMENT METHODS -->
	<div class="column right" v-if="cartItems > 0">
		<div class="payment-methods w-100">
			<div class="total-fee">
				<div class="fee">
					{{ __('Purchase Fee : ') }}
					<span v-if="!isNaN(getPaymentFee())">
						@{{ price(getPaymentFee()) }}
					</span>
				</div>

				<div class="total">
					{{ __('Total : ') }}
					<span v-if="!isNaN(getTotalAmount())">
						@{{ price(getTotalAmount()) }}
					</span>
				</div>
			</div>
          
		  <div class="ui unstackable items" v-if="getTotalAmount() > 0 || customAmount > 0">
				@if(config('payments.stripe.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'stripe')}" @click="setPaymentProcessor('stripe')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Stripe') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'stripe')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.flutterwave.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'flutterwave')}" @click="setPaymentProcessor('flutterwave')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Flutterwave') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'flutterwave')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.skrill.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'skrill')}" @click="setPaymentProcessor('skrill')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Skrill') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'skrill')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.sslcommerz.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'sslcommerz')}" @click="setPaymentProcessor('sslcommerz')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Sslcommerz') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'sslcommerz')}"></i>
						</div>
					</div>
				</div>
				@endif
				
				@if(config('payments.paypal.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'paypal')}" @click="setPaymentProcessor('paypal')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Paypal') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/paypal-curved-64px.png') }}" alt="Paypal">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'paypal')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.authorize_net.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'authorize_net')}" @click="setPaymentProcessor('authorize_net')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Authorize') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/paypal-curved-64px.png') }}" alt="Paypal">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'authorize_net')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.razorpay.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'razorpay')}" @click="setPaymentProcessor('razorpay')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Razorpay') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'razorpay')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.coingate.enabled'))
				<div class="item crypto" :class="{'active': (paymentProcessor == 'coingate')}" @click="setPaymentProcessor('coingate')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Coingate') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/crypto-currency-icons/bch.png') }}" alt="bch">
							<img src="{{ asset_('assets/images/crypto-currency-icons/btc.png') }}" alt="btc">
							<img src="{{ asset_('assets/images/crypto-currency-icons/eth.png') }}" alt="eth">
							<img src="{{ asset_('assets/images/crypto-currency-icons/usdt.png') }}" alt="usdt">
							<img src="{{ asset_('assets/images/crypto-currency-icons/xrp.png') }}" alt="xrp">
							<img src="{{ asset_('assets/images/crypto-currency-icons/ltc.png') }}" alt="ltc">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'coingate')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.spankpay.enabled'))
				<div class="item crypto" :class="{'active': (paymentProcessor == 'spankpay')}" @click="setPaymentProcessor('spankpay')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Spankpay') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/crypto-currency-icons/bch.png') }}" alt="bch">
							<img src="{{ asset_('assets/images/crypto-currency-icons/btc.png') }}" alt="btc">
							<img src="{{ asset_('assets/images/crypto-currency-icons/eth.png') }}" alt="eth">
							<img src="{{ asset_('assets/images/crypto-currency-icons/usdt.png') }}" alt="usdt">
							<img src="{{ asset_('assets/images/crypto-currency-icons/xrp.png') }}" alt="xrp">
							<img src="{{ asset_('assets/images/crypto-currency-icons/ltc.png') }}" alt="ltc">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'spankpay')}"></i>
						</div>
					</div>
				</div>
				@endif


				@if(config('payments.coinpayments.enabled'))
				<div class="item crypto" :class="{'active': (paymentProcessor == 'coinpayments')}" @click="setPaymentProcessor('coinpayments')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Coinpayments') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/crypto-currency-icons/bch.png') }}" alt="bch">
							<img src="{{ asset_('assets/images/crypto-currency-icons/btc.png') }}" alt="btc">
							<img src="{{ asset_('assets/images/crypto-currency-icons/eth.png') }}" alt="eth">
							<img src="{{ asset_('assets/images/crypto-currency-icons/xrp.png') }}" alt="xrp">
							<img src="{{ asset_('assets/images/crypto-currency-icons/ltc.png') }}" alt="ltc">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'coinpayments')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.midtrans.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'midtrans')}" @click="setPaymentProcessor('midtrans')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Midtrans') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'midtrans')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.iyzico.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'iyzico')}" @click="setPaymentProcessor('iyzico')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Iyzico') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'iyzico')}"></i>
						</div>
					</div>
				</div>
				@endif
				
				@if(config('payments.paytr.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'paytr')}" @click="setPaymentProcessor('paytr')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Paytr') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'paytr')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.payhere.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'payhere')}" @click="setPaymentProcessor('payhere')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Payhere') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'payhere')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.omise.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'omise')}" @click="setPaymentProcessor('omise')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Omise') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/kbank.png') }}" title="Kasikorn Bank">
							<img src="{{ asset_('assets/images/ktc_card.jpg') }}" title="Krungthai Card">
							<img src="{{ asset_('assets/images/krungsri_first_choice.png') }}" title="krungsri first choice">
							<img src="{{ asset_('assets/images/scb.png') }}" title="Siam Commercial Bank">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'omise')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.paystack.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'paystack')}" @click="setPaymentProcessor('paystack')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Paystack') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'paystack')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.adyen.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'adyen')}" @click="setPaymentProcessor('adyen')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Adyen') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>	

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'adyen')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.instamojo.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'instamojo')}" @click="setPaymentProcessor('instamojo')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Instamojo') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/visa-curved-64px.png') }}" alt="Visa">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'instamojo')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.paymentwall.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'paymentwall')}" @click="setPaymentProcessor('paymentwall')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Paymentwall') }}</div>
						</div>

						<div class="icons">
							<img src="{{ asset_('assets/images/mastercard-curved-64px.png') }}" alt="Mastercard">
							<img src="{{ asset_('assets/images/paypal-curved-64px.png') }}" alt="Paypal">
							<img src="{{ asset_('assets/images/american-express-curved-64px.png') }}" alt="American-express">
							<img src="{{ asset_('assets/images/discover-curved-64px.png') }}" alt="Discover">
						</div>

						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'paymentwall')}"></i>
						</div>
					</div>
				</div>
				@endif

				@if(config('payments.offline.enabled'))
				<div class="item" :class="{'active': (paymentProcessor == 'offline')}" @click="setPaymentProcessor('offline')">
					<div class="wrapper">
						<div class="ui small header">
							<div class="sub header">{{ __('Offline payment') }}</div>
						</div>
						<div class="content">
							<i class="circle outline large icon mx-0" :class="{'dot blue': (paymentProcessor == 'offline')}"></i>
						</div>
					</div>
				</div>
				@endif
			</div>

			<div class="cart-payment cart-checkout">
				<form action="{{ route('home.checkout.payment') }}" method="post" id="form-checkout" class="ui big form" :class="{'d-none': !/^iyzico|paystack|paytr|payhere|coinpayments|sslcommerz|flutterwave$/.test(paymentProcessor) || ({{ var_export(\Auth::check()) }} && paymentProcessor == 'paystack')}">
					@csrf
					<input type="hidden" name="cart" :value="JSON.stringify(cart)">
					<input type="hidden" name="processor" :value="paymentProcessor">
					<input type="hidden" name="coupon" :value="couponRes.status ? couponRes.coupon.code : ''">
					<input type="hidden" name="locale" value="{{ get_locale() }}">

					@if(config('payments.authorize_net.enabled'))
					<input type="hidden" name="dataValue" id="dataValue" />
    			<input type="hidden" name="dataDescriptor" id="dataDescriptor" />
    			@endif

					@if(config('payments.omise.enabled'))
					<input type="hidden" name="omiseToken">
  				<input type="hidden" name="omiseSource">
					@endif

					@foreach($errors->all() as $message)
          <div class="ui negative fluid small message rounded-corner">
          	<i class="close icon mr-0"></i>
            {{ $message }}
          </div>
          @endforeach
          
          
				<div v-if="/iyzico|paytr|payhere|sslcommerz|flutterwave/i.test(paymentProcessor)">
					<div class="two fields">
						<div class="field">
		          <label>{{ __('First name') }}</label>
		          <input type="text" placeholder="..." name="buyer[firstname]" value="{{ old('buyer.firstname', request()->user()->firstname ?? null) }}" required autocomplete="firstname">
		        </div>

		        <div class="field">
		          <label>{{ __('Last name') }}</label>
		          <input type="text" placeholder="..." name="buyer[lastname]" value="{{ old('buyer.lastname', request()->user()->lastname ?? null) }}" required autocomplete="lastname">
		        </div>
					</div>

	        <div class="two fields">
	          <div class="field" v-if="paymentProcessor === 'iyzico'">
	            <label>{{ __('ID number') }}</label>
	            <input type="text" placeholder="..." name="buyer[id_number]" value="{{ old('buyer.id_number', request()->user()->id_number ?? null) }}" required autocomplete="id_number">
	            @error('buyer.id_number')
	              <div class="ui negative message">
	                <strong>{{ $message }}</strong>
	              </div>
	            @enderror
	          </div>

	          <div class="field" v-if="/payhere|paytr|sslcommerz|flutterwave/i.test(paymentProcessor)">
	            <label>{{ __('Phone') }}</label>
	            <input type="text" placeholder="..." name="buyer[phone]" value="{{ old('buyer.phone', request()->user()->phone ?? null) }}" required autocomplete="phone">
	            @error('buyer.phone')
	              <div class="ui negative message">
	                <strong>{{ $message }}</strong>
	              </div>
	            @enderror
	          </div>

	          <div class="field">
	            <label>{{ __('Email') }}</label>
	            <input type="email" placeholder="..." name="buyer[email]" value="{{ old('buyer.email', request()->user()->email ?? null) }}" required autocomplete="email">
	            <input type="hidden" class="d-none" value="{{ request()->ip() }}" name="ip_address">
	          </div>
	        </div>

	        <div class="two fields" v-if="paymentProcessor != 'flutterwave'">
	          <div class="field">
	            <label>{{ __('City') }}</label>
	            <input type="text" placeholder="..." name="buyer[city]" value="{{ old('buyer.city', request()->user()->city ?? null) }}" required autocomplete="city">
	          </div>

	          <div class="field">
	            <label>{{ __('Country') }}</label>
	            <input type="text" placeholder="..." name="buyer[country]" value="{{ old('buyer.country', request()->user()->country ?? null) }}" required autocomplete="country">
	          </div>
	        </div>

	        <div class="field" v-if="paymentProcessor != 'flutterwave'">
	          <label>{{ __('Address') }}</label>
	          <input type="text" placeholder="..." name="buyer[address]" value="{{ old('buyer.address', request()->user()->address ?? null) }}" required autocomplete="address">
	        </div>

	        <div class="two fields" v-if="paymentProcessor == 'sslcommerz'">
	        	<div class="field">
		          <label>{{ __('Zip code') }}</label>
		          <input type="text" placeholder="..." name="buyer[zip_code]" value="{{ old('buyer.zip_code', request()->user()->zip_code ?? null) }}" required autocomplete="address">
		        </div>

		        <div class="field">
		          <label>{{ __('State') }}</label>
		          <input type="text" placeholder="..." name="buyer[state]" value="{{ old('buyer.state', request()->user()->state ?? null) }}" required autocomplete="state">
		        </div>
	        </div>
				</div>

					@guest
					@if(config('payments.guest_checkout') && (config('payments.paystack.enabled') || config('payments.coinpayments.enabled')))
					<div v-if="/^paystack|coinpayments$/i.test(paymentProcessor)">
						<div class="field">
							<label>{{ __('Email address') }} <sup><i class="exclamation small red circular icon" title="{{ __('Required by the payment gateway.') }}"></i></sup></label>
							<input type="email" name="email" value="{{ old('email') }}">
						</div>
					</div>
					@endif
					@endguest
				</form>

				@if(config('payments.offline.enabled'))
				<div v-if="paymentProcessor == 'offline'">
					<div class="offline-payment">
						{!! config('payments.offline.instructions') !!}
					</div>
				</div>
				@endif

				<form id="coupon-form" class="ui big form">
					<div class="message" :class="{negative: !couponRes.status, positive: couponRes.status}" v-if="couponRes.msg !== undefined">
						@{{ couponRes.msg }}
					</div>

					<div class="ui action fluid input" v-if="couponFormVisible">
					  <input type="text" name="coupon-code" placeholder="{{ __('Enter your coupon code...') }}" spellcheck="false" :disabled="couponRes.status">
					  <a class="ui button" v-if="!couponRes.status" @click="applyCoupon($event)">{!! __('Apply') !!}</a>
						<a class="ui button" v-else class="reset" @click="removeCoupon()">{{ __('Reset') }}</a>
					</div>
				</form>

				<div class="bottom @guest guest @endguest" :class="{'mt-1': couponFormVisible}">
					@auth
					<button type="button" class="ui yellow circular big button mx-0" @click="checkout($event)">{{ __('Checkout') }}</button>

					<div class="coupon-text" @click="toggleCouponForm">{{ __('Do you have a coupon code ?') }}</div>
					@else
					<div class="buttons w-100 p-0">
						<button type="button" class="ui fluid blue circular big button mx-0" @click="checkout($event)">{{ __('Checkout as guest') }}</button>

						<div class="ui horizontal divider">{{ __('Or') }}</div>

						<a type="button" class="ui fluid circular yellow big button mx-0" href="{{ route('login', ['redirect' => url()->full()]) }}">{{ __('Login') }}</a>

						<div class="coupon-text right aligned mt-2" @click="toggleCouponForm">{{ __('Do you have a coupon code ?') }}</div>
					</div>
					@endauth

					@if(config('payments.authorize_net.enabled'))
					<button type="button"
						id="AcceptUIBtn"
		        class="AcceptUI d-none"
		        data-billingAddressOptions='{"show":true, "required":false}' 
		        data-apiLoginID="{{ config('payments.authorize_net.api_login_id') }}" 
		        data-clientKey="{{ config('payments.authorize_net.client_key') }}"
		        data-acceptUIFormBtnTxt="Submit" 
		        data-acceptUIFormHeaderTxt="Card Information"
		        data-paymentOptions='{"showCreditCard": true, "showBankAccount": true}' 
		        data-responseHandler="authorizeNetResponseHandler">Pay
		    	</button>
		    	@endif
				</div>
		  </div>
	  </div>
	</div>
</div>

@endsection