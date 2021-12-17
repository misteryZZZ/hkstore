@extends('back.master')

@section('title', __('Keys, Accounts, Licenses, ...'))


@section('content')

<div class="row main" id="keys">

	@if(session('message'))
	<div class="ui fluid positive message">
		<i class="close icon"></i>
		{{ session('message') }}
	</div>
	@endif

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<form action="{{ route('keys') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
			<a href="{{ route('keys.create') }}" class="item ml-1">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items keys">
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
						<a href="{{ route('keys', ['orderby' => 'code', 'order' => $items_order]) }}">{{ __('Code') }}</a>
						<div><small>({{ __('CTRL or CMD + S to save') }})</small></div>
					</th>
					<th>
						<a href="{{ route('keys', ['orderby' => 'product_name', 'order' => $items_order]) }}">{{ __('Product') }}</a>
					</th>
					<th>
						<a href="{{ route('keys', ['orderby' => 'user_email', 'order' => $items_order]) }}">{{ __('Purchased by') }}</a>
					</th>
					<th>
						<a href="{{ route('keys', ['orderby' => 'purchased_at', 'order' => $items_order]) }}">{{ __('Purchased at') }}</a>
					</th>
					<th>
						<a href="{{ route('keys', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($keys as $key)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $key->id }}" @change="toogleId({{ $key->id }})">
						  <label></label>
						</div>
					</td>
					<td class="ui form">
						<textarea data-id="{{ $key->id }}" data-product-id="{{ $key->product_id }}" rows="2">{{ $key->code }}</textarea>
					</td>
					<td><a href="{{ item_url(['slug' => $key->product_slug, 'id' => $key->product_id]) }}">{!! $key->product_name !!}</a></td>
					<td class="center aligned">{!! $key->user_email ?? '-' !!}</td>
					<td class="center aligned">{{ $key->purchased_at ?? '-' }}</td>
					<td class="center aligned">{{ $key->updated_at }}</td>
					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown left rounded-corner">
								<a href="{{ route('keys.edit', $key->id) }}" class="item">{{ __('Edit') }}</a>
								<a @click="deleteItem($event)" href="{{ route('keys.destroy', $key->id) }}" class="item">{{ __('Delete') }}</a>
								<a @click="voidPurchase($event, {{ $key->id }})" class="item">{{ __('Void purchase') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($keys->hasPages())
	<div class="ui fluid divider"></div>

	{{ $keys->appends($base_uri)->onEachSide(1)->links() }}
	{{ $keys->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif


	<form class="ui form modal export" action="{{ route('keys.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Keys']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="keys">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('key_s') as $column)
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
	  el: '#keys',
	  data: {
	  	route: '{{ route('keys.destroy', "") }}/',
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
	  		$('#keys tbody .ui.checkbox.select').checkbox('toggle')
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
	  	updateCode: function(e)
	  	{
	  		var $this = $(e.target);
	  		var id = $this.data('id');
	  		var productId = $this.data('product-id');
	  		var code = $this.val().trim();

	  		if (!(event.which == 115 && event.ctrlKey) && !(event.which == 19)) return true;

  			$.post('/admin/keys/update_async', {id: id, code: code, product_id: productId})
  			.done(function()
  			{
  				$this.transition('pulse');
  			})
	  	},
	  	voidPurchase: function(e, keyId)
	  	{
	  	   $.post('/admin/keys/void_purchase', {id: keyId})
  			.done(function()
  			{
  			    
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
	
	    

		$('#keys textarea').on('keydown', function(e) 
		{
		    if((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
		    {		        
		        var $this = $(this);
			  		var id = $this.data('id');
			  		var productId = $this.data('product-id');
			  		var code = $this.val().trim();

		  			$.post('/admin/keys/update_async', {id: id, code: code, product_id: productId})
		  			.done(function()
		  			{
		  				$this.transition('pulse');
		  			})
		  			.fail(function(res)
		  			{
		  				try
		  				{
		  					var errs = res.responseJSON.errors;

		  					alert(Object.values(errs).join(','))
		  				}
		  				catch(err)
		  				{

		  				}
		  			})

		  			e.preventDefault();

		        return false;
		    }
		    else
		    {
		        return true;
		    }
		})

</script>
@endsection