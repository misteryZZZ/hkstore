@extends('back.master')

@section('title', __('Dashboard'))

@section('additional_head_tags')
<script src="{{ asset_('assets/admin/chart.bundle.2.9.3.min.js') }}"></script>
@endsection

@section('content')

<div class="row main" id="dashboard">

	<div class="ui four doubling cards general">

		<div class="card fluid items">
			<div class="content top">
				<h3 class="header">{{ __('Items') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/items.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->products }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid orders">
			<div class="content top">
				<h3 class="header">{{ __('Orders') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/cart.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->orders }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid earnings">
			<div class="content top">
				<h3 class="header">{{ __('Earnings') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/dollar.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ config('payments.currency_code') .' '. number_format($counts->earnings, 2) }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid users">
			<div class="content top">
				<h3 class="header">{{ __('Users') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/users.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->users }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid comments">
			<div class="content top">
				<h3 class="header">{{ __('Comments') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/comments.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->comments }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid subscribers">
			<div class="content top">
				<h3 class="header">{{ __('Subscribers') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/subscribers.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{$counts->newsletter_subscribers  }}</h3>
				</div>
			</div>
		</div>

		<div class="card fluid categories">
			<div class="content top">
				<h3 class="header">{{ __('Categories') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/tag.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->categories }}</h3>
				</div>
			</div>
		</div>


		<div class="card fluid posts">
			<div class="content top">
				<h3 class="header">{{ __('Posts') }}</h3>
			</div>
			<div class="content bottom px-0">
				<div class="l-side">
					<div class="ui image">
						<img src="{{ asset_('assets/images/pages.png') }}">
					</div>
				</div>
				<div class="r-side">
					<h3>{{ $counts->posts }}</h3>
				</div>
			</div>
		</div>
	</div>



	<div class="transactions-sales-wrapper">
		<div class="latest transactions">
			<table class="ui celled unstackable table">
				<thead>
					<tr>
						<th colspan="5" class="left aligned header"><h3>{{ __('Latest transactions') }}</h3></th>
					</tr>
					<tr>
						<th class="left aligned w-auto">{{ __('Products') }}</th>
						<th class="left aligned">{{ __('Buyer') }}</th>
						<th class="left aligned">{{ __('Amount') }} ({{ config('payments.currency_code') }})</th>
						<th class="left aligned">{{ __('Processor') }}</th>
						<th class="left aligned">{{ __('Date') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($transactions as $transaction)
					<tr>
						<td>
							@if($transaction->is_subscription)
								{{ __('Subscription') .' - '. $transaction->products[0] }}
							@else
								<div class="ui bulleted list">
									@foreach($transaction->products as $product)
										{{ $product }}
									@endforeach
								</div>
							@endif
						</td>
						<td class="left aligned">{{ $transaction->buyer_name ?? $transaction->buyer_email }}</td>
						<td class="left aligned">{{ number_format($transaction->amount, 2) }}</td>
						<td class="left aligned capitalize">{{ $transaction->processor }}</td>
						<td class="left aligned">{{ $transaction->date }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		<div class="sales chart">
			<div class="ui fluid card">
				<div class="content top">
				  <img class="left floated mini ui image mb-0" src="{{ asset_('assets/images/chart.png') }}">
				  <div class="ui floating dropdown scrolling large blue labeled icon button right floated circular" id="sales-months">
				  	<input type="hidden" name="month" value="{{ date('F') }}">
				  	<i class="dropdown icon"></i>
				  	<div class="text">{{ date('F') }}</div>
				  	<div class="menu">
				  		@foreach(cal_info(0)['months'] as $month)
							<a class="item" data-value="{{ $month }}">{{ __($month) }}</a>
							@endforeach
				  	</div>
				  </div>
				  <div class="header">{{ __('Sales') }}</div>
				  <div class="meta">
				    <span class="date">{{ __('Sales evolution per month') }}</span>
				  </div>
				</div>
				<div class="content">
					<div><canvas id="sales-chart" height="320" width="1284" min-width="1284"></canvas></div>
				</div>
			</div>
		</div>
	</div>


	<div class="ui two stackable cards latest mt-2">
		<div class="card fluid">
			<table class="ui celled unstackable table borderless">
				<thead>
					<tr>
						<th class="left aligned" colspan="2"><h3>{{ __('Latest newsletter subscribers') }}</h3></th>
					</tr>
					<tr>
						<th class="left aligned w-auto">{{ __('Email') }}</th>
						<th class="left aligned">{{ __('Date') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($newsletter_subscribers as $newsletter_subscriber)
					<tr>
						<td class="capitalize">{{ $newsletter_subscriber->email }}</td>
						<td>{{ $newsletter_subscriber->created_at }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		<div class="card fluid">
			<table class="ui celled unstackable table borderless">
				<thead>
					<tr>
						<th class="left aligned" colspan="3"><h3>{{ __('Latest reviews') }}</h3></th>
					</tr>
					<tr>
						<th class="left aligned w-auto">{{ __('Product') }}</th>
						<th class="left aligned">{{ __('Review') }}</th>
						<th>{{ __('Date') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($reviews as $review)
					<tr>
						<td><a href="{{ route('home.product', ['id' => $review->product_id, 'slug' => $review->product_slug.'#reviews']) }}">{{ $review->product_name }}</a></td>
						<td><div class="ui star small rating" data-rating="{{ $review->rating }}" data-max-rating="5"></div></td>
						<td>{{ $review->created_at }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>

	<script type="application/javascript">
		'use strict';
		
		var ctx1 = document.getElementById("sales-chart").getContext('2d');

		window.chart = new Chart(ctx1, {
			type: 'bar',
			responsive: false,
			data: {
				labels: {!! json_encode(range(1, date('t'))) !!}, // days
				datasets: [{
					label: 'Sales',
					backgroundColor: '#EDEDED',
					data: {!! json_encode($sales) !!}, // Sales
					borderWidth: 0
				}]
			},
			options: {
				tooltips: {
					mode: 'index',
					intersect: false,
					backgroundColor: '#fff',
					cornerRadius: 0,
					bodyFontColor: '#000',
					titleFontColor: '#000',
					legendColorBackground: '#000'
				},
				legend: {
					display: false,
				},
				responsive: false,
				maintainAspectRatio: false,
				scales: {
					xAxes: [{
						stacked: true,
					}],
					yAxes: [{
						stacked: false,
						ticks: {
	            stepSize: 1,
	            min: 0
	          }
					}]
				}
			}
		});

		$('#sales-months').dropdown();

		$('#sales-months input').on('change', function()
		{
			$.post('{{ route('admin.update_sales_chart') }}', {month: $(this).val()}, null, 'json')
			.done(function(res)
			{
				chart.data.labels = res.labels;
				chart.data.datasets[0]['data'] = res.data;

				chart.update();
			})
			.fail(function()
			{
				alert('Failed to update sales chart')
			})
		})
	</script>

</div>

@endsection