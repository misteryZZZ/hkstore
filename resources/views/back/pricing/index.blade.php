@extends('back.master')

@section('title', $title)


@section('content')

<div class="row main" id="subscriptions">
	
	<div class="ui menu large shadowless">
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<a href="{{ route('subscriptions.create') }}" class="item">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items subscriptions">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th class="one column wide">
						<div class="ui fitted checkbox">
						  <input type="checkbox" @change="selectAll">
						  <label></label>
						</div>
					</th>
					<th class="five columns wide">{{ __('Name') }}</th>
					<th>{{ __('Price') }}</th>
					<th>{{ __('Limit downloads') }}</th>
					<th>{{ __('Downloads per day') }}</th>
					<th>{{ __('Days') }}</th>
					<th>{{ __('Updated at') }}</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($subscriptions as $subscription)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $subscription->id }}" @change="toogleId({{ $subscription->id }})">
						  <label></label>
						</div>
					</td>
					<td>{{ ucfirst($subscription->name) }}</td>
					<td class="center aligned">{{ price($subscription->price, true) }}</td>
					<td class="center aligned">{{ __($subscription->limit_downloads) }}</td>
					<td class="center aligned">{{ __($subscription->limit_downloads_per_day) }}</td>
					<td class="center aligned">{{ __($subscription->days) }}</td>
					<td class="center aligned">{{ $subscription->updated_at }}</td>
					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown left">
								<a href="{{ route('subscriptions.edit', ['id' => $subscription->id]) }}" class="item">{{ __('Edit') }}</a>
								<a href="{{ route('subscriptions.destroy', ['ids' => $subscription->id]) }}" class="item">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<form class="ui form modal export" action="{{ route('subscriptions.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Subscriptions']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="subscriptions">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('subscriptions') as $column)
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
	  el: '#subscriptions',
	  data: {
	  	route: '{{ route('subscriptions.destroy', "") }}/',
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
	  		$('#subscriptions tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected element(s)') }} ?';

	  		if(!this.ids.length || !confirm(confirmationMsg))
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