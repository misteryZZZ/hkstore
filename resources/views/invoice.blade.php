<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>{{ __('Invoice') }}</title>
	<style>
		#watermark {
	    position: fixed;
	    top: 45%;
	    width: 100%;
	    text-align: center;
	    opacity: .05;
	    transform: rotate(10deg);
	    transform-origin: 50% 50%;
	    z-index: -1000;
	    font-size: 8rem;
	    font-weight: bold;
	    font-family: 'sans-serif';
	  }

	  #app-name {
	  	padding: 1rem;
	  }

	  #refunded {
	  	position: fixed;
	    top: 90%;
	    width: 100%;
	    text-align: left;
	    opacity: 1;
	    z-index: -1000;
	    font-size: 2rem;
	    font-weight: bold;
	    font-family: 'sans-serif';
	    color: #FF3B3B;
	  }

	  #reference {
	  	max-width: 400px;
	  	float: right;
	  }
	</style>
</head>

<body dir="{{ locale_direction() }}">
	<div id="watermark">
    {{ config('app.name') }}
  </div>

  @if($refunded)
  <div id="refunded">{{ __('Refunded') }}</div>
  @endif

	<div style="line-height: 2rem; font-size: 1.1rem;">
		<div style="display: flex;padding: 0 2rem;">
			<div style="flex: 1;">
				<div style="font-weight: 500; font-size: 3rem;" id="app-name">{{ config('app.name') }}</div>
				<div>{{ __('Email') }} : {{ config('app.email') }}</div>
				<div>{{ __('Website') }} : {{ config('app.url') }}</div>
			</div>

			<div style="flex: 1; text-align: right;">
				<div>{{ __('Date') }} : {{ $transaction->created_at }}</div>
				<div id="reference">{{ __('Reference') }} : {{ $reference }}</div>
			</div>
		</div>

		<div style="padding: 2rem;">
			<div style="font-weight: 500; font-size: 1.2rem;">{{ __('Bill to :') }}</div>
			<div>{{ __('Name') }} : {{ $buyer->name }}</div>
			<div>{{ __('Email') }} : {{ $buyer->email }}</div>
		</div>

		<div>
			<div>
				<table style="border-spacing: 0; width: 100%;">
					<thead style="background: ghostwhite">
						<tr>
							<th style="border: 1px solid #acacac; padding: .5rem; text-transform: uppercase;">{{ __('Description') }}</th>
							<th style="border: 1px solid #acacac; padding: .5rem; text-transform: uppercase;">{{ __('Unit price') }}</th>
							<th style="border: 1px solid #acacac; padding: .5rem; text-transform: uppercase;">{{ __('Quantity') }}</th>
							<th style="border: 1px solid #acacac; padding: .5rem; text-transform: uppercase;">{{ __('Total') }}</th>
						</tr>	
					</thead>
					<tbody>
						@foreach($items ?? [] as $item)
							<tr>
								<td style="border: 1px solid #acacac; padding: .5rem;">
									{{ $item['name'] }}
									@if($is_subscription)
									<sup>({{ __('Subscription') }})</sup>
									@endif
								</td>
								<td style="border: 1px solid #acacac; padding: .5rem; text-align: right;">{{ $item['value'] }}</td>
								<td style="border: 1px solid #acacac; padding: .5rem; text-align: right;">1</td>
								<td style="border: 1px solid #acacac; padding: .5rem; text-align: right;">{{ $item['value'] }}</td>
							</tr>
						@endforeach
					</tbody>
					<tfoot style="text-align: right;">
						<tr>
							<td></td>
							<td></td>
							<td style="padding: .5rem; font-weight: 500;">{{ __('Subtotal') }}</td>
							<td style="border: 1px solid #acacac; padding: .5rem;">{{ $currency .' '. $subtotal }}</td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td style="padding: .5rem; font-weight: 500;">{{ __('Handling Fee') }}</td>
							<td style="border: 1px solid #acacac; padding: .5rem;">{{ $currency .' '. $fee }}</td>
						</tr>
						<tr>
							<td></td>
							<td></td>
							<td style="padding: .5rem; font-weight: 500;">{{ __('Tax') }}</td>
							<td style="border: 1px solid #acacac; padding: .5rem;">{{ $currency .' '. $tax }}</td>
						</tr>
						@if($discount)
						<tr>
							<td></td>
							<td></td>
							<td style="padding: .5rem; font-weight: 500;">{{ __('Discount') }}</td>
							<td style="border: 1px solid #acacac; padding: .5rem;">{{ $currency .' '. $discount }}</td>
						</tr>
						@endif
						<tr>
							<td></td>
							<td></td>
							<th style="padding: .5rem; text-align: right; font-weight: 500;">{{ __('Total due') }}</td>
							<td style="border: 1px solid #acacac; padding: .5rem; background: ghostwhite">{{ $currency .' '. $total_due }}</td>
						</tr>
					</tfoot>
				</table>			
			</div>
		</div>
	</div>
</body>
</html>