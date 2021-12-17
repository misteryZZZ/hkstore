@extends('back.master')

@section('title', __('Create and send newsletter'))

@section('additional_head_tags')
<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
@endsection

@section('content')

<form class="ui large form" method="post" action="{{ route('subscribers.newsletter.send') }}" id="newsletter" spellcheck="false">
	@csrf
	
	@if($errors->any())
    @foreach ($errors->all() as $error)
		<div class="ui negative fluid small message circular-corner bold">
			<i class="times icon close"></i>
			{{ $error }}
		</div>
    @endforeach
	@endif

	@if(session('newsletter_sent'))
	<div class="ui positive fluid small bold message circular-corner bold">
		<i class="times icon close"></i>
		{{ session('newsletter_sent') }}
	</div>
	@endif
	
	<div class="fields-wrapper">

		<div class="field">
			<label>{{ __('Subscribers') }}</label>
			<div class="ui floating multiple selection scrolling search fluid dropdown">
				<input type="hidden" name="emails" value="{{ old('emails') }}">
				<div class="text"></div>
				<i class="dropdown icon"></i>
				<div class="menu">
					@foreach($emails as $email)
					<a data-value="{{ $email }}" class="item">{{ $email }}</a>
					@endforeach
				</div>
			</div>
		</div>

		<div class="field">
			<label>{{ __('Subject') }}</label>
			<input type="text" name="subject" required value="{{ old('subject') }}" class="circular-corner">
		</div>

		<div class="field">
			<label>{{ __('Tool') }}</label>
			<div class="ui floating selection fluid dropdown tool">
				<input type="hidden" name="tool" :value="selectedTool">
				<div class="text">...</div>
				<i class="dropdown icon"></i>
				<div class="menu">
					<a data-value="html_editor" class="item">{{ __('HTML editor') }}</a>
					<a data-value="selections" class="item">{{ __('Selections') }}</a>
				</div>
			</div>
		</div>

		<div class="field mb-0" :class="{'d-none': selectedTool === 'selections'}" v-cloak>
			<label>{{ __('HTML editor') }}</label>
			<div class="field rounded-corner">
				<textarea name="newsletter" id="summernote" spellcheck="false" cols="30">{{ old('newsletter') }}</textarea>
			</div>
		</div>

		<div class="field mb-0" id="selections" :class="{'d-none': selectedTool === 'html_editor'}" v-cloak>
			<label>{{ __('Selections') }}</label>
			
			<div class="items-selection">
				<input type="text" class="selection-title" name="selections[titles][]" placeholder="{{ __('Selection title') }}">
				<div class="ui multiple floating selection search fluid dropdown mt-1-hf items-search">
					<input type="hidden" name="selections[ids][]">
					<div class="default text">{{ __('Search products') }}</div>
					<i class="dropdown icon"></i>
					<div class="menu"></div>
				</div>
			</div>
			
			<button type="button" id="add-selection" class="ui basic icon circular button small mt-1"><i class="plus icon mx-0"></i></button>
			<button type="button" id="remove-selection" class="ui basic icon circular button small mt-1"><i class="close icon mx-0"></i></button>
		</div>
	</div>

	<div class="field right aligned mt-1">
		<a href="{{ route('subscribers') }}" class="ui teal right large labeled icon circular button mx-0 left floated">
		  <i class="times icon mx-0"></i>
		  {{ __('Cancel') }}
		</a>

		<button type="submit" name="action" value="send" class="ui pink large labeled icon circular button mx-1-hf"
						onclick="$(this).closest('form').attr('target', '_self')">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Send') }}
		</button>

		<button type="submit" name="action" value="render" class="ui yellow large labeled icon circular button mx-0" title="{{ __('Visualize') }}" onclick="$(this).closest('form').attr('target', '_blank')">
		  <i class="eye icon mx-0"></i>
		  {{ __('Preview') }}
		</button>
	</div>
</form>

