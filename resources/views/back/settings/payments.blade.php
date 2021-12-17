@extends('back.master')

@section('title', __('Payments settings'))

@section('additional_head_tags')

<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>

@endsection

@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'payments') }}">

	<div class="field">
		<button type="submit" class="ui pink large circular labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Update') }}
		</button>

		<button type="button" class="ui grey large circular button mr-0 ml-1-hf" id="disable-all-services">{{ __('Disable all services') }}</button>
	</div>

	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative fluid small message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	@if(session('settings_message'))
	<div class="ui positive fluid message">
		<i class="times icon close"></i>
		{{ session('settings_message') }}
	</div>
	@endif
	
	<div class="ui fluid divider"></div>
	
	<div class="one column grid" id="settings">

		<div class="ui three doubling stackable cards">

			<!-- FLUTTERWAVE -->
			<div class="fluid card" id="flutterwave">
				<div class="content">
					<h3 class="header">
						<a href="https://www.flutterwave.com/" target="_blank"><img src="{{ asset_('assets/images/flutterwave_icon.png') }}" alt="flutterwave" class="ui small avatar mr-1">{{ __('Flutterwave') }}</a>
						<input type="hidden" name="flutterwave[name]" value="flutterwave">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="flutterwave[enabled]"
						    	@if(!empty(old('flutterwave.enabled')))
									{{ old('flutterwave.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->flutterwave->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="flutterwave[mode]" value="{{ old('flutterwave.mode', $settings->flutterwave->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Public key') }}</label>
						<input type="text" name="flutterwave[public_key]" placeholder="..." value="{{ old('flutterwave.public_key', $settings->flutterwave->public_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret key') }}</label>
						<input type="text" name="flutterwave[secret_key]" placeholder="..." value="{{ old('flutterwave.secret_key', $settings->flutterwave->secret_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Payment methods') }}</label>
						<div  class="ui selection multiple search floating dropdown">
							<input type="hidden" name="flutterwave[methods]" placeholder="..." value="{{ old('flutterwave.methods', $settings->flutterwave->methods ?? null) }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="account">{{ __('Account') }}</div>
								<div class="item" data-value="card">{{ __('Card') }}</div>
								<div class="item" data-value="banktransfer">{{ __('Bankt ransfer') }}</div>
								<div class="item" data-value="mpesa">{{ __('Mpesa') }}</div>
								<div class="item" data-value="mobilemoneyrwanda">{{ __('Mobile money rwanda') }}</div>
								<div class="item" data-value="mobilemoneyzambia">{{ __('Mobile money zambia') }}</div>
								<div class="item" data-value="qr">{{ __('Qr') }}</div>
								<div class="item" data-value="mobilemoneyuganda">{{ __('Mobile money uganda') }}</div>
								<div class="item" data-value="ussd">{{ __('Ussd') }}</div>
								<div class="item" data-value="credit">{{ __('Credit') }}</div>
								<div class="item" data-value="barter">{{ __('Barter') }}</div>
								<div class="item" data-value="mobilemoneyghana">{{ __('Mobile money ghana') }}</div>
								<div class="item" data-value="payattitude">{{ __('Payattitude') }}</div>
								<div class="item" data-value="mobilemoneyfranco">{{ __('Mobile money franco') }}</div>
								<div class="item" data-value="paga">{{ __('Paga') }}</div>
								<div class="item" data-value="1voucher">{{ __('1voucher') }}</div>
								<div class="item" data-value="mobilemoneytanzania">{{ __('Mobile money tanzania') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Verif hash') }}</label>
						<input type="text" name="flutterwave[verif_hash]" placeholder="..." value="{{ old('flutterwave.verif_hash', $settings->flutterwave->verif_hash ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="flutterwave[fee]" placeholder="..." value="{{ old('flutterwave.fee', $settings->flutterwave->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="flutterwave[minimum]" placeholder="..." value="{{ old('flutterwave.minimum', $settings->flutterwave->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }}</label>
						<input type="text" name="flutterwave[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('flutterwave.auto_exchange_to', $settings->flutterwave->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>




			<!-- PAYPAL -->
			<div class="fluid card" id="paypal">
				<div class="content">
					<h3 class="header">
						<a href="https://www.paypal.com/" target="_blank"><img src="{{ asset_('assets/images/paypal_icon.png') }}" alt="PayPal" class="ui small avatar mr-1">{{ __('PayPal') }}</a>
						<input type="hidden" name="paypal[name]" value="paypal">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="paypal[enabled]"
						    	@if(!empty(old('paypal.enabled')))
									{{ old('paypal.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->paypal->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="paypal[mode]" value="{{ old('paypal.mode', $settings->paypal->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="paypal[client_id]" placeholder="..." value="{{ old('paypal.client_id', $settings->paypal->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="paypal[secret_id]" placeholder="..." value="{{ old('paypal.secret_id', $settings->paypal->secret_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="paypal[fee]" placeholder="..." value="{{ old('paypal.fee', $settings->paypal->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="paypal[minimum]" placeholder="..." value="{{ old('paypal.minimum', $settings->paypal->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="paypal[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('paypal.auto_exchange_to', $settings->paypal->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- STRIPE -->
			<div class="fluid card" id="stripe">
				<div class="content">
					<h3 class="header">
						<a href="https://stripe.com/" target="_blank"><i class="circular blue inverted stripe s icon mr-1"></i>{{ __('Stripe') }}</a>
						<input type="hidden" name="stripe[name]" value="stripe">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="stripe[enabled]"
						    	@if(!empty(old('stripe.enabled')))
									{{ old('stripe.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->stripe->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="stripe[mode]" value="{{ old('stripe.mode', $settings->stripe->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="stripe[client_id]" placeholder="..." value="{{ old('stripe.client_id', $settings->stripe->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="stripe[secret_id]" placeholder="..." value="{{ old('stripe.secret_id', $settings->stripe->secret_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="stripe[fee]" placeholder="..." value="{{ old('stripe.fee', $settings->stripe->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Allowed method types') }}</label>
						<div  class="ui selection multiple search floating dropdown">
							<input type="hidden" name="stripe[method_types]" value="{{ old('stripe.method_types', $settings->stripe->method_types ?? null) }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="card">{{ __('Card') }}</div>
								<div class="item" data-value="bancontact">{{ __('Bancontact') }}</div>
								<div class="item" data-value="alipay">{{ __('Alipay') }}</div>
								<div class="item" data-value="eps">{{ __('EPS') }}</div>
								<div class="item" data-value="fpx">{{ __('FPX') }}</div>
								<div class="item" data-value="giropay">{{ __('Giropay') }}</div>
								<div class="item" data-value="ideal">{{ __('Ideal') }}</div>
								<div class="item" data-value="p24">{{ __('P24') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="stripe[minimum]" placeholder="..." value="{{ old('stripe.minimum', $settings->stripe->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="stripe[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('stripe.auto_exchange_to', $settings->stripe->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- CoinGate -->
			<div class="fluid card" id="coingate">
				<div class="content">
					<h3 class="header">
						<a href="https://coingate.com/" target="_blank"><img src="{{ asset_('assets/images/coingate.png') }}" alt="CoinGate" class="ui small avatar mr-1">{{ __('CoinGate') }}</a>
						<input type="hidden" name="coingate[name]" value="coingate">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="coingate[enabled]"
						    	@if(!empty(old('coingate.enabled')))
									{{ old('coingate.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->coingate->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="coingate[mode]" value="{{ old('coingate.mode', $settings->coingate->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Auth Token') }}</label>
						<input type="text" name="coingate[auth_token]" placeholder="..." value="{{ old('coingate.auth_token', $settings->coingate->auth_token ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Receive currency') }}</label>
						<input type="text" name="coingate[receive_currency]" placeholder="USD" value="{{ old('coingate.receive_currency', $settings->coingate->receive_currency ?? 'USD') }}">
						<small>{{ __('Exchange the received currency to your preferable currency. Default: USD') }} <sup>({{ __('Optional') }})</sup></small>
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="coingate[fee]" placeholder="..." value="{{ old('coingate.fee', $settings->coingate->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="coingate[minimum]" placeholder="..." value="{{ old('coingate.minimum', $settings->coingate->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="coingate[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('coingate.auto_exchange_to', $settings->coingate->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>
			

			<!-- Midtrans -->
			<div class="fluid card" id="midtrans">
				<div class="content">
					<h3 class="header">
						<a href="https://midtrans.com/" target="_blank"><img src="{{ asset_('assets/images/midtrans.png') }}" alt="midtrans" class="ui small avatar mr-1">{{ __('Midtrans') }}</a>
						<input type="hidden" name="midtrans[name]" value="midtrans">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="midtrans[enabled]"
						    	@if(!empty(old('midtrans.enabled')))
									{{ old('midtrans.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->midtrans->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="midtrans[mode]" value="{{ old('midtrans.mode', $settings->midtrans->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Client Key') }}</label>
						<input type="text" name="midtrans[client_key]" placeholder="..." value="{{ old('midtrans.client_key', $settings->midtrans->client_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Server Key') }}</label>
						<input type="text" name="midtrans[server_key]" placeholder="..." value="{{ old('midtrans.server_key', $settings->midtrans->server_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Merchant ID') }}</label>
						<input type="text" name="midtrans[merchant_id]" placeholder="..." value="{{ old('midtrans.merchant_id', $settings->midtrans->merchant_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="midtrans[fee]" placeholder="..." value="{{ old('midtrans.fee', $settings->midtrans->fee ?? null) }}">
					</div>
					
					<div class="field">
						<label>{{ __('Allowed methods') }}</label>
						<div  class="ui selection multiple search floating dropdown">
							<input type="hidden" name="midtrans[methods]" placeholder="..." value="{{ old('midtrans.methods', $settings->midtrans->methods ?? null) }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="credit_card">{{ __('Credit card') }}</div>
								<div class="item" data-value="danamon_online">{{ __('Danamon online') }}</div>
								<div class="item" data-value="cimb_clicks">{{ __('CIMB clicks') }}</div>
								<div class="item" data-value="bca_klikpay">{{ __('BCA klikpay') }}</div>
								<div class="item" data-value="mandiri_clickpay">{{ __('Mandiri clickpay') }}</div>
								<div class="item" data-value="bri_epay">{{ __('BRI epay') }}</div>
								<div class="item" data-value="bca_klikbca">{{ __('BCA klikbca') }}</div>
								<div class="item" data-value="echannel">{{ __('Echannel') }}</div>
								<div class="item" data-value="mandiri_ecash">{{ __('Mandiri ecash') }}</div>
								<div class="item" data-value="permata_va">{{ __('Permata va') }}</div>
								<div class="item" data-value="bca_va">{{ __('Bca va') }}</div>
								<div class="item" data-value="bni_va">{{ __('Bni va') }}</div>
								<div class="item" data-value="other_va">{{ __('Other va') }}</div>
								<div class="item" data-value="indomaret">{{ __('Indomaret') }}</div>
								<div class="item" data-value="alfamart">{{ __('Alfamart') }}</div>
								<div class="item" data-value="akulaku">{{ __('Akulaku') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="midtrans[minimum]" placeholder="..." value="{{ old('midtrans.minimum', $settings->midtrans->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="midtrans[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('midtrans.auto_exchange_to', $settings->midtrans->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- Sslcommerz -->
			<div class="fluid card" id="sslcommerz">
				<div class="content">
					<h3 class="header">
						<a href="https://sslcommerz.com/" target="_blank"><img src="{{ asset_('assets/images/sslcommerz-icon.png') }}" alt="Sslcommerz" class="ui small avatar mr-1">{{ __('sslcommerz') }}</a>
						<input type="hidden" name="sslcommerz[name]" value="sslcommerz">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="sslcommerz[enabled]"
						    	@if(!empty(old('sslcommerz.enabled')))
									{{ old('sslcommerz.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->sslcommerz->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="sslcommerz[mode]" value="{{ old('sslcommerz.mode', $settings->sslcommerz->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Store ID') }}</label>
						<input type="text" name="sslcommerz[store_id]" placeholder="..." value="{{ old('sslcommerz.store_id', $settings->sslcommerz->store_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Store password') }}</label>
						<input type="text" name="sslcommerz[store_passwd]" placeholder="..." value="{{ old('sslcommerz.store_passwd', $settings->sslcommerz->store_passwd ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Use IPN') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="sslcommerz[use_ipn]" value="{{ old('sslcommerz.use_ipn', $settings->sslcommerz->use_ipn ?? '1') }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="1" default>{{ __('Yes') }}</div>
								<div class="item" data-value="0">{{ __('No') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="sslcommerz[fee]" placeholder="..." value="{{ old('sslcommerz.fee', $settings->sslcommerz->fee ?? null) }}">
					</div>
					
					<div class="field">
						<label>{{ __('Allowed methods') }}</label>
						<div  class="ui selection multiple search floating dropdown">
							<input type="hidden" name="sslcommerz[methods]" placeholder="..." value="{{ old('sslcommerz.methods', $settings->sslcommerz->methods ?? null) }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item center aligned header disabled">{{ __('Specific methods') }}</div>
								<div class="item" title="BRAC VISA" data-value="brac_visa">{{ __('Brac_ isa') }}</div>
								<div class="item" title="Dutch Bangla VISA" data-value="dbbl_visa">{{ __('Dbbl visa') }}</div>
								<div class="item" title="City Bank Visa" data-value="city_visa">{{ __('City visa') }}</div>
								<div class="item" title="EBL Visa" data-value="ebl_visa">{{ __('Ebl visa') }}</div>
								<div class="item" title="Southeast Bank Visa" data-value="sbl_visa">{{ __('Sbl visa') }}</div>
								<div class="item" title="BRAC MASTER" data-value="brac_master">{{ __('Brac master') }}</div>
								<div class="item" title="MASTER Dutch-Bangla" data-value="dbbl_master">{{ __('Dbbl master') }}</div>
								<div class="item" title="City Master Card" data-value="city_master">{{ __('City master') }}</div>
								<div class="item" title="EBL Master Card" data-value="ebl_master">{{ __('Ebl master') }}</div>
								<div class="item" title="Southeast Bank Master Card" data-value="sbl_master">{{ __('Sbl master') }}</div>
								<div class="item" title="City Bank AMEX" data-value="city_amex">{{ __('City_amex') }}</div>
								<div class="item" title="QCash" data-value="qcash">{{ __('Qcash') }}</div>
								<div class="item" title="DBBL Nexus" data-value="dbbl_nexus">{{ __('Dbbl nexus') }}</div>
								<div class="item" title="Bank Asia IB" data-value="bankasia">{{ __('Bankasia') }}</div>
								<div class="item" title="AB Bank IB" data-value="abbank">{{ __('Abbank') }}</div>
								<div class="item" title="IBBL IB and Mobile Banking" data-value="ibbl">{{ __('Ibbl') }}</div>
								<div class="item" title="Mutual Trust Bank IB" data-value="mtbl">{{ __('Mtbl') }}</div>
								<div class="item" title="Bkash Mobile Banking" data-value="bkash">{{ __('Bkash') }}</div>
								<div class="item" title="DBBL Mobile Banking" data-value="dbblmobilebanking">{{ __('Dbblmobilebanking') }}</div>
								<div class="item" title="City Touch IB" data-value="city">{{ __('City') }}</div>
								<div class="item" title="Upay" data-value="upay">{{ __('Upay') }}</div>
								<div class="item" title="Tap N Pay Gateway" data-value="tapnpay">{{ __('Tapnpay') }}</div>
								<div class="item center aligned header disabled">{{ __('Global methods') }}</div>
								<div class="item" title="For all internet banking" data-value="internetbank">{{ __('Internetbank') }}</div>
								<div class="item" title="For all mobile banking" data-value="mobilebank">{{ __('Mobilebank') }}</div>
								<div class="item" title="For all cards except visa,master and amex" data-value="othercard">{{ __('Othercard') }}</div>
								<div class="item" title="For all visa" data-value="visacard">{{ __('Visacard') }}</div>
								<div class="item" title="For All Master card" data-value="mastercard">{{ __('Mastercard') }}</div>
								<div class="item" title="For Amex Card" data-value="amexcard">{{ __('Amexcard') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="sslcommerz[minimum]" placeholder="..." value="{{ old('sslcommerz.minimum', $settings->sslcommerz->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="sslcommerz[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('sslcommerz.auto_exchange_to', $settings->sslcommerz->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- RAZOPAY -->
			<div class="fluid card" id="razorpay">
				<div class="content">
					<h3 class="header">
						<a href="https://razorpay.com/" target="_blank"><img src="{{ asset_('assets/images/razorpay_icon.png') }}" alt="razorpay" class="ui small avatar mr-1">{{ __('Razorpay') }}</a>
						<input type="hidden" name="razorpay[name]" value="razorpay">
						
						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="razorpay[enabled]"
						    	@if(!empty(old('razorpay.enabled')))
									{{ old('razorpay.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->razorpay->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Key ID') }}</label>
						<input type="text" name="razorpay[client_id]" placeholder="..." value="{{ old('razorpay.client_id', $settings->razorpay->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Key Secret') }}</label>
						<input type="text" name="razorpay[secret_id]" placeholder="..." value="{{ old('razorpay.secret_id', $settings->razorpay->secret_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Webhook Secret') }}</label>
						<input type="text" name="razorpay[webhook_secret]" placeholder="..." value="{{ old('razorpay.webhook_secret', $settings->razorpay->webhook_secret ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="razorpay[fee]" placeholder="..." value="{{ old('razorpay.fee', $settings->razorpay->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="razorpay[minimum]" placeholder="..." value="{{ old('razorpay.minimum', $settings->razorpay->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="razorpay[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('razorpay.auto_exchange_to', $settings->razorpay->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- IYZICO -->
			<div class="fluid card" id="iyzico">
				<div class="content">
					<h3 class="header">
						<a href="https://www.iyzico.com/" target="_blank"><img src="{{ asset_('assets/images/iyzico_icon.png') }}" alt="iyzico" class="ui small avatar mr-1">{{ __('Iyzico') }}</a>
						<input type="hidden" name="iyzico[name]" value="iyzico">
						
						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="iyzico[enabled]"
						    	@if(!empty(old('iyzico.enabled')))
									{{ old('iyzico.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->iyzico->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="iyzico[mode]" value="{{ old('iyzico.mode', $settings->iyzico->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Key ID') }}</label>
						<input type="text" name="iyzico[client_id]" placeholder="..." value="{{ old('iyzico.client_id', $settings->iyzico->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Key Secret') }}</label>
						<input type="text" name="iyzico[secret_id]" placeholder="..." value="{{ old('iyzico.secret_id', $settings->iyzico->secret_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="iyzico[fee]" placeholder="..." value="{{ old('iyzico.fee', $settings->iyzico->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="iyzico[minimum]" placeholder="..." value="{{ old('iyzico.minimum', $settings->iyzico->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="iyzico[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('iyzico.auto_exchange_to', $settings->iyzico->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- SKRILL -->
			<div class="fluid card" id="skrill">
				<div class="content">
					<h3 class="header">
						<a href="https://www.skrill.com/" target="_blank"><img src="{{ asset_('assets/images/skrill_icon.png') }}" alt="Skrill" class="ui small avatar mr-1">{{ __('Skrill') }}</a>
						<input type="hidden" name="skrill[name]" value="skrill">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="skrill[enabled]"
						    	@if(!empty(old('skrill.enabled')))
									{{ old('skrill.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->skrill->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Merchant account') }}</label>
						<input type="text" name="skrill[merchant_account]" placeholder="..." value="{{ old('skrill.merchant_account', $settings->skrill->merchant_account ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('MQI/API secret word') }}</label>
						<input type="text" name="skrill[mqiapi_secret_word]" placeholder="..." value="{{ old('skrill.mqiapi_secret_word', $settings->skrill->mqiapi_secret_word ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('MQI/API password') }}</label>
						<input type="text" name="skrill[mqiapi_password]" placeholder="..." value="{{ old('skrill.mqiapi_password', $settings->skrill->mqiapi_password ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Allowed payment methods') }}</label>
						<div  class="ui selection scrolling search multiple floating dropdown">
							<input type="hidden" name="skrill[methods]" placeholder="..." value="{{ old('skrill.methods', $settings->skrill->methods ?? null) }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="WLT">{{ __('Skrill Digital Wallet') }}</div>
								<div class="item" data-value="NTL">{{ __('Neteller') }}</div>
								<div class="item" data-value="PSC">{{ __('Paysafecard') }}</div>
								<div class="item" data-value="PCH">{{ __('Paysafecash') }}</div>
								<div class="item" data-value="ACC">{{ __('All card types available in the customer’s country') }}</div>
								<div class="item" data-value="VSA">{{ __('Visa') }}</div>
								<div class="item" data-value="MSC">{{ __('Mastercard') }}</div>
								<div class="item" data-value="VSE">{{ __('Visa Electron') }}</div>
								<div class="item" data-value="MAE">{{ __('Maestro') }}</div>
								<div class="item" data-value="GCB">{{ __('Carte Bleue') }}</div>
								<div class="item" data-value="DNK">{{ __('Dankort') }}</div>
								<div class="item" data-value="PSP">{{ __('PostePay') }}</div>
								<div class="item" data-value="CSI">{{ __('CartaSi') }}</div>
								<div class="item" data-value="ACH">{{ __('iACH') }}</div>
								<div class="item" data-value="GCI">{{ __('iDEAL GCI') }}</div>
								<div class="item" data-value="IDL">{{ __('iDEAL IDL') }}</div>
								<div class="item" data-value="PWY">{{ __('Przelewy24') }}</div>
								<div class="item" data-value="GLU">{{ __('Trustly') }}</div>
								<div class="item" data-value="ALI">{{ __('Alipay') }}</div>
								<div class="item" data-value="ADB">{{ __('Astropay - Online bank transfer (Direct Bank Transfer)') }}</div>
								<div class="item" data-value="AOB">{{ __('Astropay - Offline bank transfer') }}</div>
								<div class="item" data-value="ACI">{{ __('Astropay - Cash (Invoice)') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="skrill[fee]" placeholder="..." value="{{ old('skrill.fee', $settings->skrill->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="skrill[minimum]" placeholder="..." value="{{ old('skrill.minimum', $settings->skrill->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="skrill[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('skrill.auto_exchange_to', $settings->skrill->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>
			

			<!-- PAYSTACK -->
			<div class="fluid card" id="paystack">
				<div class="content">
					<h3 class="header">
						<a href="https://paystack.com/" target="_blank"><img src="{{ asset_('assets/images/paystack.png') }}" alt="Paystack" class="ui small avatar mr-1">{{ __('Paystack') }}</a>
						<input type="hidden" name="paystack[name]" value="paystack">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="paystack[enabled]"
						    	@if(!empty(old('paystack.enabled')))
									{{ old('paystack.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->paystack->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">

					<div class="field">
						<label>{{ __('Public Key') }}</label>
						<input type="text" name="paystack[public_key]" placeholder="..." value="{{ old('paystack.public_key', $settings->paystack->public_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret key') }}</label>
						<input type="text" name="paystack[secret_key]" placeholder="..." value="{{ old('paystack.secret_key', $settings->paystack->secret_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="paystack[fee]" placeholder="..." value="{{ old('paystack.fee', $settings->paystack->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Allowed channels') }}</label>
						<div  class="ui selection multiple floating dropdown">
							<input type="hidden" name="paystack[channels]" placeholder="..." value="{{ old('paystack.channels', $settings->paystack->channels ?? null) }}">
							<div class="default text">...</div>
							<div class="menu">
								<div class="item" data-value="card">{{ __('Card') }}</div>
								<div class="item" data-value="bank">{{ __('Bank') }}</div>
								<div class="item" data-value="ussd">{{ __('USSD') }}</div>
								<div class="item" data-value="qr">{{ __('QR') }}</div>
								<div class="item" data-value="mobile_money">{{ __('MOBILE MONEY') }}</div>
								<div class="item" data-value="bank_transfer">{{ __('BANK TRANSFER') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="paystack[minimum]" placeholder="..." value="{{ old('paystack.minimum', $settings->paystack->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="paystack[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('paystack.auto_exchange_to', $settings->paystack->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- ADYEN -->
			<div class="fluid card" id="adyen">
				<div class="content">
					<h3 class="header">
						<a href="https://www.adyen.com/" target="_blank"><img src="{{ asset_('assets/images/adyen.jpg') }}" alt="Adyen" class="ui small avatar mr-1">{{ __('Adyen') }}</a>
						<input type="hidden" name="adyen[name]" value="adyen">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="adyen[enabled]"
						    	@if(!empty(old('adyen.enabled')))
									{{ old('adyen.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->adyen->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="adyen[mode]" value="{{ old('adyen.mode', $settings->adyen->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('API key') }}</label>
						<input type="text" name="adyen[api_key]" placeholder="..." value="{{ old('adyen.api_key', $settings->adyen->api_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Client key') }}</label>
						<input type="text" name="adyen[client_key]" placeholder="..." value="{{ old('adyen.client_key', $settings->adyen->client_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Merchant account') }}</label>
						<input type="text" name="adyen[merchant_account]" placeholder="..." value="{{ old('adyen.merchant_account', $settings->adyen->merchant_account ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('HMAC key') }}</label>
						<input type="text" name="adyen[hmac_key]" placeholder="..." value="{{ old('adyen.hmac_key', $settings->adyen->hmac_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="adyen[fee]" placeholder="..." value="{{ old('adyen.fee', $settings->adyen->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="adyen[minimum]" placeholder="..." value="{{ old('adyen.minimum', $settings->adyen->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="adyen[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('adyen.auto_exchange_to', $settings->adyen->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- INSTAMOJO -->
			<div class="fluid card" id="instamojo">
				<div class="content">
					<h3 class="header">
						<a href="https://www.instamojo.com/" target="_blank"><img src="{{ asset_('assets/images/instamojo.png') }}" alt="Instamojo" class="ui small avatar mr-1">{{ __('Instamojo') }}</a>
						<input type="hidden" name="instamojo[name]" value="instamojo">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input
						    	type="checkbox" 
						    	name="instamojo[enabled]"
						    	@if(!empty(old('instamojo.enabled')))
									{{ old('instamojo.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->instamojo->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="instamojo[mode]" value="{{ old('instamojo.mode', $settings->instamojo->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Private API Key') }}</label>
						<input type="text" name="instamojo[private_api_key]" placeholder="..." value="{{ old('instamojo.private_api_key', $settings->instamojo->private_api_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Private Auth Token') }}</label>
						<input type="text" name="instamojo[private_auth_token]" placeholder="..." value="{{ old('instamojo.private_auth_token', $settings->instamojo->private_auth_token ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Private Salt') }}</label>
						<input type="text" name="instamojo[private_salt]" placeholder="..." value="{{ old('instamojo.private_salt', $settings->instamojo->private_salt ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="instamojo[fee]" placeholder="..." value="{{ old('instamojo.fee', $settings->instamojo->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="instamojo[minimum]" placeholder="..." value="{{ old('instamojo.minimum', $settings->instamojo->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="instamojo[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('instamojo.auto_exchange_to', $settings->instamojo->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- PAYHERE -->
			<div class="fluid card" id="payhere">
				<div class="content">
					<h3 class="header">
						<a href="https://www.payhere.com/" target="_blank"><img src="{{ asset_('assets/images/payhere.png') }}" class="ui small avatar mr-1">{{ __('Payhere') }}</a>
						<input type="hidden" name="payhere[name]" value="payhere">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="payhere[enabled]"
						    	@if(!empty(old('payhere.enabled')))
									{{ old('payhere.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->payhere->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>

					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="payhere[mode]" value="{{ old('payhere.mode', $settings->payhere->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Merchant secret') }}</label>
						<input type="text" name="payhere[merchant_secret]" placeholder="..." value="{{ old('payhere.merchant_secret', $settings->payhere->merchant_secret ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Merchant ID') }}</label>
						<input type="text" name="payhere[merchant_id]" placeholder="..." value="{{ old('payhere.merchant_id', $settings->payhere->merchant_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="payhere[fee]" placeholder="..." value="{{ old('payhere.fee', $settings->payhere->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="payhere[minimum]" placeholder="..." value="{{ old('payhere.minimum', $settings->payhere->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="payhere[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('payhere.auto_exchange_to', $settings->payhere->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- SPANKPAY -->
			<div class="fluid card" id="spankpay">
				<div class="content">
					<h3 class="header">
						<a href="https://spankpay.com/" target="_blank"><img src="{{ asset_('assets/images/spankpay_icon.png') }}" alt="Spankpay" class="ui small avatar mr-1">{{ __('Spankpay') }}</a>
						<input type="hidden" name="spankpay[name]" value="spankpay">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="spankpay[enabled]"
						    	@if(!empty(old('spankpay.enabled')))
									{{ old('spankpay.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->spankpay->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Client key') }}</label>
						<input type="text" name="spankpay[public_key]" placeholder="..." value="{{ old('spankpay.public_key', $settings->spankpay->public_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret key') }}</label>
						<input type="text" name="spankpay[secret_key]" placeholder="..." value="{{ old('spankpay.secret_key', $settings->spankpay->secret_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="spankpay[fee]" placeholder="..." value="{{ old('spankpay.fee', $settings->spankpay->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="spankpay[minimum]" placeholder="..." value="{{ old('spankpay.minimum', $settings->spankpay->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="spankpay[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('spankpay.auto_exchange_to', $settings->spankpay->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			<!-- OMISE -->
			<div class="fluid card" id="omise">
				<div class="content">
					<h3 class="header">
						<a href="https://omise.com/" target="_blank"><img src="{{ asset_('assets/images/omise_icon.png') }}" alt="omise" class="ui small avatar mr-1">{{ __('omise') }}</a>
						<input type="hidden" name="omise[name]" value="omise">
						
						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="omise[enabled]"
						    	@if(!empty(old('omise.enabled')))
									{{ old('omise.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->omise->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Public key') }}</label>
						<input type="text" name="omise[public_key]" placeholder="..." value="{{ old('omise.public_key', $settings->omise->public_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret key') }}</label>
						<input type="text" name="omise[secret_key]" placeholder="..." value="{{ old('omise.secret_key', $settings->omise->secret_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="omise[fee]" placeholder="..." value="{{ old('omise.fee', $settings->omise->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="omise[minimum]" placeholder="..." value="{{ old('omise.minimum', $settings->omise->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="omise[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('omise.auto_exchange_to', $settings->omise->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>


			
			<!-- PAYMENTWALL -->
			<div class="fluid card" id="paymentwall">
				<div class="content">
					<h3 class="header">
						<a href="https://paymentwall.com/" target="_blank"><img src="{{ asset_('assets/images/paymentwall_icon.png') }}" alt="paymentwall" class="ui small avatar mr-1">{{ __('Paymentwall') }}</a>
						<input type="hidden" name="paymentwall[name]" value="paymentwall">
						
						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="paymentwall[enabled]"
						    	@if(!empty(old('paymentwall.enabled')))
									{{ old('paymentwall.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->paymentwall->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
				<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="paymentwall[mode]" value="{{ old('paymentwall.mode', $settings->paymentwall->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('Project key') }}</label>
						<input type="text" name="paymentwall[project_key]" placeholder="..." value="{{ old('paymentwall.project_key', $settings->paymentwall->project_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret key') }}</label>
						<input type="text" name="paymentwall[secret_key]" placeholder="..." value="{{ old('paymentwall.secret_key', $settings->paymentwall->secret_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="paymentwall[fee]" placeholder="..." value="{{ old('paymentwall.fee', $settings->paymentwall->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="paymentwall[minimum]" placeholder="..." value="{{ old('paymentwall.minimum', $settings->paymentwall->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="paymentwall[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('paymentwall.auto_exchange_to', $settings->paymentwall->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>



			<!-- AUTHORIZE.NET -->
			<div class="fluid card" id="authorize-net">
				<div class="content">
					<h3 class="header">
						<a href="https://authorize.net/" target="_blank"><img src="{{ asset_('assets/images/authorize_net_icon.png') }}" alt="authorize_net" class="ui small avatar mr-1">{{ __('Authorize.Net') }}</a>
						<input type="hidden" name="authorize_net[name]" value="authorize_net">
						
						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="authorize_net[enabled]"
						    	@if(!empty(old('authorize_net.enabled')))
									{{ old('authorize_net.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->authorize_net->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
				<div class="field">
						<label>{{ __('Mode') }}</label>
						<div  class="ui selection floating dropdown">
							<input type="hidden" name="authorize_net[mode]" value="{{ old('authorize_net.mode', $settings->authorize_net->mode ?? 'sandbox') }}">
							<div class="default text">{{ __('Select Mode') }}</div>
							<div class="menu">
								<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
								<div class="item" data-value="live">{{ __('Live') }}</div>
							</div>
						</div>
					</div>

					<div class="field">
						<label>{{ __('API login ID') }}</label>
						<input type="text" name="authorize_net[api_login_id]" placeholder="..." value="{{ old('authorize_net.api_login_id', $settings->authorize_net->api_login_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Client key') }}</label>
						<input type="text" name="authorize_net[client_key]" placeholder="..." value="{{ old('authorize_net.client_key', $settings->authorize_net->client_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction key') }}</label>
						<input type="text" name="authorize_net[transaction_key]" placeholder="..." value="{{ old('authorize_net.transaction_key', $settings->authorize_net->transaction_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Signature key') }}</label>
						<input type="text" name="authorize_net[signature_key]" placeholder="..." value="{{ old('authorize_net.signature_key', $settings->authorize_net->signature_key ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Transaction Fee') }}</label>
						<input type="number" step="0.01" name="authorize_net[fee]" placeholder="..." value="{{ old('authorize_net.fee', $settings->authorize_net->fee ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
						<input type="number" step="0.01" name="authorize_net[minimum]" placeholder="..." value="{{ old('authorize_net.minimum', $settings->authorize_net->minimum ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Auto exchange currency to') }} <sup>(5)</sup></label>
						<input type="text" name="paymentwall[auto_exchange_to]" placeholder="{{ __('Currency code') }}" value="{{ old('authorize_net.auto_exchange_to', $settings->authorize_net->auto_exchange_to ?? null) }}">
					</div>
				</div>
			</div>
		</div>

		<!-- OFFLINE PAYMENT -->
		<div class="ui fluid card" id="offline">
			<div class="content" id="offline">
				<h3 class="header">
					<span>{{ __('Offline payment') }}</span>
					<input type="hidden" name="offline[name]" value="offline">
					
					<div class="checkbox-wrapper">
						<div class="ui fitted toggle checkbox">
					    <input 
					    	type="checkbox" 
					    	name="offline[enabled]"
					    	@if(!empty(old('offline.enabled')))
								{{ old('offline.enabled') ? 'checked' : '' }}
								@else
								{{ ($settings->offline->enabled ?? null) ? 'checked' : '' }}
					    	@endif
					    >
					    <label></label>
					  </div>
					</div>

				</h3>
			</div>
			
			<div class="content" id="offline-payment-instruction">
				<div class="field">
					<label>{{ __('Instructions') }}</label>
					<textarea name="offline[instructions]" class="summernote" cols="30" rows="10">{{ old('offline.instructions', $settings->offline->instructions ?? null)}}</textarea>
				</div>

				<div class="field">
					<label>{{ __('Transaction Fee') }}</label>
					<input type="number" step="0.01" name="offline[fee]" placeholder="..." value="{{ old('offline.fee', $settings->offline->fee ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
					<input type="number" step="0.01" name="offline[minimum]" placeholder="..." value="{{ old('offline.minimum', $settings->offline->minimum ?? null) }}">
				</div>
			</div>
		</div>

		<!-- YOOMONEY -->
		{{-- <div class="ui fluid card mt-1" id="yoomoney">
			<div class="content">
				<h3 class="header">
					<a href="https://yoomoney.ru/" target="_blank"><img src="{{ asset_('assets/images/yoomoney_icon.png') }}" alt="yoomoney" class="ui small avatar mr-1">YooMoney / Yookassa</a>
					<input type="hidden" name="yoomoney[name]" value="yoomoney">
					
					<div class="checkbox-wrapper">
						<div class="ui fitted toggle checkbox">
					    <input 
					    	type="checkbox" 
					    	name="yoomoney[enabled]"
					    	@if(!empty(old('yoomoney.enabled')))
								{{ old('yoomoney.enabled') ? 'checked' : '' }}
								@else
								{{ ($settings->yoomoney->enabled ?? null) ? 'checked' : '' }}
					    	@endif
					    >
					    <label></label>
					  </div>
					</div>
				</h3>
			</div>

			<div class="content">
				<div class="field">
					<label>{{ __('Shop id') }}</label>
					<input type="text" name="yoomoney[shop_id]" placeholder="..." value="{{ old('yoomoney.shop_id', $settings->yoomoney->shop_id ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Secret key') }}</label>
					<input type="text" name="yoomoney[secret_key]" placeholder="..." value="{{ old('yoomoney.secret_key', $settings->yoomoney->secret_key ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Transaction Fee') }}</label>
					<input type="number" step="0.01" name="yoomoney[fee]" placeholder="..." value="{{ old('yoomoney.fee', $settings->yoomoney->fee ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
					<input type="number" step="0.01" name="yoomoney[minimum]" placeholder="..." value="{{ old('yoomoney.minimum', $settings->yoomoney->minimum ?? null) }}">
				</div>
			</div>
		</div> --}}


		<!-- COINPAYMENTS -->
		{{--< div class="ui fluid card" id="coinpayments">
			<div class="content">
				<h3 class="header">
					<a href="https://www.coinpayments.net/" target="_blank"><img src="{{ asset_('assets/images/coinpayments.ico') }}" class="ui small avatar mr-1">Coinpayments</a>
					<input type="hidden" name="coinpayments[name]" value="coinpayments">

					<div class="checkbox-wrapper">
						<div class="ui fitted toggle checkbox">
					    <input 
					    	type="checkbox" 
					    	name="coinpayments[enabled]"
					    	@if(!empty(old('coinpayments.enabled')))
								{{ old('coinpayments.enabled') ? 'checked' : '' }}
								@else
								{{ ($settings->coinpayments->enabled ?? null) ? 'checked' : '' }}
					    	@endif
					    >
					    <label></label>
					  </div>
					</div>

				</h3>
			</div>

			<div class="content">
				<div class="field">
					<label>{{ __('Mode') }}</label>
					<div  class="ui selection floating dropdown">
						<input type="hidden" name="coinpayments[mode]" value="{{ old('coinpayments.mode', $settings->coinpayments->mode ?? 'sandbox') }}">
						<div class="default text">{{ __('Select Mode') }}</div>
						<div class="menu">
							<div class="item" data-value="sandbox" default>{{ __('Sandbox') }}</div>
							<div class="item" data-value="live">{{ __('Live') }}</div>
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Public key') }}</label>
					<input type="text" name="coinpayments[public_key]" placeholder="..." value="{{ old('coinpayments.public_key', $settings->coinpayments->public_key ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Private key') }}</label>
					<input type="text" name="coinpayments[private_key]" placeholder="..." value="{{ old('coinpayments.private_key', $settings->coinpayments->private_key ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Merchant ID') }}</label>
					<input type="text" name="coinpayments[merchant_id]" placeholder="..." value="{{ old('coinpayments.merchant_id', $settings->coinpayments->merchant_id ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Transaction Fee') }}</label>
					<input type="number" step="0.01" name="coinpayments[fee]" placeholder="..." value="{{ old('coinpayments.fee', $settings->coinpayments->fee ?? null) }}">
				</div>

				<div class="field">
					<label>{{ __('Mimimun amount') }} <sup><i class="exclamation circle blue icon m-0" title="{{ __('For Pay what you want') }}"></i></sup></label>
					<input type="number" step="0.01" name="coinpayments[minimum]" placeholder="..." value="{{ old('coinpayments.minimum', $settings->coinpayments->minimum ?? null) }}">
				</div>
			</div>
		</div> --}}

		<div class="ui fluid blue segment rounded-corner">
			<div class="five fields mt-1">
				<div class="field" id="vat">
					<label>{{ __('VAT') }} (%)</label>
					<input type="number" step="0.01" name="vat" value="{{ old('vat', $settings->vat ?? null) }}">
				</div>

				<div class="field" id="currency-code">
					<label>{{ __('Main currency code') }}</label>
					<input type="text" name="currency_code" value="{{ old('currency_code', $settings->currency_code ?? null) }}">
				</div>

				<div class="field" id="main-currency-symbol">
					<label>{{ __('Main currency symbol') }}</label>
					<input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings->currency_symbol ?? null) }}">
				</div>

				<div class="field" id="currency_position">
					<label>{{ __('Currency position') }} <sup>(1)</sup></label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="currency_position" value="{{ old('currency_position', $settings->currency_position ?? 'left') }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-text="{{ __('Left') }}" data-value="left">{{ __('Left') }}</a>
							<a class="item" data-text="{{ __('Right') }}" data-value="right">{{ __('Right') }}</a>
						</div>
					</div>
				</div>

				<div class="field" id="allow-foreign-currencies">
					<label>{{ __('Allow foreign currencies') }} <sup>(1)</sup></label>
					<div class="ui selection floating dropdown">
						<input type="hidden" name="allow_foreign_currencies" value="{{ old('allow_foreign_currencies', $settings->allow_foreign_currencies ?? null) }}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-text="{{ __('Yes') }}" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-text="{{ __('No') }}" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
			</div>

			<div class="field">
				<small>(1) : {{ __('Allow receiving payments in defferent currencies than the main currency.') }}</small>
			</div>

			<div class="field" id="currencies">
				<label>{{ __('Currencies') }}</label>
				<div class="ui fluid multiple search selection floating dropdown">
					<input type="hidden" name="currencies" value="{{ strtolower(old('currencies', $settings->currencies ?? null)) }}">
					<div class="text">...</div>
					<div class="menu">
						@foreach($currencies ?? [] as $code => $currency)
						<a class="item" data-text="{{ $code }}">{{ $code }}</a>
						@endforeach
					</div>
				</div>
			</div>

			<div class="field" id="currency-exchange">
				<label>{{ __('Currency exchange API') }}</label>
				<div class="ui selection floating dropdown">
					<input type="hidden" name="currency_exchange_api" value="{{ old('currency_exchange_api', $settings->currency_exchange_api ?? null) }}">
					<div class="text"></div>
					<div class="menu">
						<a class="item"></a>
						<a class="item" data-value="api.exchangeratesapi.io">api.exchangeratesapi.io</a>
						<a class="item" data-value="api.currencyscoop.com">api.currencyscoop.com</a>
						<a class="item" data-value="api.exchangerate.host" data-text="api.exchangerate.host">api.exchangerate.host <sup>{{ __('Supports cryptocurrency') }}</sup></a>
						<a class="item" data-value="api.coingate.com" data-text="api.coingate.com">api.coingate.com <sup>{{ __('Supports cryptocurrency') }}</sup></a>
					</div>
				</div>
				<small>
					<a href="https://exchangeratesapi.io" target="_blank">api.exchangeratesapi.io</a>
					<a class="ml-1" href="https://currencyscoop.com" target="_blank">api.currencyscoop.com</a>
					<a class="ml-1" href="https://exchangerate.host" target="_blank">api.exchangerate.host</a>
					<a class="ml-1" href="https://coingate.com" target="_blank">api.coingate.com</a>
				</small>
			</div>
            
            <div class="field" id="currency-exchanger-api-key">
				<label>{{ __(':name API key', ['name' => 'Api.exchangeratesapi.io']) }}</label>
				<input type="text" name="exchangeratesapi_io_key" value="{{ old('exchangeratesapi_io_key', $settings->exchangeratesapi_io_key ?? null) }}">
				<small>{{ __('Required if api.exchangeratesapi.io is selected.') }}</small>
			</div>
			
			<div class="field" id="currency-exchanger-api-key">
				<label>{{ __(':name API key', ['name' => 'Api.currencyscoop.com']) }}</label>
				<input type="text" name="currencyscoop_api_key" value="{{ old('currencyscoop_api_key', $settings->currencyscoop_api_key ?? null) }}">
				<small>{{ __('Required if api.currencyscoop.com is selected.') }}</small>
			</div>
			
			<div class="field" id="allow-guest-checkout">
				<label>{{ __('Allow guest checkout') }} <sup>(3)</sup></label>
				<div class="ui fluid selection floating dropdown">
					<input type="hidden" name="guest_checkout" value="{{ old('guest_checkout', $settings->guest_checkout ?? null)}}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
				<small>(3) {{ __('Allow users to make purchases without being logged in.') }}</small>
			</div>


			<div class="two fields" id="pay-what-you-want">
				<div class="field">
					<label>{{ __('Enable « Pay What You Want »') }} <sup>(4)</sup></label>
				  <div class="ui selection floating dropdown left-circular-corner">
						<input type="hidden" name="pay_what_you_want[enabled]" value="{{ old('pay_what_you_want.enabled', $settings->pay_what_you_want->enabled ?? '0')}}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
				<div class="field">
					<label>{{ __('For') }}</label>
					<div class="ui selection multiple floating dropdown right-circular-corner">
						<input type="hidden" name="pay_what_you_want[for]" value="{{ old('pay_what_you_want.for', $settings->pay_what_you_want->for ?? null)}}">
						<div class="text">...</div>
						<div class="menu">
							<a class="item" data-value="products">{{ __('Products') }}</a>
							<a class="item" data-value="subscriptions">{{ __('Subscriptions') }}</a>
						</div>
					</div>
				</div>
			</div>
			<small>(4) {{ __('Allow users to pay what they want for products with an optional minimum amount.') }}</small>

			<div class="field mt-1">
				<label>{{ __('Change user currency based on his country') }}</label>
			  <div class="ui selection floating dropdown left-circular-corner">
					<input type="hidden" name="currency_by_country" value="{{ old('currency_by_country', $settings->currency_by_country ?? '0')}}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>

			<small>(5) : {{ __('Change the currency to this currency when a user proceed to the checkout, regardless his selected currrency.') }}</small>
		</div>

	</div>
</form>

<script>
	'use strict';

	$(function()
  {
		$('.summernote').summernote({
	    placeholder: '...',
	    tabsize: 2,
	    height: 150,
	    tooltip: false
	  });

	  $('#disable-all-services').on('click', function()
	  {
	  	$('#settings input[type="checkbox"]').prop('checked', false);
	  })

	  $('#settings input, #settings textarea').on('keydown', function(e) 
		{
		    if((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
		    {		        
		        $('form.main').submit();

		  			e.preventDefault();

		        return false;
		    }
		    else
		    {
		        return true;
		    }
		})
	})
</script>

@endsection