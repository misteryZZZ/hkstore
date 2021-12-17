@extends('back.master')

@section('title', __('Edit coupon'))


@section('content')
<form class="ui large form" method="post" action="{{ route('coupons.update', $coupon->id) }}" id="coupon" spellcheck="false">
	@csrf

	<div class="field">
		<button class="ui icon labeled large pink circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Update') }}
		</button>
		<a class="ui icon labeled large yellow circular button" href="{{ route('coupons') }}">
			<i class="times icon"></i>
			{{ __('Cancel') }}
		</a>
	</div>
	
	@if($errors->any())
    @foreach ($errors->all() as $error)
		<div class="ui negative fluid small message">
			<i class="times icon close"></i>
			{{ $error }}
		</div>
    @endforeach
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid">
		<div class="column">

			<div class="field" id="coupon-code">
				<label>{{ __('Code') }}</label>
				<div class="ui right action input">
				  <input type="text" name="code" placeholder="..." value="{{ old('code', $coupon->code) }}" autofocus required>
				  <button class="ui teal button" type="button">{{ __('Generate') }}</button>
				</div>
			</div>

			<div class="field">
				<label>{{ __('Value') }}</label>
				<input type="number" step="0.01" name="value" placeholder="..." value="{{ old('value', $coupon->value) }}" required>
			</div>

			<div class="field">
				<label>{{ __('Is percentage') }}</label>
				<div class="ui fluid selection dropdown floating">
					<input type="hidden" name="is_percentage" value="{{ old('is_percentage', $coupon->is_percentage ? '1' : '0') }}">
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>

			<div class="field">
				<label>{{ __('For') }}</label>
				<div class="ui fluid dropdown selection floating">
					<input type="hidden" name="for" value="{{ old('for', $coupon->for ?? 'products') }}">
					<div class="text"></div>
					<div class="menu">
						<a class="item" data-value="products">{{ __('Products') }}</a>
						<a class="item" data-value="subscriptions">{{ __('Subscriptions') }}</a>
					</div>
				</div>
			</div>

			<div class="ui segment rounded-corner items products">
				<div class="field">
					<label>{{ __('Products') }}</label>
					<div class="ui multiple search selection dropdown floating">
						<input type="hidden" name="products_ids" value="{{ old('products_ids', str_replace("'", '', $coupon->products_ids)) }}">
						<i class="dropdown icon"></i>
						<div class="text"></div>
						<div class="menu">
						@foreach($products as $product)
							<div class="item" data-value="{{ $product->id }}">{{ $product->name }}</div>
						@endforeach
						</div>
					</div>
				</div>

				<div class="field">
					<label>{{ __('Valid for regular licenses only') }}</label>
					<div class="ui selection dropdown floating">
						<input type="hidden" name="regular_license_only" value="{{ old('regular_license_only', $coupon->regular_license_only ?? '0') }}">
						<div class="text"></div>
						<i class="dropdown icon"></i>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
			</div>

			<div class="ui segment rounded-corner items subscriptions d-none">
				<div class="field">
					<label>{{ __('Subscriptions') }}</label>
					<div class="ui multiple search selection dropdown floating">
						<input type="hidden" name="subscriptions_ids" value="{{ old('subscriptions_ids', $coupon->subscriptions_ids) }}">
						<i class="dropdown icon"></i>
						<div class="text"></div>
						<div class="menu">
						@foreach($subscriptions as $subscription)
							<div class="item" data-value="{{ $subscription->id }}">{{ $subscription->name }}</div>
						@endforeach
						</div>
					</div>
				</div>
			</div>

			<div class="field">
				<label>{{ __('Users ids') }}</label>
				<select class="ui fluid  dropdown users" name="users_ids" multiple>
					@foreach($users as $user)
					<option value="{{ $user->id }}">{{ $user->email }}</option>
					@endforeach
				</select>
			</div>


			<div class="field">
				<label>{{ __('Allow one time use per user') }}</label>
				<div class="ui selection dropdown floating">
					<input type="hidden" name="once" value="{{ old('once', $coupon->once) }}">
					<div class="text"></div>
					<i class="dropdown icon"></i>
					<div class="menu">
						<a class="item" data-value="1">{{ __('Yes') }}</a>
						<a class="item" data-value="0">{{ __('No') }}</a>
					</div>
				</div>
			</div>


			<div class="field">
				<label>{{ __('Starts at') }}</label>
				<input type="datetime-local" name="starts_at" required value="{{ old('starts_at', $coupon->starts_at) }}">
			</div>
			

			<div class="field">
				<label>{{ __('Expires at') }}</label>
				<input type="datetime-local" name="expires_at" required value="{{ old('expires_at', $coupon->expires_at) }}">
			</div>

		</div>
	</div>
</form>

<script>
	$(function()
	{
		'use strict';

		$('#coupon').on('submit', function(e)
		{
			if($('#coupon input[name="value"]').val() <= 0)
			{
				$('#coupon input[name="value"]').focus();
				e.preventDefault();
				return false;
			}
		})

		$('#coupon input[name="value"]').on('change', function()
		{
			$(this).toggleClass('error', !($(this).val() > 0));
		})

		$('#coupon-code button').on('click', function()
		{
			$.post('{{ route('coupons.generate') }}', null, null, 'json')
			.done(function(coupon)
			{
				$('#coupon-code input').val(coupon.code);
			})
		})

		$('.ui.dropdown.users').dropdown('set selected', {{ old('users_ids', str_replace("'", '', $coupon->users_ids)) }})
		
		$('input[name="for"]').on('change', function()
		{
			$('.items.' + $(this).val()).toggleClass('d-none', false)
																				.siblings('.items').toggleClass('d-none', true).find('.selection').dropdown('clear');
		})

		if($('input[name="for"]').val().length)
		{
			$('.items.' + $('input[name="for"]').val()).toggleClass('d-none', false).siblings('.items').toggleClass('d-none', true);
		}
	})
</script>

@endsection