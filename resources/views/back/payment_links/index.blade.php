@extends('back.master')

@section('title', __('Payment links'))


@section('content')

<div class="row main" id="payment_links">

	@if(session('message'))
	<div class="ui positive message circular-corner">
		{{ session('message') }}
		<i class="close icon"></i>
	</div>
	@endif

	<div class="ui positive message circular-corner" v-if="response.length">
		@{{ response }}
	</div>

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<a href="{{ route('payment_links.create') }}" class="item ml-1">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items payment_links">
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
						<a href="{{ route('payment_links', ['orderby' => 'name', 'order' => $items_order]) }}">{{ __('Name') }}</a>
					</th>
					<th class="five columns wide">
						<a href="{{ route('payment_links', ['orderby' => 'processor', 'order' => $items_order]) }}">{{ __('Processor') }}</a>
					</th>
					<th class="five columns wide">
						<a href="{{ route('payment_links', ['orderby' => 'short_link', 'order' => $items_order]) }}">{{ __('Short link') }}</a>
					</th>
					<th>
						<a href="{{ route('payment_links', ['orderby' => 'user', 'order' => $items_order]) }}">{{ __('User') }}</a>
					</th>
					<th>{{ __('Amount') }}</th>
					<th>{{ __('Status') }}</th>
					<th>
						<a href="{{ route('payment_links', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Created at') }}</a>
					</th>
					{{-- <th>
						<a href="{{ route('payment_links', ['orderby' => 'expired', 'order' => $items_order]) }}">{{ __('Expired') }}</a>
					</th> --}}
					<th>-</th>
				</tr>
			</thead>
			<tbody>
				@foreach($payment_links as $payment_link)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $payment_link->id }}" @change="toogleId({{ $payment_link->id }})">
						  <label></label>
						</div>
					</td>
					<td class="one column wide">{{ ucfirst($payment_link->name) }}</td>
					<td>{{ ucfirst($payment_link->processor) }}</td>
					<td class="center aligned">{{ urldecode($payment_link->short_link) }}</td>
					<td class="center aligned">{{ $payment_link->user }}</td>
					<td class="center aligned">{{ $payment_link->amount }} <sup>({{ $payment_link->currency }})</sup></td>
					<td class="center aligned">{{ __(mb_ucfirst($payment_link->status)) }}</td>
					<td class="center aligned">{{ $payment_link->updated_at }}</td>
					{{-- <td class="center aligned">{{ $payment_link->expired ? __('Yes') : __('No') }}</td> --}}
					<td><button class="ui yellow small button circular btn-{{ $payment_link->id }}" {{ $payment_link->status !== '-' ? 'disabled' : '' }} type="button" @click="intPaymentLinkModal($event, {{ $payment_link->id }})">{{ $payment_link->status !== '-' ? __('Sent') : __('Send') }}</button></td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	<form class="ui form modal payment-link-modal" :target="target" action="{{ route('payment_links.send') }}" method="POST">
		<input type="hidden" name="id" v-model="paymentLinkId">
		<input type="hidden" name="action" v-model="action">
		<div class="header">{{ __('Send payment link') }}</div>
		<div class="content">
			<div class="field">
				<label>{{ __('Subject') }}</label>
				<input type="text" name="subject" v-model="subject" placeholder="{{ __('Subject') }}">
			</div>
			<div class="field">
				<label>{{ __('Text') }}</label>
				<input type="text" name="text" v-model="text" placeholder="{{ __('Text') }}">
			</div>
			<div class="field">
				<button class="ui circular yellow button" type="button" @click="sendPaymentLink('send')">{{ __('Send') }}</button>
				<button class="ui circular red button" type="button" @click="sendPaymentLink('render')">{{ __('Preview') }}</button>
			</div>
		</div>
	</form>
	
	@if($payment_links->hasPages())
	<div class="ui fluid divider"></div>

	{{ $payment_links->appends($base_uri)->onEachSide(1)->links() }}
	{{ $payment_links->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('payment_links.export') }}" method="payment_link">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'payment_links']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="payment_links">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('payment_links') as $column)
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
	  el: '#payment_links',
	  data: {
	  	route: '{{ route('payment_links.destroy', "") }}/',
	    ids: [],
	    isDisabled: true,
	    details: {!! json_encode($payment_links->pluck('details', 'id')->toArray()) !!},
	    action: 'send',
	    paymentLinkId: null,
	    subject: null,
	    text: null,
	    target: '',
	    response: ''
	  },
	  methods: {
	  	sendPaymentLink: function(action)
	  	{
	  		this.action = action;
	  		this.target = action === 'send' ? '' : '_blank';

	  		Vue.nextTick(function()
	  		{
	  			if(action === 'send')
	  			{
		  			$('.modal.payment-link-modal').modal('hide');

		  			var payload = {id: app.paymentLinkId, subject: app.subject, text: app.text, action: app.action};

		  			$('button.btn-' + app.paymentLinkId).toggleClass('disabled loading', true);

		  			$.post('{{ route('payment_links.send') }}', payload, 'json')
		  			.done(function(data)
		  			{
		  				$('button.btn-' + app.paymentLinkId).toggleClass('disabled loading', false);
		  				
		  				app.response = data.response;

		  				setTimeout(function()
		  				{
		  					app.response = '';
		  				}, 3000)
		  			})	
	  			}
	  			else
	  			{
	  				$('.payment-link-modal').submit();
	  			}
	  		})

	  		return;
	  	},
	  	intPaymentLinkModal: function(e, paymentLinkId)
	  	{
	  		if($(e.target).prop('disbaled'))
	  			return false;

	  		this.paymentLinkId = paymentLinkId;

	  		Vue.nextTick(function()
	  		{
	  			$('.modal.payment-link-modal').modal('show');
	  		})
	  	},
	  	toogleId: function(id)
	  	{
	  		if(this.ids.indexOf(id) >= 0)
	  			this.ids.splice(this.ids.indexOf(id), 1);
	  		else
	  			this.ids.push(id);
	  	},
	  	selectAll: function()
	  	{
	  		$('#payment_links tbody .ui.checkbox.select').checkbox('toggle')
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