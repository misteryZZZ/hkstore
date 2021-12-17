@extends('back.master')

@section('title', __('Affiliate settings'))

@section('additional_head_tags')

<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>

@endsection


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'affiliate') }}">

	<div class="field">
		<button type="submit" class="ui pink large circular labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Update') }}
		</button>
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
		<div class="column">
			<div class="field" id="affiliate">
				<label>{{ __('Enable') }}</label>
				<div class="ui dropdown floating selection">
					<input type="hidden" name="affiliate[enabled]" value="{{ old('enabled', $settings->enabled ?? '0') }}">
					<div class="default text">...</div>
					<div class="menu">
						<div class="item" data-value="1">{{ __('Yes') }}</div>
						<div class="item" data-value="0">{{ __('No') }}</div>
					</div>
				</div>
			</div>
			
			<div class="field" id="affiliate-commission">
				<label>{{ __('Commission in %') }}</label>
				<input type="number" step="0.001" name="affiliate[commission]" value="{{ old('commission', $settings->commission ?? '0') }}">
			</div>

			<div class="field" id="affiliate-cookie">
				<label>{{ __('Cookie expiration time') }} <sup>({{ __('In days') }})</sup></label>
				<input type="number" name="affiliate[expire]" value="{{ old('expire', $settings->expire ?? '30') }}">
			</div>

			<div class="field" id="affiliate">
				<label>{{ __('Cashout methods') }}</label>
				<div class="ui dropdown multiple search floating selection">
					<input type="hidden" name="affiliate[cashout_methods]" value="{{ old('cashout_methods', $settings->cashout_methods ?? 'paypal_account') }}">
					<div class="default text">...</div>
					<div class="menu">
						<div class="item" data-value="paypal_account">{{ __('PayPal account') }}</div>
						<div class="item" data-value="bank_account">{{ __('Bank transfer') }}</div>
					</div>
				</div>
			</div>

			<div class="field" id="minimum-cashout-paypal">
				<label>{{ __('Minimum PayPal Cashout') }} <sup>({{ __(config('payments.currency_code')) }})</sup></label>
				<input type="number" name="affiliate[minimum_cashout][paypal]" value="{{ old('minimum_cashout.paypal', $settings->minimum_cashout->paypal ?? null) }}">
			</div>
			
			<div class="field" id="minimum-cashout-bank-transfer">
				<label>{{ __('Minimum Bank Transfer Cashout') }} <sup>({{ __(config('payments.currency_code')) }})</sup></label>
				<input type="number" name="affiliate[minimum_cashout][bank_transfer]" value="{{ old('minimum_cashout.bank_transfer', $settings->minimum_cashout->bank_transfer ?? null) }}">
			</div>
			
			<div class="field" id="affiliate-cashout">
				<label>{{ __('How cashout works') }}</label>
				<textarea name="affiliate[cashout_description]" class="summernote" rows="4" placeholder="...">{{ old('cashout_description', $settings->cashout_description ?? null) }}</textarea>
			</div>
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
	    height: 250,
	    tooltip: false
	  });

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