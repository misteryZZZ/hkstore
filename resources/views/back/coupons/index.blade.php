@extends('back.master')

@section('title', __('Coupons'))


@section('content')

<div class="row main" id="coupons">

	<div class="ui menu shadowless">
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>
		
		<div class="right menu">
			<form action="{{ route('coupons') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
			</form>
			<a href="{{ route('coupons.create') }}" class="item ml-1">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items coupons">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>
						<div class="ui fitted checkbox">
						  <input type="checkbox" @change="selectAll">
						  <label></label>
						</div>
					</th>
					<th>
						<a href="{{ route('coupons', ['orderby' => 'code', 'order' => $items_order]) }}">{{ __('Code') }}</a>
					</th>
					<th>
						<a href="{{ route('coupons', ['orderby' => 'used_by', 'order' => $items_order]) }}">{{ __('Used by') }}</a>
					</th>
					<th>
						<a href="{{ route('coupons', ['orderby' => 'value', 'order' => $items_order]) }}">{{ __('Value') }}</a>
					</th>
					<th>
						<a href="{{ route('coupons', ['orderby' => 'starts_at', 'order' => $items_order]) }}">{{ __('Starts at') }}</a>
					</th>
					<th>
						<a href="{{ route('coupons', ['orderby' => 'expires_at', 'order' => $items_order]) }}">{{ __('Expires at') }}</a>
					</th>
					<th>
						<a href="{{ route('coupons', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>

			<tbody>
				@foreach($coupons as $coupon)
				<tr id="{{ $coupon->id }}">

					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $coupon->id }}" @change="toogleId({{ $coupon->id }})">
						  <label></label>
						</div>
					</td>

					<td class="center aligned">{{ $coupon->code }}</td>

					<td class="center aligned">{{ $coupon->used_by }}</td>

					<td class="center aligned">
						@if($coupon->is_percentage)
						{{ $coupon->value.'% OFF' }}
						@else
						{{ config('payments.currency_code').' '.number_format($coupon->value, 2) }}
						@endif
					</td>

					<td class="center aligned" >
						{{ (new DateTime($coupon->starts_at))->format("d M Y \a\\t h:i:s A") }}
					</td>

					<td class="center aligned">
						@if($coupon->expires_at < date('Y-m-d H:i:s'))
						<span class="ui basic circular red label m-0 expired">
							{{ __('Expired') }}
						</span>
						@else
						{{ (new DateTime($coupon->expires_at))->format("d M Y \a\\t h:i:s A") }}
						@endif
					</td>

					<td class="center aligned">
						{{ (new DateTime($coupon->updated_at))->format("d M Y \a\\t h:i:s A") }}
					</td>

					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown left rounded-corner">
								<a href="{{ route('coupons.edit', $coupon->id) }}" class="item">{{ __('Edit') }}</a>
								<a @click="deleteItem($event)" href="{{ route('coupons.destroy', $coupon->id) }}" class="item">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($coupons->hasPages() ?? null)
	<div class="ui fluid divider"></div>

	{{ $coupons->appends($base_uri)->onEachSide(1)->links() }}
	{{ $coupons->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('coupons.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Coupons']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="coupons">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('coupons') as $column)
					<tr>
						<td>
							<div class="ui checked checkbox">
							  <input type="checkbox" id="{{ $column }}" name="columns[{{ $column }}][active]" checked="checked">
							  <label for="{{ $column }}">{{ $column }}</label>
							</div>
							
							<input type="hidden" name="columns[{{ $column }}][name]" value="{{ $column }}">
						</td>
						<td>
							<input type="text" name="columns[{{ $column }}][new_name]" placeholder="...">
						</td>
					</tr>
					@endforeach
				</tbody>				
			</table>
		</div>
		<div class="actions">
			<button class="ui yellow large circular button approve">{{ __('Export') }}</button>
			<button class="ui red circular large button cancel" type="button">{{ __('Cancel') }}</button>
		</div>
	</form>

</div>

<script>
	'use strict';

	var app = new Vue({
	  el: '#coupons',
	  data: {
	  	route: '{{ route('coupons.destroy', "") }}/',
	    ids: [],
	    isDisabled: true
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
	  		$('#coupons tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected element(s)') }} ?';
	  		
	  		if(!this.ids.length || !confirm(confirmationMsg))
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	deleteItem: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to delete the selected element(s)') }} ?'))
  			{
  				e.preventDefault();
  				return false;
  			}
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