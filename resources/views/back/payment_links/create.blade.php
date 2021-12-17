@extends('back.master')

@section('title', __('Create payment link'))

@section('content')
<form class="ui large form" method="post" action="{{ route('payment_links.store') }}" enctype="multipart/form-data">
	@csrf

	<div class="field">
		<button class="ui icon labeled pink large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('payment_links') }}">
			<i class="times icon"></i>
			{{ __('Cancel') }}
		</a>
	</div>
	
	@if($errors->any())
    @foreach($errors->all() as $error)
		<div class="ui negative fluid small message">
			<i class="close icon"></i>
			{{ $error }}
		</div>
    @endforeach
	@endif

	@if(session('user_message'))
	<div class="ui fluid small message">
		<i class="close icon"></i>
		{{ session('user_message') }}
	</div>
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid" id="payment-link">
		<div class="column">
			<div class="field">
				<label>{{ __('Name') }}</label>
			  <input type="text" name="name" value="{{ old('name') }}">
			</div>

			<div class="field">
				<label>{{ __('User') }}</label>
				<div class="ui fluid dropdown search selection floating">
					<input type="hidden" name="user_id" value="{{ old('user_id') }}">
					<i class="dropdown icon"></i>
					<div class="text capitalize">...</div>
					<div class="menu">
						@foreach(\App\User::useIndex('blocked')->select('id', 'email')->where('blocked', '0')->get() as $user)
						<a class="item" data-value="{{ $user->id }}">{{ $user->email }}</a>
						@endforeach
					</div>
				</div>
			</div>

			<div class="field">
				<label>{{ __('Is subscription') }}</label>
				<div class="ui fluid dropdown selection floating">
					<input type="hidden" name="is_subscription" value="{{ old('is_subscription', '0') }}">
					<i class="dropdown icon"></i>
					<div class="text">...</div>
					<div class="menu">
						<a class="item" data-value="0">{{ __('No') }}</a>
						<a class="item" data-value="1">{{ __('Yes') }}</a>
					</div>
				</div>
			</div>

			<div class="field items" id="products">
				<label>{{ __('Products') }}</label>
				<div class="table wrapper">
					<table class="ui fluid unstackable basic table">
						<thead>
							<tr>
								<th>{{ __('Name') }}</th>
								<th>{{ __('License') }}</th>
								<th>{{ __('Price') }} <sup class="currency">{{ currency() }}</sup></th>
								<th>-</th>
							</tr>
						</thead>
						<tbody>
							<tr class="d-none main">
								<td class="name">
									<div class="ui floating circular scrolling fluid dropdown large basic search names button mx-0">
										<input type="hidden" name="products[id][]">
										<span class="default text">...</span>
										<i class="dropdown icon"></i>
										<div class="menu"></div>
									</div>
								</td>
								<td class="license four column wide">
									<div class="ui floating circular scrolling fluid dropdown large basic search licenses button mx-0">
										<input type="hidden" name="products[license][]">
										<span class="default text">...</span>
										<i class="dropdown icon"></i>
										<div class="menu"></div>
									</div>
								</td>
								<td class="price three column wide">
									<input type="number" name="products[price][]" class="price" step="0.0000000001">
								</td>
								<td class="one column wide">
									<div class="d-flex">
										<i class="minus circular link yellow inverted icon mr-1-hf"></i>
									  <i class="plus circular link purple inverted icon mx-0"></i>
									</div>
								</td>
							</tr>

							<tr>
								<td class="name">
									<div class="ui floating circular scrolling fluid dropdown large basic search names button mx-0">
										<input type="hidden" name="products[id][]">
										<span class="default text">...</span>
										<i class="dropdown icon"></i>
										<div class="menu"></div>
									</div>
								</td>
								<td class="license four column wide">
									<div class="ui floating circular scrolling fluid dropdown large basic search licenses button mx-0">
										<input type="hidden" name="products[license][]">
										<span class="default text">...</span>
										<i class="dropdown icon"></i>
										<div class="menu"></div>
									</div>
								</td>
								<td class="price three column wide">
									<input type="number" name="products[price][]" class="price" step="0.0000000001">
								</td>
								<td class="one column wide">
									<div class="d-flex">
										<i class="minus circular link yellow inverted icon mr-1-hf"></i>
									  <i class="plus circular link purple inverted icon mx-0"></i>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div class="field items d-none" id="subscriptions">
				<label>{{ __('Subscriptions') }}</label>
				<div class="table wrapper">
					<table class="ui fluid unstackable basic table">
						<thead>
							<tr>
								<th>{{ __('Name') }}</th>
								<th>{{ __('Price') }} <sup class="currency">{{ currency() }}</sup></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="name">
									<div class="ui floating circular scrolling fluid dropdown large basic search button mx-0">
										<input type="hidden" name="subscription[id]" value="{{ old('subscription.id') }}">
										<span class="default text">...</span>
										<i class="dropdown icon"></i>
										<div class="menu">
											@foreach($subscriptions as $subscription)
											<div class="item" data-price="{{ $subscription->price }}" data-value="{{ $subscription->id }}">{{ $subscription->name }}</div>
											@endforeach
										</div>
									</div>
								</td>
								<td class="price three column wide">
									<input type="number" name="subscription[price]" class="price" step="0.0000000001" value="{{ old('subscription.price') }}">
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>


			<div class="three doubling fields">
				<div class="field disabled">
					<label>{{ __('Amount') }}</label>
				  <input type="text" step="0.001" name="amount">
				</div>
				<div class="field">
					<label>{{ __('Discount') }}</label>
				  <input type="text" step="0.001" name="discount" value="{{ old('discount', '0') }}">
				</div>
				<div class="field">
					<label>{{ __('Custom amount') }}</label>
				  <input type="text" step="0.001" name="custom_amount" value="{{ old('custom_amount') }}">
				</div>
			</div>

			<div class="three doubling fields">
				<div class="field">
					<label>{{ __('Payment service') }}</label>
					<div class="ui floating circular scrolling fluid dropdown large basic search button payment-services mx-0">
						<input type="hidden" name="payment_service" value="{{ old('payment_service') }}">
						<div class="text"></div>
						<i class="dropdown icon"></i>
						<div class="menu">
							@foreach($payment_services as $name => $fee)
							<div class="item capitalize" data-value="{{ $name }}" data-fee="{{ $fee ?? '0' }}">{{ $name }} <sup>({{ $fee }})</sup></div>
							@endforeach
						</div>
					</div>
				</div>
				<div class="field">
					<label>{{ __('Currency') }}</label>
					<div class="ui floating circular scrolling fluid dropdown large basic search button mx-0">
						<input type="hidden" name="currency" value="{{ old('currency') }}">
						<div class="text"></div>
						<i class="dropdown icon"></i>
						<div class="menu">
							@foreach(config('payments.currencies') as $code => $currency)
							<div class="item" data-value="{{ $code }}">{{ $code }}</div>
							@endforeach
						</div>
					</div>
				</div>
				<div class="field">
					<label>{{ __('Exchange rate') }}</label>
					<input type="number" step="0.001" name="exchange_rate" value="{{ old('exchange_rate') }}">
				</div>
			</div>
		</div>
	</div>