<script>
	'use strict';
	
	var products = {},
			titles = {},
			productsSelections = {};

	var app = new Vue({
		el: '#newsletter',
		data: {
			products: {},
			titles: {},
			coversBase: '{{ asset('storage/covers') }}',
			productBaseUrl: '{{ route('home.product', ['id' => ' ', 'slug' => ' ']) }}',
			currency: '{{ currency('symbol') }}',
			selectedTool: 'selections'
		},
		methods: {
			updateApp: function()
			{
				this.products = productsSelections;
				this.titles 	= titles;

				this.$forceUpdate();
			},
			chunkArr: function(arr, n) 
			{
			  return  arr.reduce(function(p, cur, i)
			              {
			                  (p[i/n|0] = p[i/n|0] || []).push(cur);
			                  return p;
			              },[]);
			}
		},
		watch: {

		}
	});


	$.fn.itemsDropdown = function()
	{
		this.dropdown({
			onAdd: function(addedValue, addedText, $addedChoice)
			{
				var keys = Object.keys(productsSelections);
				var i = $('.items-search').index($(this)).toString();

				if(keys.indexOf(i) < 0)
				{
					productsSelections[i] = {};
				}
				
				productsSelections[i][addedValue] = products[addedValue];
			},
			onRemove: function(removedValue, removedText, $removedChoice)
			{
				var keys = Object.keys(productsSelections);
				var i = $('.items-search').index($(this)).toString();

				if(keys.indexOf(i) >= 0)
				{
					var items = productsSelections[i];

					items = Object.keys(items).reduce(function(carry, key)
									{
										if(key != removedValue)
											carry[key] = items[key];

										return carry;
									}, {});
					
					if(!Object.keys(items).length)
					{
						productsSelections = 	Object.keys(productsSelections).reduce(function(carry, key)
																	{
																		if(key != i)
																			carry[key] = productsSelections[key];

																		return carry;
																	}, {});
					}
					else
					{
						productsSelections[i] = items;
					}
				}
			},
			onHide: function()
			{
				app.updateApp();
			}
		})
	};


	$(function()
	{
		$('.items-search').itemsDropdown();


		$(document).on('keyup', '.items-search input.search', debounce(function(e)
		{
			var _this = $(e.target);

			var val = _this.val().trim();

			if(!val.length)
				return;

			$.post('{{ route('products.api') }}', {'keywords': val}, null, 'json')
			.done(function(res)
			{
				var items = res.products.reduce(function(carry, item)
										{
											carry.push({'value': item.id, 'name': item.name});
											return carry;
										}, []);

				_this.closest('.items-search').dropdown('setup menu', {'values': items});

				if(items.length)
				{
					items = res.products.reduce(function(carry, item)
					{
						carry[item.id] = item;
						return carry;
					}, {});

					products = Object.assign(products, items);
				}
			})
			.fail(function()
			{
				alert('{{ __('Request failed') }}')
			})
		}, 200));


		$('#summernote').summernote({
	    placeholder: '...',
	    tabsize: 2,
	    height: 450,
	    tooltip: false
	  })


		$('.dropdown.tool input[type="hidden"]').on('change', function()
		{
			app.selectedTool = $(this).val();
		})


		$('#add-selection').on('click', function()
		{
			$('<div class="items-selection mt-1">\
					<input type="text" class="selection-title" name="selections[titles][]" placeholder="{{ __('Selection title') }}">\
					<div class="ui multiple selection search fluid dropdown mt-1-hf items-search">\
						<input type="hidden" name="selections[ids][]">\
						<div class="default text">{{ __('Search products') }}</div>\
						<i class="dropdown icon"></i>\
						<div class="menu"></div>\
					</div>\
				</div>').insertAfter('.items-selection:last');
			
			$('.items-search:last').itemsDropdown();
		})

		$('#remove-selection').on('click', function()
		{
			if($('.items-selection').length > 1)
			{
				$('.items-selection:last').remove()
			}
		})

	})
</script>

@endsection