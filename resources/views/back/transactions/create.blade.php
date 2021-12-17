@extends('back.master')

@section('title', __('Create transaction'))

@section('content')

@if(session('user_not_found'))
<div class="ui fluid small negative bold message">
	<i class="close icon"></i>
	{{ session('user_not_found') }}
</div>
@endif

<form class="ui large form" method="post" action="{{ route('transactions.store') }}" spellcheck="false">
	@csrf

	<div class="field">
		<button class="ui icon labeled teal large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('transactions') }}">
			<i class="times icon"></i>
			{{ __('Cancel') }}
		</a>
		
		@if(strtolower(request()->for ?? 'default') === 'default')
		<a href="{{ route('transactions.create', ['for' => 'subscriptions']) }}" class="ui blue large circular button right floated">
			{{ __('Switch to subscriptions') }}
		</a>
		@else
		<a href="{{ route('transactions.create') }}" class="ui blue large circular button right floated">
			{{ __('Switch to default') }}
		</a>
		@endif
	</div>
	
	@if($errors->any())
    @foreach ($errors->all() as $error)
		<div class="ui negative circular-corner bold fluid small message">
			<i class="times icon close"></i>
			{{ $error }}
		</div>
    @endforeach
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid">
		<div class="column">
			<input type="hidden" name="is_subscription" value="{{ var_export(strtolower(request()->for) === 'subscriptions') }}">

			<div class="field">
				<label>{{ __('User email') }}</label>
				<input type="email" name="email" placeholder="..." value="{{ old('email') }}" autofocus required>
			</div>
			
			@if(!request()->for)

			<div class="field">
				<label>{{ __('Products') }}</label>
				<div class="ui search floating selection multiple dropdown">
					<input type="hidden" name="products_ids" required value="{{ old('products_ids') }}">
					<i class="dropdown icon"></i>
					<div class="default text">{{ __('Products') }}</div>
					<div class="menu">
					@foreach($products as $product)
						<div class="item" data-value="{{ $product->id }}">
							{{ $product->name }} 
							<span class="right floated">{{ price($product->price) }}</span>
						</div>
					@endforeach
					</div>
				</div>
			</div>

			@else

			<div class="field">
				<label>{{ __('Subscription') }}</label>
				<div class="ui selection floating dropdown subscriptions">
					<input type="hidden" name="products_ids" required value="{{ old('products_ids') }}">
					<i class="dropdown icon"></i>
					<div class="default text">{{ __('Subscription') }}</div>
					<div class="menu">
					@foreach(App\Models\Subscription::orderBy('id', 'desc')->get() as $subscription)
						<div class="item capitalize" data-value="{{ $subscription->id }}" data-price="{{ $subscription->price }}">
							{{ $subscription->name }} 
							<span class="right floated">{{ price($subscription->price) }}</span>
						</div>
					@endforeach
					</div>
				</div>
			</div>

			@endif

			<div class="field">
				<label>{{ __('Discount') }}</label>
				<input type="number" name="discount" step="0.01" value="{{ old('discount', '0') }}">
			</div>
			
			<div class="field">
				<label>{{ __('Amount') }}</label>
				<input type="number" name="amount" required step="0.01" value="{{ old('amount', '0') }}">
			</div>

		</div>
	</div>

	@if(strtolower(request()->for) === 'subscriptions')
	<script>
		'use strict';

		$(function()
		{
			$('.ui.dropdown.subscriptions').dropdown({
				onChange: function(value, text, $choice)
				{
					$('input[name="amount"]').val($($choice).data('price'));
				}
			})
		})
	</script>
	@endif
</form>

@endsection