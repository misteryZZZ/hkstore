@extends('back.master')

@section('title', __('Support'))


@section('content')

<div class="row main" id="supports">
	
	@if(session('unseen_messages'))
	<div class="ui positive message bold">
		<i class="close icon mx-0"></i>
		{{ session('unseen_messages') }}
	</div>
	@endif

	<div class="ui menu shadowless">
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<form action="{{ route('support') }}" method="get" id="search" class="ui transparent icon input item mr-1">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>
	
	<div class="table wrapper items supports">
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
						<a href="{{ route('support', ['orderby' => 'email', 'order' => $items_order]) }}">{{ __('Email') }}</a>
					</th>
					<th class="five columns wide">{{ __('Subject') }}</th>
					<th>
						<a href="{{ route('support', ['orderby' => 'read', 'order' => $items_order]) }}">{{ __('Read') }}</a>
					</th>
					<th>
						<a href="{{ route('support', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Created at') }}</a>
					</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($support_messages as $message)
				<tr id="{{ $message->id }}">
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $message->id }}" @change="toogleId({{ $message->id }})">
						  <label></label>
						</div>
					</td>
					<td>{{ ucfirst($message->email) }}</td>
					<td>{{ ucfirst($message->subject) }}</td>
					<td class="center aligned">
						<span class="ui circular basic @if(!$message->read) blue @endif label support-message" 
							data-id="{{ $message->id }}" 
							data-html="{{ nl2br($message->content) }}">
							{{ __('Read') }}
						</span>
					</td>
					<td class="center aligned">{{ $message->created_at }}</td>
					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu rounded-corner dropdown left">
								<a class="item" @click="replyToMessage('{{ $message->email }}')">{{ __('Reply') }}</a>
								<a @click="deleteItem($event)" href="{{ route('support.destroy', $message->id) }}" class="item">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	@if($support_messages->hasPages())
	<div class="ui fluid divider"></div>

	{{ $support_messages->appends($base_uri)->onEachSide(1)->links() }}
	{{ $support_messages->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<div class="ui tiny modal" id="message-form">
		<form method="post" class="ui large form content" spellcheck="false">
			<div class="field">
				<label>{{ __('Message') }}</label>
				<textarea name="message" cols="30" rows="10" class="rounded-corner"></textarea>
			</div>
			<div class="field">
				<label>{{ __('Subject') }}</label>
				<input type="text" name="subject" class="circular-corner">
				<input type="hidden" name="email" v-model="email">
			</div>
		</form>
		<div class="content actions">
			<a class="ui teal large approve circular button">{{ __('Send') }}</a>
			<a class="ui yellow large cancel circular button">{{ __('Cancel') }}</a>
		</div>
	</div>

	<form class="ui form modal export" action="{{ route('support.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Support']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="support">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('support') as $column)
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
	  el: '#supports',
	  data: {
	  	route: '{{ route('support.destroy', "") }}/',
	    ids: [],
	    isDisabled: true,
	    email: ''
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
	  		$('#supports tbody .ui.checkbox').checkbox('toggle')
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
	  	replyToMessage: function(email)
	  	{
	  		this.email = email;
	  		Vue.nextTick(function()
	  		{
	  			$('#message-form').modal('show')
	  			.modal({
	  				closable: false,
	  				onApprove: function()
	  				{
	  					var formData = $('form', this).serialize();

	  					$.post('{{ route('support.create') }}', formData)
	  					.done(function(data)
	  					{
	  						if(data.status === true)
	  						{
	  							alert(__('Message sent.'))
	  						}
	  					})
	  					.fail(function(data)
	  					{
	  						alert(JSON.stringify(data.responseJSON))
	  					})
	  				},
	  				onDeny: function()
	  				{

	  				}
	  			});
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

	$(function()
	{
		$('.support-message').popup({
	    on: 'click',
	    onShow: function(el)
	    {
	    	var id = $(el).data('id');

	    	if($(el).hasClass('blue'))
	    	{
	    		$.post('{{ route('support.status') }}', {id: id}, null, 'json')
	    		.done(function()
	    		{
	    			$(el).removeClass('blue')
	    		})
	    		.fail(function()
	    		{
	    			alert('{{ __('Failed to update read status') }}');
	    		})
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
	})

</script>
@endsection