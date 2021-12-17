@extends('back.master')

@section('title', $title)


@section('content')

<div class="row main" id="subscriptions">

	<div class="ui menu shadowless">		
		<a @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>
		<div class="item ui floating dropdown">
			<div class="text">{{ __('Send renewal payment link') }}</div>
			<i class="dropdown icon"></i>
			<div class="menu">
				<a @click="createSendRenewalPaymentLink($event)" class="item" :class="{disabled: isDisabled}">{{ __('To selected users') }}</a>
				<a @click="createSendRenewalPaymentLink($event, true)" class="item" >{{ __('To users with expiring subscription') }}</a>
			</div>
		</div>
	</div>

	@if(session('message'))
	<div class="ui fluid message">
			<i class="close icon"></i>
			{{ session('message') }}
	</div>
	@endif


	<div class="ui fluid message response d-none">
			<i class="close icon"></i>
			<div class="content"></div>
	</div>

	<div class="table wrapper items subscriptions">
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
						<a href="{{ route('users_subscriptions', ['orderby' => 'username', 'order' => $items_order]) }}">{{ __('Name') }}</a>
					</th>
					<th>
						<a href="{{ route('users_subscriptions', ['orderby' => 'name', 'order' => $items_order]) }}">{{ __('Subscription') }}</a>
					</th>
					<th>
						<a href="{{ route('users_subscriptions', ['orderby' => 'starts_at', 'order' => $items_order]) }}">{{ __('Starts at') }}</a>
					</th>
					<th>
						<a href="{{ route('users_subscriptions', ['orderby' => 'ends_at', 'order' => $items_order]) }}">{{ __('Ends at') }}</a>
					</th>
					<th>
						<a href="{{ route('users_subscriptions', ['orderby' => 'remaining_days', 'order' => $items_order]) }}">{{ __('Remaining days') }}</a>
					</th>
					<th>
						<a href="{{ route('users_subscriptions', ['orderby' => 'downloads', 'order' => $items_order]) }}">{{ __('Downloads') }}</a>
					</th>
					<th>
						<a href="{{ route('users_subscriptions', ['orderby' => 'expired', 'order' => $items_order]) }}">{{ __('Status') }}</a>
					</th>
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
					<td class="center aligned">
						{{ $subscription->username }}
					</td>
					<td class="center aligned">
						{{ $subscription->name }}
					</td>
					<td class="center aligned">
						{{ $subscription->starts_at }}
					</td>
					<td class="center aligned">
						{{ $subscription->ends_at ?? '-' }}
					</td>
					<td class="center aligned">
						{{ $subscription->ends_at ? $subscription->remaining_days : '∞' }}
					</td>
					<td class="center aligned">
						{{ $subscription->downloads }}
					</td>
					<td class="center aligned">
					  @if($subscription->status == 'pending')
					    <span class="ui basic circular fluid label orange">{{ __('Pending') }}</span>
						@elseif($subscription->refunded)
							<span class="ui basic circular fluid label red">{{ __('Refunded') }}</span>
						@elseif(!$subscription->expired)
							<span class="ui basic circular fluid label teal">{{ __('Active') }}</span>
						@else
							<span class="ui basic circular fluid label red">{{ __('Expired') }}</span>
						@endif
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($subscriptions->hasPages())
	<div class="ui fluid divider"></div>

	{{ $subscriptions->appends($base_uri)->onEachSide(1)->links() }}
	{{ $subscriptions->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif
</div>

<script>
	'use strict';

	var app = new Vue({
	  el: '#subscriptions',
	  data: {
	  	route: '{{ route('users_subscriptions.destroy', "") }}/',
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
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected subscriptions(s)') }} ?';

	  		if(!this.ids.length || !confirm(confirmationMsg))
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	deleteItem: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to delete this subscription') }} ?'))
  			{
  				e.preventDefault();
  				return false;
  			}
	  	},
	  	createSendRenewalPaymentLink(e, all = false)
	  	{
	  		if(!all && !this.ids.length)
	  		{
	  			e.preventDefault();
	  			return;
	  		}

	  		$(e.target).closest('.ui.dropdown').toggleClass('disabled loading', true);

	  		$.post('{{ route('users_subscriptions.sendRenewalPaymentLink') }}', {ids: this.ids})
	  		.done(function(data)
	  		{ 	 
	  			if(data.errors)
	  			{
	  				for(var k in data.errors)
	  				{
	  					$('.ui.message.response .content').html('<p>' + data.errors[k] +'</p>')
	  				}
	  			}

	  			if(data.success)
	  			{
	  				for(var k in data.success)
	  				{
	  					$('.ui.message.response .content').html('<p>' + data.success[k] +'</p>')
	  				}
	  			}

	  			if(data.errors != undefined || data.success != undefined)
	  			{
	  				$('.ui.message.response').removeClass('d-none').removeClass('transition hidden');
	  			}
	  		})
	  		.always(function()
	  		{
	  			$(e.target).closest('.ui.dropdown').toggleClass('disabled loading', false);
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