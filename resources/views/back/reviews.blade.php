@extends('back.master')

@section('title', $title)


@section('content')

<div class="row main" id="reviews">

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>
		
		<div class="right menu mr-1">
			<form action="{{ route('reviews') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="Search ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>
	
	<div class="table wrapper items reviews">
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
						<a href="{{ route('reviews', ['orderby' => 'item_name', 'order' => $items_order]) }}">{{ __('Item') }}</a>
					</th>
					<th>
						<a href="{{ route('reviews', ['orderby' => 'user_name', 'order' => $items_order]) }}">{{ __('Name') }}</a>
					</th>
					<th>
						<a href="{{ route('reviews', ['orderby' => 'user_email', 'order' => $items_order]) }}">{{ __('Email') }}</a>
					</th>
					<th>
						<a href="{{ route('reviews', ['orderby' => 'rating', 'order' => $items_order]) }}">{{ __('Rating') }}</a>
					</th>
					<th>
						{{ __('Review') }}
					</th>
					<th>
						<a href="{{ route('reviews', ['orderby' => 'approved', 'order' => $items_order]) }}">{{ __('Approved') }}</a>
					</th>
					<th>
						<a href="{{ route('reviews', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Posted at') }}</a>
					</th>
				</tr>
			</thead>
			<tbody>
				@foreach($reviews as $review)
				<tr id="{{ $review->id }}">
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $review->id }}" @change="toogleId({{ $review->id }})">
						  <label></label>
						</div>
					</td> 
					<td><a href="{{ route('home.product', ['id' => $review->item_id, 'slug' => $review->item_slug]) }}#reviews">{{ ucfirst($review->item_name) }}</a></td>
					<td class="center aligned">{{ $review->user_name }}</td>
					<td class="center aligned">{{ $review->user_email }}</td>
					<td class="center aligned">
						<span class="ui star rating" data-rating="{{ $review->rating }}" data-max-rating="5"></span>
					</td>
					<td class="center aligned">
						@if($review->content)
						<span class="ui basic circular blue label review-content" data-html="{{ nl2br($review->content) }}">{{ __('Read') }}</span>
						@else
						-
						@endif
					</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
							<input type="checkbox" name="approved" @if($review->approved) checked @endif @click="updateStatus($event)"
										 data-id="{{ $review->id }}" data-item-id="{{ $review->item_id }}" data-user-id="{{ $review->user_id }}">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">{{ $review->created_at }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	@if($reviews->hasPages())
	<div class="ui fluid divider"></div>

	{{ $reviews->appends($base_uri)->onEachSide(1)->links() }}
	{{ $reviews->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('reviews.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Reviews']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="reviews">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('reviews') as $column)
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
	  el: '#reviews',
	  data: {
	  	route: '{{ route('reviews.destroy', "") }}/',
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
	  		$('#reviews tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected review(s)') }} ?';

	  		if(!this.ids.length || !confirm(confirmationMsg))
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	deleteItem: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to delete this review') }} ?'))
  			{
  				e.preventDefault();
  				return false;
  			}
	  	},
	  	updateStatus: function(e)
	  	{	
	  		var thisEl  = $(e.target);

				var payload = {
					'id': thisEl.data('id'),
					'item_id': thisEl.data('item-id'),
					'user_id': thisEl.data('user-id')
				};
				
	  		$.post('{{ route('reviews.status') }}', payload)
				.done(function(res)
				{
					if(res.success)
					{
						thisEl.checkbox('toggle');
					}
				}, 'json')
	  	}
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

	$(function()
	{
		$('.ui.rating').rating('disable');

		$('.review-content')
	  .popup()
	})
</script>
@endsection