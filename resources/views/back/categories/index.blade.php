@extends('back.master')

@section('title', __('Categories'))


@section('content')

<div class="row main" id="categories">
	<div class="ui menu large shadowless">
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>
		
		<a class="item export">{{ __('Export') }}</a>

		<div class="ui pointing dropdown link item">
			<span class="default text">{{ __(ucfirst(request()->for ?? 'Filter')) }}</span>
			<i class="dropdown icon"></i>
			<div class="menu">
				<a href="{{ route('categories') }}" class="item">{{ __('All') }}</a>
				<a href="{{ route('categories', 'posts') }}" class="item">{{ __('Posts') }}</a>
				<a href="{{ route('categories', 'products') }}" class="item">{{ __('Products') }}</a>
			</div>
		</div>

		<div class="right menu">
			<a href="{{ route('categories.create') }}" class="item">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items categories">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>
						<div class="ui fitted checkbox">
						  <input type="checkbox" @change="selectAll">
						  <label></label>
						</div>
					</th>
					<th>ID</th>
					<th class="five columns wide">{{ __('Name') }}</th>
					<th>{{ __('Position') }}</th>
					<th>{{ __('Parent') }}</th>
					<th>{{ __('Created at') }}</th>
					<th>{{ __('Updated at') }}</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($categories as $category)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $category->id }}" @change="toogleId({{ $category->id }})">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">{{ $category->id }}</td>
					<td>{{ ucfirst($category->name) }}</td>
					<td class="center aligned">{{ $category->range }}</td>
					<td class="center aligned">{{ $category->parent_name ?? '-' }}</td>
					<td class="center aligned">{{ $category->created_at }}</td>
					<td class="center aligned">{{ $category->updated_at }}</td>
					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown left">
								<a href="{{ route('categories.edit', ['id' => $category->id, 'for' => $category->for]) }}" class="item">{{ __('Edit') }}</a>
								<a href="{{ route('categories.destroy', ['ids' => $category->id, 'for' => $category->for]) }}" class="item">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<form class="ui form modal export" action="{{ route('categories.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Categories']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="categories">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('categories') as $column)
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
	  el: '#categories',
	  data: {
	  	route: '{{ route('categories.destroy', "") }}/',
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
	  		$('#categories tbody .ui.checkbox.select').checkbox('toggle')
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