</form>

<script type="application/javascript">
	'use strict';

	$(function()
	{
		$(document).on('click', '#products i.plus', function()
		{
			var row = $('#products tbody tr.main').clone().removeClass('d-none main');

			$(row).appendTo($('#products tbody'));

			$('#products .ui.dropdown').dropdown();

			getLicenses();
		})


		$(document).on('click', '#products i.minus', function()
		{
			if($('#products tbody tr:not(.main)').length > 1)
			{
				$(this).closest('tr').remove();
			}
		})


		$(document).on('keyup', '#products .ui.dropdown.search.names input.search', debounce(function(e)
		{
			var _this = $(e.target);

			var val = _this.val().trim();

			if(!val.length)
				return;

			$.post('{{ route('products.api') }}', {'keywords': val}, null, 'json')
			.done(function(res)
			{
				var items = res.products.reduce(function(carry, item)
										{
											carry.push('<div class="item" data-value="'+item.id+'" data-license-name="'+item.license_name+'" data-license-id="'+item.license_id+'" data-price="'+item.price+'">'+item.name+'</div>');
											return carry;
										}, []);

				_this.siblings('.ui.dropdown.search.names .menu').html(items.join(''));

				getLicenses();
			})
			.fail(function()
			{
				alert('{{ __('Request failed') }}')
			})
		}, 200));


		$('#subscriptions .ui.dropdown').dropdown({
			onChange: function(val, text, $choice)
			{
				$('#subscriptions input.price').val($choice.data('price'))
			}
		})


		$('input[name="is_subscription"]').on('change', function()
		{
			var itemsSlctrId = $(this).val() == '1' ? 'subscriptions' : 'products';

			$('#'+itemsSlctrId+'.field.items').toggleClass('d-none', false).siblings('.items').toggleClass('d-none', true);
		})


		function getLicenses()
		{
			$('#products .ui.dropdown.names').dropdown(
			{
				onChange: function(itemId, text, $choice)
				{
					$.post('{{ route('payment_links.item_licenses') }}', {item_id: itemId})
					.done(function(data)
					{
						var items = data.licenses_prices.reduce(function(carry, item)
												{
													carry.push('<div class="item" data-value="'+item.license_id+'" data-price="'+item.price+'">'+item.license_name+'</div>');
													return carry;
												}, []);

						$choice.closest('tr').find('.ui.dropdown.search.licenses .menu').html(items.join(''));
					})
				}
			})

			$('#products .ui.dropdown.licenses').dropdown({
				onChange: function(val, text, $choice)
				{
					var price = $choice.data('price');

					$choice.closest('tr').find('input.price').val(price)
				}
			})
		}


		setInterval(function()
		{
			var amount    = 0;
			var elementId = $('input[name="is_subscription"]').val() == '1' ? 'subscriptions' : 'products';
			var discount  = parseFloat($('input[name="discount"]').val());

			$('#'+elementId+' input.price').each(function()
			{
				var price = $(this).val().trim();

				if(!isNaN(price) && price.length)
				{
					amount += parseFloat(price);
				}
			})

			amount += parseFloat($('.ui.dropdown.payment-services .menu .item.selected').data('fee')) || 0;

			$('input[name="amount"]').val(Number(amount >= discount ? (amount - discount) : amount).toFixed('{{ config("payments.currencies.".config('payments.currency_code').".decimals") }}'));
		}, 1000)
	})
</script>
@endsection