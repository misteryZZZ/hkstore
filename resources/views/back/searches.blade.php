@extends('back.master')

@section('title', __('Users searches'))


@section('content')

<div class="row main" id="searches">

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>
		
		<div class="right menu mr-1">
			<form action="{{ route('searches') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="Search ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>
	
	<div class="table wrapper items searches">
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
						<a href="{{ route('searches', ['orderby' => 'keywords', 'order' => $items_order]) }}">{{ __('Keywords') }}</a>
					</th>
					<th>
						<a href="{{ route('searches', ['orderby' => 'user', 'order' => $items_order]) }}">{{ __('User') }}</a>
					</th>
					<th>
						<a href="{{ route('searches', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Searched at') }}</a>
					</th>
					<th>
						<a href="{{ route('searches', ['orderby' => 'occurrences', 'order' => $items_order]) }}">{{ __('Occurrences') }}</a>
					</th>
				</tr>
			</thead>
			<tbody>
				@foreach($searches as $search)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $search->id }}" @change="toogleId({{ $search->id }})">
						  <label></label>
						</div>
					</td>
					<td>{{ $search->keywords }}</td>
					<td class="center aligned">{{ $search->user }}</td>
					<td class="center aligned">{{ $search->created_at }}</td>
					<td class="center aligned">{{ $search->occurrences }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	@if($searches->hasPages())
	<div class="ui fluid divider"></div>

	{{ $searches->appends($base_uri)->onEachSide(1)->links() }}
	{{ $searches->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('searches.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'searches']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="searches">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('searches') as $column)
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
	  el: '#searches',
	  data: {
	  	route: '{{ route('searches.destroy', "") }}/',
	    ids: [],
	    isDisabled: true,
	    itemId: ''
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
	  		$('#searches tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected search(es)') }} ?';

	  		if(!this.ids.length || !confirm(confirmationMsg))
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	deleteItem: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to delete this search') }} ?'))
  			{
  				e.preventDefault();
  				return false;
  			}
	  	},
	  },
	  watch: {
	  	ids: function(val)
	  	{
	  		this.isDisabled = !val.length;
	  	}
	  }
	})


	$('#search').on('submit', function(event)
	{
		if(!$('input', this).val().trim().length)
		{
			e.preventDefault();
			return false;
		}
	})
</script>
@endsection