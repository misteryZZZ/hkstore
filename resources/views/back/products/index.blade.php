@extends('back.master')

@section('title', __('Products'))

@section('additional_head_tags')
<script src="{{ asset_('assets/wavesurfer.min.js') }}"></script>
@endsection


@section('content')

<div class="row main" id="products">

	<div class="ui menu shadowless">		
		<a id="bulk-delete" @click="deleteItems" :href="routes.delete+ids.join()" class="item" :class="{disabled: isDisabled}">
			{{ __('Delete') }}
		</a>

		<a class="item export">{{ __('Export') }}</a>

		<div class="right menu">
			<form action="{{ route('products') }}" method="get" id="search" class="ui transparent icon input item">
        <input class="prompt" type="text" name="keywords" placeholder="{{ __('Search') }}..." required>
        <i class="search link icon" onclick="$('#search').submit()"></i>
      </form>
			<a href="{{ route('products.create') }}" class="item ml-1">{{ __('Add') }}</a>
		</div>
	</div>
	
	<div class="ui fluid message circuclar-corner" v-if="peaksGenerated.length">
		@{{ peaksGenerated }}
	</div>

	<div class="table wrapper items products">
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
						<a href="{{ route('products', ['orderby' => 'name', 'order' => $items_order]) }}">{{ __('Name') }}</a>
					</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'price', 'order' => $items_order]) }}">{{ __('Price') }}</a>
					</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'sales', 'order' => $items_order]) }}">{{ __('Sales') }}</a>
					</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'category', 'order' => $items_order]) }}">{{ __('Category') }}</a>
					</th>
					<th>
						<div class="ui dropdown">
							<div class="text">{{ __('Type') }}</div>
							<div class="menu rounded-corner overflow-hidden">
								<a href="/admin/products" class="item">&nbsp;</a>
								<a href="{{ route('products', ['type' => '-']) }}" class="item">-</a>
								<a href="{{ route('products', ['type' => 'audio']) }}" class="item">{{ __('Audio') }}</a>
								<a href="{{ route('products', ['type' => 'video']) }}" class="item">{{ __('Video') }}</a>
								<a href="{{ route('products', ['type' => 'graphic']) }}" class="item">{{ __('Graphic') }}</a>
								<a href="{{ route('products', ['type' => 'ebook']) }}" class="item">{{ __('Ebook') }}</a>
							</div>
						</div>
					</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'active', 'order' => $items_order]) }}">{{ __('Active') }}</a>
					</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'trending', 'order' => $items_order]) }}">{{ __('Trending') }}</a>
					</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'featured', 'order' => $items_order]) }}">{{ __('Featured') }}</a>
					</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'newest', 'order' => $items_order]) }}">{{ __('Newest') }}</a>
					</th>
					<th>{{ __('Files') }}</th>
					<th>
						<a href="{{ route('products', ['orderby' => 'updated_at', 'order' => $items_order]) }}">{{ __('Updated at') }}</a>
					</th>
					<th>{{ __('Actions') }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach($products as $product)
				<tr>
					<td class="center aligned">
						<div class="ui fitted checkbox select">
						  <input type="checkbox" value="{{ $product->id }}" @change="toogleId({{ $product->id }})">
						  <label></label>
						</div>
					</td>
					<td><a href="{{ item_url($product) }}">{{ $product->id.' - '.ucfirst($product->name) }}</a></td>
					<td class="center aligned">{{ currency().' '.format_amount($product->price) }}</td>
					<td class="center aligned">{{ $product->sales }}</td>
					<td class="center aligned">{{ $product->category }}</td>
					<td class="center aligned">{{ __(ucfirst($product->type ?? '-')) }}</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="active" @if($product->active) checked @endif data-id="{{ $product->id }}" data-status="active" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="trending" @if($product->trending) checked @endif data-id="{{ $product->id }}" data-status="trending" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="featured" @if($product->featured) checked @endif data-id="{{ $product->id }}" data-status="featured" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">
						<div class="ui toggle fitted checkbox">
						  <input type="checkbox" name="newest" @if($product->newest) checked @endif data-id="{{ $product->id }}" data-status="newest" @change="updateStatus($event)">
						  <label></label>
						</div>
					</td>
					<td class="center aligned">
						@if($product->file_name)
							@if($product->is_dir)
								<a href="{{ item_folder_sync($product) }}" target="_blank"><i class="cloud large download link grey icon mx-0"></i></a>
							@else
								<i class="cloud large download link grey icon mx-0" @click="downloadFile({{ $product->id }})"></i>
							@endif
						@else
						-
						@endif
					</td>
					<td class="center aligned">{{ $product->updated_at }}</td>
					<td class="center aligned one column wide">
						<div class="ui dropdown">
							<i class="bars large grey icon mx-0"></i>
							<div class="menu dropdown left rounded-corner">
								<a href="{{ route('products.edit', $product->id) }}" class="item">{{ __('Edit') }}</a>
								<a @click="deleteItem($event)" href="{{ route('products.destroy', $product->id) }}" class="item">{{ __('Delete') }}</a>
								@if($product->type === 'audio')
								<a class="item {{ cache("peaks.{$product->id}") ? 'exists' : '' }}" @click="generateSavePeaks($event, '{{ $product->preview }}', {{ $product->id }})">{{ __('Generate peaks') }}</a>
								@endif
							</div>
						</div>
					</td>
				</tr>
				@endforeach
			</tbody>
		</table>
	</div>

	@if($products->hasPages())
	<div class="ui fluid divider"></div>

	{{ $products->appends($base_uri)->onEachSide(1)->links() }}
	{{ $products->appends($base_uri)->links('vendor.pagination.simple-semantic-ui') }}
	@endif
	
	<form action="{{ route('home.download') }}" method="post" class="d-none" id="download-file">
		@csrf
		<input type="hidden" name="itemId" v-model="itemId">
	</form>

	
	<form class="ui form modal export" action="{{ route('products.export') }}" method="POST">
		<div class="header">{{ __('Export :table_name table', ['table_name' => 'Products']) }}</div>
		<div class="content">
			<input type="hidden" name="ids" :value="ids.join()">
			<input type="hidden" name="model" value="products">
			
			<table class="ui unstackable fluid basic table mt-0">
				<thead>
					<tr>
						<th>{{ __('Column') }}</th>
						<th>{{ __('Rename column') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach(\Schema::getColumnListing('products') as $column)
					<tr>
						<td>
							<div class="ui checked checkbox">
							  <input type="checkbox" name="columns[{{ $column }}][active]" checked="checked">
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

	<div id="wavesurfer" class="d-none"></div>
</div>

<script>
	'use strict';

	var app = new Vue({
	  el: '#products',
	  data: {
	  	routes: {
	  		delete: '{{ route('products.destroy', "") }}/',
	  		export: '{{ route('products.export', "") }}/'
	  	},
	    ids: [],
	    peaksGenerated: '',
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
	  		$('#products tbody .ui.checkbox.select').checkbox('toggle')
	  	},
	  	deleteItems: function(e)
	  	{
	  		var confirmationMsg = '{{ __('Are you sure you want to delete the selected product(s)') }} ?';

	  		if(!this.ids.length || !confirm(confirmationMsg))
	  		{
	  			e.preventDefault();
	  			return false;
	  		}
	  	},
	  	deleteItem: function(e)
	  	{
	  		if(!confirm('{{ __('Are you sure you want to delete this product') }} ?'))
  			{
  				e.preventDefault();
  				return false;
  			}
	  	},
	  	generateSavePeaks: function(e, previewFile, itemId)
	  	{
			    if(/https?.+/.test(previewFile))
			    {
			    	$.post('{{ route('products.get_temp_url') }}', {url: previewFile, id: itemId})
					  .done(function(tempUrl)
					  {
					    app.savePeaks(e, tempUrl, itemId);
					  })
			    }
			    else
			    {
			    	previewFile = '/storage/previews/'+previewFile;

			    	this.savePeaks(e, previewFile, itemId)
			    }
	  	},
	  	savePeaks: function(e, previewFile, itemId)
	  	{
	  			$(e.target).closest('.ui.dropdown').toggleClass('loading', true);

		  		var wSuffer = WaveSurfer.create({
			        container: $('#wavesurfer')[0],
			        responsive: true,
			        partialRender: true,
			        waveColor: '#D9DCFF',
			        progressColor: '#4353FF',
			        cursorColor: '#4353FF',
			        barWidth: 2,
			        barRadius: 3,
			        cursorWidth: 1,
			        height: 60,
			        barGap: 2
			    });

	  			wSuffer.once('ready', () => 
			    {
			        wSuffer.exportPCM(1024, 10000, true).then(function(res)
			        {
			          $.post("{{ route('products.save_wave') }}", { peaks: res, id: itemId })
			          .done(function()
			          {
			          	$(e.target).closest('.ui.dropdown').toggleClass('loading', false);
			            app.peaksGenerated = __("Peaks for item :id has been generated and saved.", {id: itemId});

			            setTimeout(function()
			            {
			            	app.peaksGenerated = '';
			            }, 3000)
			          })
			        })
			    });

			    wSuffer.load(previewFile);
	  	},
	  	updateStatus: function(e)
	  	{	
	  		var thisEl  = $(e.target);
	  		var id 			= thisEl.data('id');
	  		var status 	= thisEl.data('status');

	  		if(['active', 'trending', 'featured', 'newest'].indexOf(status) < 0)
	  			return;

	  		$.post('{{ route('products.status') }}', {status: status, id: id})
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
	  	},
	  	downloadFile: function(itemId)
	  	{
	  		this.itemId = itemId;

	  		this.$nextTick(function()
	  		{
	  			$('#download-file').submit();
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