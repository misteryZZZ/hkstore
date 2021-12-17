@extends('back.master')

@section('title', __('Users'))


@section('content')

<div class="row main" id="users">

	<div class="ui menu shadowless">		
		<a @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a @click="notifyUsers" class="item">{{ __('Notify') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu mr-1">
			<form action="{{ route('users') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
		</div>
	</div>

	<div class="table wrapper items users">
		<table class="ui unstackable celled basic table">
			<thead>
				<tr>
					<th>&nbsp;</th>
					<th>
						<a href="{{ route('users', ['orderby' => 'email', 'order' => $items_order]) }}">{{ __('Email') }}</a>
					</th>
					<th>
						<a href="{{ route('users', ['orderby' => 'verified', 'order' => $items_order]) }}">{{ __('Verified') }}</a>
					</th>
					<th>
						<a href="{{ route('users', ['orderby' => 'blocked', 'order' => $items_order]) }}">{{ __('Blocked') }}</a>
					</th>
					<th>
						<a href="{{ route('users', ['orderby' => 'purchases', 'order' => $items_order]) }}">{{ __('Purchased items') }}</a>
					</th>
					<th>
						<a href="{{ route('users', ['orderby' => 'total_purchases', 'order' => $items_order]) }}">{{ __('Total expenses') }}</a>
					</th>
					<th>
						<a href="{{ route('users', ['orderby' => 'created_at', 'order' => $items_order]) }}">{{ __('Created at') }}</a>
					</th>
				</tr>
			</thead>
			<tbody>
				@foreach($users as $user)
				<tr>

					<td class="center aligned">
						<div class="ui fitted checkbox">
						  <input type="checkbox" value="{{ $user->id }}" @change="toogleIdEmail({{ $user->id }}, '{{ $user->email }}')">
						  <label></label>
						</div>
					</td>

					<td>{{ ucfirst($user->email) }}</td>
					
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="verified" @if($user->verified) checked @endif data-id="{{ $user->id }}" data-status="verified" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>

					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="blocked" @if($user->blocked) checked @endif data-id="{{ $user->id }}" data-status="blocked" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>

					<td class="center aligned">{{ $user->purchases }}</td>

					<td class="center aligned">{{ config('payments.currency_code').' '.$user->total_purchases }}</td>

					<td class="center aligned">{{ $user->created_at }}</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($users->hasPages())
	<div class="ui fluid divider"></div>

	{{ $users->appends($base_uri)->onEachSide(1)->links() }}
	{{ $users->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('users.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Users']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="users">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('users') as $column)
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

	<form action="{{ route('users.notify') }}" method="POST" class="ui modal form notify">
		<div class="header">{{ __('Send notification') }}</div>

		<div class="content">
			<input type="hidden" name="emails" :value="emails.join()">
			<div class="field">
				<label>{{ __('Notification') }}</label>
				<textarea v-model="notification" cols="30" rows="10"></textarea>
			</div>
		</div>

		<div class="actions">
			<button class="ui circular button large yellow approve" type="button">{{ __('Submit') }}</button>
			<button class="ui circular button large blue cancel" type="button">{{ __('Cancel') }}</button>
		</div>
	</form>
</div>

<script>
	'use strict';
	
	var app = new Vue({
	  el: '#users',
	  data: {
	  	route: '{{ route('users.destroy', "") }}/',
	    ids: [],
	    emails: [],
	    notification: '',
	    isDisabled: true
	  },
	  methods: {
	  	notifyUsers: function()
	  	{
	  		$('form.notify').modal({
	  			center: true,
	  			closable: false,
	  			onApprove: function()
	  			{
	  				var payload = {emails: app.emails, notification: app.notification};

	  				Vue.nextTick(function()
	  				{
		  				$.post('{{ route('users.notify') }}', payload)
		  				.done(function(data)
		  				{

		  				})	
	  				})
	  			}
	  		})
	  		.modal('show')
	  	},
	  	toogleIdEmail: function(id, email)
	  	{
	  		if(this.ids.indexOf(id) >= 0)
  			{
  				this.ids.splice(this.ids.indexOf(id), 1);
  				this.emails.splice(this.emails.indexOf(email), 1);
  			}
	  		else
	  		{
	  			this.emails.push(email);
	  			this.ids.push(id);
	  		}
	  	},
	  	deleteItems: function(e)
	  	{
	  		if(!this.ids.length)
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	updateStatus: function(e)
	  	{	
	  		var thisEl  = $(e.target);
	  		var id 			= thisEl.data('id');
	  		var status 	= thisEl.data('status');
	  		var val  		= thisEl.prop('checked') ? 1 : 0;

	  		if(['verified', 'blocked'].indexOf(status) < 0)
	  			return;

	  		$.post('{{ route('users.status') }}', {status: status, id: id, val: val});
	  	},
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