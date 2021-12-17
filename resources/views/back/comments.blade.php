@extends('back.master')

@section('title', $title)


@section('content')

<div class="row main" id="comments">

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>
		
		<div class="right menu mr-1">
			<form action="{{ route('comments') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>
	
	<div class="table wrapper items comments">
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
						<a href="{{ route('comments', ['orderby' => 'item_name', 'order' => $items_order]) }}">{{ __('Items') }}</a>
					</th>
					<th>
						<a href="{{ route('comments', ['orderby' => 'user_name', 'order' => $items_order]) }}">{{ __('Name') }}</a>
					</th>
					<th>
						<a href="{{ route('comments', ['orderby' => 'user_email', 'order' => $items_order]) }}">{{ __('Email') }}</a>
					</th>
					<th>
						Comment
					</th>
					<th>
						<a href="{{ route('comments', ['orderby' => 'approved', 'order' => $items_order]) }}">{{ __('Approved') }}</a>
					</th>
					<th>
						<a href="{{ route('comments', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Posted at') }}</a>
					</th>
				</tr>
			</thead>
			<tbody>
				@foreach($comments as $comment)
				<tr id="{{ $comment->id }}">
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $comment->id }}" @change="toogleId({{ $comment->id }})">
						  <label></label>
						</div>
					</td>
					<td><a href="{{ route('home.product', ['id' => $comment->item_id, 'slug' => $comment->item_slug]) }}#support">{{ ucfirst($comment->item_name) }}</a></td>
					<td class="center aligned">{{ $comment->user_name }}</td>
					<td class="center aligned">{{ $comment->user_email }}</td>
					<td class="center aligned">
						<span class="ui basic circular blue label comment-content" data-html="{{ nl2br($comment->body) }}">
							{{ __('Read') }}
						</span>
					</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
							<input type="checkbox" name="approved" @if($comment->approved) checked @endif  @change="updateStatus($event)"
							data-id="{{ $comment->id }}" data-item-id="{{ $comment->item_id }}" data-user-id="{{ $comment->user_id }}">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">{{ $comment->created_at }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<div class="ui fluid divider"></div>

	{{ $comments->appends($base_uri)->onEachSide(1)->links() }}
	{{ $comments->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}

	<form class="ui form modal export" action="{{ route('comments.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Comments']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="comments">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('comments') as $column)
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
	  el: '#comments',
	  data: {
	  	route: '{{ route('comments.destroy', "") }}/',
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
	  		$('#comments tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected comment(s)') }} ?';

	  		if(!this.ids.length || !confirm(confirmationMsg))
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	deleteItem: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to delete this comment') }} ?'))
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

	  		$.post('{{ route('comments.status') }}', payload)
				.done(function(res)
				{
					if(res.success)
					{
						thisEl.checkbox('toggle');
					}
				}, 'json')
				.fail(function()
				{
					alert('{{ __('Failed') }}')
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
		$('.comment-content').popup()
	})
</script>
@endsection