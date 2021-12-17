@extends('back.master')

@section('title', __('Balances'))


@section('content')

<div class="row main" id="balances">

	<div class="ui menu shadowless">
		<a href="{{ route('affiliate.cashouts') }}" class="item">{{ __('Cashouts') }}</a>
		<a @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<div class="right menu mr-1">
			<form action="{{ route('affiliate.balances') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>

	<div class="table wrapper items balances">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th>
						<a href="{{ route('affiliate.balances', ['orderby' => 'email', 'order' => $items_order]) }}">{{ __('Email') }}</a>
					</th>
					<th>
						<a href="{{ route('affiliate.balances', ['orderby' => 'earnings', 'order' => $items_order]) }}">{{ __('Earnings') }}</a>
					</th>
					<th>{{ __('Eligible') }}</th>
					<th>
						<a href="{{ route('affiliate.balances', ['orderby' => 'method', 'order' => $items_order]) }}">{{ __('Method') }}</a>
					</th>
					<th>
						{{ __('Details') }}
					</th>
					<th>
						<a href="{{ route('affiliate.balances', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Created at') }}</a>
					</th>
					<th>{{ __('Action') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($balances as $balance)
				<tr>

					<td class="center aligned">
						<div class="ui fitted checkbox">
						  <input type="checkbox" value="{{ $balance->ids }}" @change="toogleIds('{{ $balance->ids }}')">
						  <label></label>
						</div>
					</td>

					<td>{{ ucfirst($balance->email) }}</td>

					<td class="center aligned">{{ price($balance->earnings, false) }}</td>

					<td class="center aligned">
						@if($balance->has_minimum)
						<i class="circle green icon mx-0"></i>
						@else
						<i class="circle red icon mx-0"></i>
						@endif
					</td>
					
					<td class="center aligned">{{ ucfirst(explode('_', $balance->method)[0] ?? null) }}</td>

					<td class="center aligned"><button class="small ui circular yellow button mx-0" type="button" @click="showDetails({{ $balance->details }})">{{ __('Details') }}</button></td>

					<td class="center aligned">{{ $balance->updated_at }}</td>

					<td class="center aligned">
						@if($balance->has_minimum)
							@if($balance->method === 'paypal_account')
							<button class="ui yellow small circular button fluid nowrap mx-0" type="button" @click="transferToPaypal('{{ $balance->ids }}', $event)">{{ __('Pay') }}</button>
							@elseif($balance->method === 'bank_account')
							<button class="ui yellow small circular button fluid nowrap mx-0" type="button" @click="markAsPaid('{{ $balance->ids }}', $event)">{{ __('Mark as paid') }}</button>
							@endif
						@else
						-
						@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($balances->hasPages())
	<div class="ui fluid divider"></div>

	{{ $balances->appends($base_uri)->onEachSide(1)->links() }}
	{{ $balances->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<div class="ui tiny modal" id="details">
		<div class="content">
			<table class="ui large fluid table">
				<tbody>
					<tr v-for="(v, k) of details">
						<td>@{{ k }}</td>
						<td>@{{ v }}</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<script>
	'use strict';
	
	var app = new Vue({
	  el: '#balances',
	  data: {
	  	route: '{{ route('affiliate.destroy_balances', "") }}/',
	    ids: [],
	    emails: [],
	    notification: '',
	    isDisabled: true,
	    details: {}
	  },
	  methods: {
	  	toogleIds: function(ids)
	  	{
	  		if(this.ids.indexOf(ids) >= 0)
	  			this.ids.splice(this.ids.indexOf(ids), 1);
	  		else
	  			this.ids.push(ids);
	  	},
	  	selectAll: function()
	  	{
	  		$('#balances tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		if(!this.ids.length)
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	markAsPaid: function(ids, e)
	  	{
	  		var btn = $(e.target);
	  		var ids = ids.split(',');

	  		if(!ids.length)
	  		{
	  			return;
	  		}

	  		btn.prop('disabled', true).toggleClass('loading', true);

	  		$.post('/admin/affiliate/mark_as_paid', {ids: ids})
	  		.done(function(data)
	  		{
	  			if(data.status)
	  			{
	  				btn.closest('td').html('-');
	  				alert(data.message);
	  			}
	  		})
	  		.always(function()
	  		{
	  			if(btn.length)
	  			{
	  				btn.prop('disabled', false).toggleClass('loading', false);
	  			}
	  		})
	  	},
	  	transferToPaypal: function(ids, e)
	  	{
	  		var btn = $(e.target);
	  		var ids = ids.split(',');

	  		if(!ids.length)
	  		{
	  			return;
	  		}

	  		btn.prop('disabled', true).toggleClass('loading', true);

	  		$.post('/admin/affiliate/transfer_to_paypal', {ids: ids})
	  		.done(function(data)
	  		{
	  			if(data.status)
	  			{
	  				btn.closest('td').html('-');
	  				alert(data.message);
	  			}
	  		})
	  		.always(function()
	  		{
	  			if(btn.length)
	  			{
	  				btn.prop('disabled', false).toggleClass('loading', false);
	  			}
	  		})
	  	},
	  	showDetails: function(details)
	  	{
	  		this.details = details;

	  		Vue.nextTick(function()
	  		{
	  			$('#details').modal('show');
	  		})
	  	}
	  },
	  watch: {
	  	ids: function(val)
	  	{
	  		this.isDisabled = !val.length;
	  	}
	  }
	})
</script>
@endsection