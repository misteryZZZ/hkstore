@extends('back.master')

@section('title', __('Transactions'))


@section('content')

@if(session('response'))
<div class="ui fluid small positive bold message">
	<i class="close icon"></i>
	{{ session('response') }}
</div>
@endif

<div class="row main" id="transactions">

	<div class="ui menu shadowless">
		<a id="bulk-delete" @click="deleteItems" :href="route+ids.join()" class="item" :class="{disabled: isDisabled}">{{ __('Delete') }}</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<form action="{{ route('transactions') }}" method="get" id="search" class="ui transparent icon input item mr-1">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }} ..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>

      <a href="{{ route('transactions.create') }}" class="item ml-1">{{ __('Create') }}</a>
		</div>
	</div>
	
	<div class="table wrapper items transactions">
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
						<a href="{{ route('transactions', ['orderby' => 'buyer', 'order' => $items_order]) }}">{{ __('Buyer') }}</a>
					</th>
					<th>
						<a href="{{ route('transactions', ['orderby' => 'amount', 'order' => $items_order]) }}">{{ __('Amount') }}</a>
					</th>
					<th>
						<a href="{{ route('transactions', ['orderby' => 'processor', 'order' => $items_order]) }}">{{ __('Processor') }}</a>
					</th>
					<th>
						<a href="{{ route('transactions', ['orderby' => 'status', 'order' => $items_order]) }}">{{ __('Paid') }}</a>
					</th>
					<th>
						<a href="{{ route('transactions', ['orderby' => 'refunded', 'order' => $items_order]) }}">{{ __('Refunded') }}</a>
					</th>					<th>
						<a href="{{ route('transactions', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($transactions as $transaction)
				<tr id="{{ $transaction->id }}">
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $transaction->id }}" @change="toogleId({{ $transaction->id }})">
						  <label></label>
						</div>
					</td>

					<td class="left aligned">{{ $transaction->buyer ?? __('Guest') }}</td>

					<td class="center aligned">{{ price($transaction->amount) }}</td>
					
					<td class="center aligned">{{ ucfirst($transaction->processor) }}</td>

					<td class="center aligned" >
						<div class="ui toggle fitted checkbox" title="{{ mb_ucfirst($transaction->status) }}">
						  <input type="checkbox" @if($transaction->status === 'paid') checked @endif data-prop="status" data-id="{{ $transaction->id }}" @change="updateProp($event)">
						  <label></label>
						</div>
					</td>

					<td class="center aligned" >
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" @if($transaction->refunded) checked @endif data-prop="refunded" data-id="{{ $transaction->id }}" @change="updateProp($event)">
						  <label></label>
						</div>
					</td>

					<td class="center aligned">{{ $transaction->updated_at }}</td>

					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars icon mx-0"></i>
							<div class="menu dropdown rounded-corner left">
								<a href="{{ route('transactions.show', $transaction->id) }}" class="item">{{ __('Details') }}</a>

								@if(!$transaction->refunded)
								<a class="item" @click="confirmRefund($event)" href="{{ route('transactions.mark_as_refunded', ['id' => $transaction->id, 
																																								 'processor' => $transaction->processor]) }}">
									{{ __('Mark as refunded') }}
								</a>
								@endif

								@if($transaction->processor === 'manual')
								<a class="item" href="{{ route('transactions.edit', ['id' => $transaction->id]) }}">
									{{ __('Edit') }}
								</a>
								@endif

								<a class="item" @click="deleteItem($event)" href="{{ route('transactions.destroy', $transaction->id) }}">{{ __('Delete') }}</a>
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>
	
	@if($transactions->hasPages())
	<div class="ui fluid divider"></div>

	{{ $transactions->appends($base_uri)->onEachSide(1)->links() }}
	{{ $transactions->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif

	<form class="ui form modal export" action="{{ route('transactions.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Transactions']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="transactions">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('transactions') as $column)
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
	  el: '#transactions',
	  data: {
	  	route: '{{ route('transactions.destroy', "") }}/',
	    ids: [],
	    isDisabled: true,
	    transaction_id: null
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
	  		$('#transactions tbody .ui.checkbox.select').checkbox('toggle')
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
	  	confirmRefund: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to mark this transaction as refunded ?') }}'))
	  		{
	  			e.preventDefault();
  				return false;
	  		}
	  	},
	  	updateProp: function(e)
	  	{	
	  		var thisEl = $(e.target);
	  		var id   = thisEl.data('id');
	  		var prop = thisEl.data('prop');

	  		if(['status', 'refunded'].indexOf(prop) < 0)
	  			return;

	  		$.post('{{ route('transactions.update_prop') }}', {prop: prop, id: id})
				.done(function(res)
				{
					if(res.response)
					{
						thisEl.checkbox('toggle');
					}
				})
				.fail(function(data)
				{
					alert(data.responseJSON.message)
				})
	  	}
	  },
	  watch: {
	  	ids: function(val)
	  	{
	  		this.isDisabled = !val.length;
	  	},
	  	transaction_id: function(val)
	  	{
	  		$('#refund').modal(!val ? 'hide' : 'show')
	  	}
	  }
	})

	$('.ui.modal').modal({closable: false})
</script>
@endsection