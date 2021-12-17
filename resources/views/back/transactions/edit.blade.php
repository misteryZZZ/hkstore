@extends('back.master')

@section('title', __('Edit offline transaction - :transaction_id', ['transaction_id' => $transaction->transaction_id]))

@section('content')

@if(session('user_not_found'))
<div class="ui fluid small negative bold message">
	<i class="close icon"></i>
	{{ session('user_not_found') }}
</div>
@endif

<form class="ui large form" method="post" action="{{ route('transactions.update', ['id' => $transaction->id]) }}" spellcheck="false">
	@csrf

	<div class="field">
		<button class="ui icon labeled teal large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Update') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('transactions') }}">
			<i class="times icon"></i>
			{{ __('Cancel') }}
		</a>
	</div>
	
	@if($errors->any())
    @foreach ($errors->all() as $error)
		<div class="ui negative fluid circular-corner bold small message">
			<i class="times icon close"></i>
			{{ $error }}
		</div>
    @endforeach
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid">
		<div class="column">
			<input type="hidden" name="is_subscription" value="{{ var_export($transaction->is_subscription === 1) }}">

			<div class="field">
				<label>{{ __('User email') }}</label>
				<input type="email" name="email" placeholder="..." value="{{ old('email', $transaction->email) }}" autofocus required>
			</div>
		
			@if(!$transaction->is_subscription)

			<div class="field">
				<label>{{ __('Products') }}</label>
				<div class="ui search floating selection multiple dropdown">
					<input type="hidden" name="products_ids" required value="{{ old('products_ids', str_ireplace('\'', '', $transaction->products_ids)) }}">
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
					<input type="hidden" name="products_ids" required value="{{ old('products_ids', str_ireplace('\'', '', $transaction->products_ids)) }}">
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
				<label>{{ __('Amount') }}</label>
				<input type="number" name="amount" required step="0.01" value="{{ old('amount', $transaction->amount) }}">
			</div>

			<div class="field">
				<label>{{ __('Discount') }}</label>
				<input type="number" name="discount" step="0.01" value="{{ old('discount', $transaction->discount ?? '0') }}">
			</div>

			<div class="field">
				<label>{{ __('Refunded') }}</label>
				<div class="ui selection floating dropdown">
					<input type="hidden" name="refunded" value="{{ old('refunded', $transaction->refunded) }}">
					<i class="dropdown icon"></i>
					<div class="default text">{{ __('Refunded status') }}</div>
					<div class="menu">
						<div class="item" data-value="1">{{ __('Yes') }} </div>
						<div class="item" data-value="0">{{ __('No') }} </div>
					</div>
				</div>
			</div>

		</div>
	</div>
</form>

@endsection