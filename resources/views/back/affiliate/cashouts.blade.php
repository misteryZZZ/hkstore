@extends('back.master')

@section('title', __('Cashouts'))


@section('content')

<div class="row main" id="cashouts">

	<div class="ui menu shadowless">
		<a href="{{ route('affiliate.balances') }}" class="item">{{ __('Balances') }}</a>
		<a @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<div class="right menu mr-1">
			<form action="{{ route('affiliate.cashouts') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>

	<div class="table wrapper items cashouts">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th>
						<a href="{{ route('affiliate.cashouts', ['orderby' => 'email', 'order' => $items_order]) }}">{{ __('Email') }}</a>
					</th>
					<th>
						<a href="{{ route('affiliate.cashouts', ['orderby' => 'method', 'order' => $items_order]) }}">{{ __('Method') }}</a>
					</th>
					<th>
						<a href="{{ route('affiliate.cashouts', ['orderby' => 'amount', 'order' => $items_order]) }}">{{ __('Amount') }}</a>
					</th>
					<th>
						{{ __('Details') }}
					</th>
					<th>
						<a href="{{ route('affiliate.cashouts', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Created at') }}</a>
					</th>
				</tr>
			</thead>
			<tbody>
				@foreach($cashouts as $cashout)
				<tr>

					<td class="center aligned">
						<div class="ui fitted checkbox">
						  <input type="checkbox" value="{{ $cashout->id }}" @change="toogleId({{ $cashout->id }})">
						  <label></label>
						</div>
					</td>

					<td>{{ ucfirst($cashout->email) }}</td>
					
					<td class="center aligned">{{ ucfirst($cashout->method) }}</td>

					<td class="center aligned">{{ price($cashout->amount, false) }}</td>

					<td class="center aligned">
						<button class="small ui circular yellow button mx-0" type="button" @click="showDetails({{ $cashout->details }})">{{ __('Details') }}</button>
					</td>

					<td class="center aligned">{{ $cashout->updated_at }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($cashouts->hasPages())
	<div class="ui fluid divider"></div>

	{{ $cashouts->appends($base_uri)->onEachSide(1)->links() }}
	{{ $cashouts->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
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
	  el: '#cashouts',
	  data: {
	  	route: '{{ route('affiliate.destroy_cashouts', "") }}/',
	    ids: [],
	    emails: [],
	    notification: '',
	    isDisabled: true,
	    details: null,
	  },
	  methods: {
	  	toogleId: function(id)
	  	{
	  		if(this.ids.indexOf(id) >= 0)
	  			this.ids.splice(this.ids.indexOf(id), 1);
	  		else
	  			this.ids.push(id);
	  	},
	  	selectAll: function()
	  	{
	  		$('#cashouts tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		if(!this.ids.length)
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
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