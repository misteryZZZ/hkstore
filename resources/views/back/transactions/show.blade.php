{{-- @include("back.transactions.{$processor}") --}}

@extends('back.master')

@section('title', __('Transaction details'))


@section('content')

<a class="ui icon labeled blue large circular button mb-1" href="{{ route('transactions') }}">
	<i class="left angle icon"></i>
	{{ __('Transactions') }}
</a>

<div class="ui three doubling stackable cards" id="transaction">
	<div class="fluid card">
		<div class="content header">
			{{ __('Transaction identifiers') }}
		</div>
		<div class="content body">
			<div class="item">
				<div class="name">{{ __('Transaction ID') }}</div>
				<div class="value">{{ $transaction->id ?? __('N-A') }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Reference ID') }}</div>
				<div class="value">{{ $transaction->reference_id ?? __('N-A') }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Order ID') }}</div>
				<div class="value">{{ $transaction->order_id ?? __('N-A') }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('CS Token') }} <sup>(Stripe)</sup></div>
				<div class="value">{{ $transaction->cs_token ?? __('N-A') }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Processor') }}</div>
				<div class="value">{{ __($transaction->processor === 'n-a' ? 'Guest' : ucfirst($transaction->processor)) }}</div>
			</div>
		</div>
	</div>

	<div class="fluid card">
		<div class="content header">
			{{ __('Transaction summary') }}
		</div>
		<div class="content body">
			<div class="item">
				<div class="name">{{ __('Created at') }}</div>
				<div class="value">{{ $transaction->created_at }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Status') }}</div>
				<div class="value">{{ __(ucfirst($transaction->status)) }}</div>
			</div>
			@if($transaction->processor === 'offline')
			<div class="item">
				<div class="name">{{ __('Confirmed') }}</div>
				<div class="value">{{ $transaction->confirmed ? __('Yes') : __('No') }}</div>
			</div>
			@endif
			<div class="item">
				<div class="name">{{ __('Refunded') }}</div>
				<div class="value">{{ $transaction->refunded ? __('Yes') : __('No') }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Currency') }}</div>
				<div class="value">{{ $transaction->details->currency }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Exchange rate') }}</div>
				<div class="value">{{ $transaction->details->exchange_rate }}</div>
			</div>
			@foreach($transaction->details->items ?? [] as $item)
			<div class="item">
				<div class="name">
					{{ __($item->name) }}
					@if($item->license ?? null)
					<sup>({{ __($item->license) }})</sup>
					@endif
				</div>
				<div class="value">{{ $transaction->details->currency .' '. format_amount($item->value, false, $transaction->details->decimals ?? 2) }}</div>
			</div>
			@endforeach
			@if(!($transaction->details->items->discount ?? null))
			<div class="item">
				<div class="name">{{ __('Discount') }}</div>
				<div class="value">{{ $transaction->details->currency .' '. format_amount($transaction->details->discount ?? $transaction->discount, false, $transaction->details->decimals ?? 2) }}</div>
			</div>
			@endif
			@if($transaction->details->custom_amount ?? null)
			<div class="item">
				<div class="name">{{ __('Custom amount') }}</div>
				<div class="value">{{ $transaction->details->currency .' '. format_amount($transaction->details->custom_amount ?? $transaction->custom_amount, false, $transaction->details->decimals ?? 2) }}</div>
			</div>
			@endif
		</div>
	</div>

	<div class="fluid card">
		<div class="content header">
			{{ __('Buyer info') }}
		</div>
		<div class="content body">
			@if($buyer)
			<div class="item">
				<div class="name">{{ __('First name') }}</div>
				<div class="value">{{ $buyer->firstname }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Last name') }}</div>
				<div class="value">{{ $buyer->lastname }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Email') }}</div>
				<div class="value">{{ $buyer->email }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Address') }}</div>
				<div class="value">{{ $buyer->address }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Country') }}</div>
				<div class="value">{{ $buyer->country }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('City') }}</div>
				<div class="value">{{ $buyer->city }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('ID number') }}</div>
				<div class="value">{{ $buyer->id_number }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Zip code') }}</div>
				<div class="value">{{ $buyer->zip_code }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('State') }}</div>
				<div class="value">{{ $buyer->state }}</div>
			</div>
			<div class="item">
				<div class="name">{{ __('Verified') }}</div>
				<div class="value">{{ $buyer->verified ? __('Yes') : __('No') }}</div>
			</div>
			@else
			<div class="item">
				<div class="name">{{ __('Type') }}</div>
				<div class="value">{{ __('Guest') }}</div>
			</div>
			@endif
			@isset($transaction->guest_token)
			<div class="item">
				<div class="name">{{ __('Guest token') }}</div>
				<div class="value">{{ $transaction->guest_token }}</div>
			</div>
			@endisset
		</div>
	</div>
</div>

@endsection