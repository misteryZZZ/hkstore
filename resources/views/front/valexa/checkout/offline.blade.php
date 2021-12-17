@extends(view_path('master'))

@section('additional_head_tags')
<meta name="robots" content="nofollow, noindex">
@endsection


@section('body')
	<div class="column row w-100 confirm" id="checkout-response">
		<div class="ui fluid card">
			<div class="content title">
				{{ __('Payment created') }}
			</div>

			<div class="content summary">
				<div class="details">

					@foreach($transaction_details->items as $k => $item)
					<div class="item {{ $k }}">
						<div class="name capitalize">{{ mb_ucfirst($item->name) }}</div>
						<div class="price">{{ $item->value }}</div>
					</div>
					@endforeach

					<div class="total item">
						<div class="name">{{ __('Total') }}</div>
						<div class="price">{{ $transaction_details->currency.' '.$transaction_details->total_amount }}</div>
					</div>
				</div>
			</div>

			<div class="content center aligned">
				<form method="post" action="{{ route('home.checkout.offline_confirm', ['reference' => $transaction->reference_id]) }}">
					@csrf
					<button class="ui red circular large button" value="false" name="cancel">{{ __('Cancel') }}</button>
					<button class="ui yellow circular large button" value="true" name="confirm">{{ __('Confirm') }}</button>
				</form>
			</div>
		</div>
	</div>
@endsection