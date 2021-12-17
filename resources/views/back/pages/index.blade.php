@extends('back.master')

@section('title', $title)


@section('content')

<div class="row main" id="pages">

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<form action="{{ route('pages') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
			<a href="{{ route('pages.create') }}" class="item ml-1">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items pages">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>
						<div class="ui fitted checkbox">
						  <input type="checkbox" @change="selectAll">
						  <label></label>
						</div>
					</th>
					<th class="five columns wide">
						<a href="{{ route('pages', ['orderby' => 'name', 'order' => $items_order]) }}">{{ __('Name') }}</a>
					</th>
					<th>
						<a href="{{ route('pages', ['orderby' => 'views', 'order' => $items_order]) }}">{{ __('Views') }}</a>
					</th>
					<th>
						<a href="{{ route('pages', ['orderby' => 'active', 'order' => $items_order]) }}">{{ __('Active') }}</a>
					</th>
					<th>
						<a href="{{ route('pages', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($pages as $page)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $page->id }}" @change="toogleId({{ $page->id }})">
						  <label></label>
						</div>
					</td>
					<td><a href="{{ route('home.page', $page->slug) }}">{{ ucfirst($page->name) }}</a></td>
					<td class="center aligned">{{ $page->views }}</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="active" @if($page->active) checked @endif data-id="{{ $page->id }}" data-status="active" 
						  @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">{{ $page->updated_at }}</td>
					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown left rounded-corner">
								<a href="{{ route('pages.edit', $page->id) }}" class="item">{{ __('Edit') }}</a>
								<a @click="deleteItem($event)" href="{{ route('pages.destroy', $page->id) }}" class="item">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($pages->hasPages())
	<div class="ui fluid divider"></div>

	{{ $pages->appends($base_uri)->onEachSide(1)->links() }}
	{{ $pages->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('pages.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Pages']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="pages">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('pages') as $column)
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
	  el: '#pages',
	  data: {
	  	route: '{{ route('pages.destroy', "") }}/',
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
	  		$('#pages tbody .ui.checkbox.select').checkbox('toggle')
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
	  	},
	  	updateStatus: function(e)
	  	{	
	  		var thisEl  = $(e.target);
	  		var id 			= thisEl.data('id');

	  		$.post('{{ route('pages.status') }}', {id: id})
				.done(function(res)
				{
					if(res.success)
					{
						thisEl.checkbox('toggle');
					}
				}, 'json')
				.fail(function()
				{
					alert('Failed')
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