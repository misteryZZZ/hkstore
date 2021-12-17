@extends('back.master')

@section('title', $title)


@section('content')

<div class="row main" id="subscribers">
	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<a href="{{ route('subscribers.newsletter.create') }}" class="item ml-1">{{ __('Create newsletter') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items subscribers">
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
						<a href="{{ route('subscribers', ['orderby' => 'email', 'order' => $items_order]) }}">{{ __('Email') }}</a>
					</th>
					<th>
						<a href="{{ route('subscribers', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Delete') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($subscribers as $subscriber)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $subscriber->id }}" @change="toogleId({{ $subscriber->id }})">
						  <label></label>
						</div>
					</td>
					<td>{{ ucfirst($subscriber->email) }}</td>
					<td class="center aligned">{{ $subscriber->updated_at }}</td>
					<td class="center aligned one column wide">
						<a @click="deleteItem($event)" href="{{ route('subscribers.destroy', $subscriber->id) }}" class="ui basic circular labeled icon button"><i class="trash alternate outline icon"></i>{{ __('Delete') }}</a>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($subscribers->hasPages())
	<div class="ui fluid divider"></div>

	{{ $subscribers->appends($base_uri)->onEachSide(1)->links() }}
	{{ $subscribers->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('subscribers.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Newsletter_subscribers']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="subscribers">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('newsletter_subscribers') as $column)
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
	  el: '#subscribers',
	  data: {
	  	route: '{{ route('subscribers.destroy', "") }}/',
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
	  		$('#subscribers tbody .ui.checkbox.select').checkbox('toggle')
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