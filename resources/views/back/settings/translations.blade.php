@extends('back.master')

@section('title', __('Translation settings'))


@section('content')

<form class="ui large main form" method="post" spellcheck="true" action="{{ route('settings.update', 'translations') }}" enctype="multipart/form-data">

	<div class="field">
		<button type="submit" class="ui pink large circular labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Save') }}
		</button>
	</div>

	@if($errors->any())
		@foreach ($errors->all() as $error)
		<div class="ui negative fluid small message">
			<i class="times icon close"></i>
			{{ $error }}
		</div>
		@endforeach
	@endif

	@if(session('settings_message'))
	<div class="ui positive fluid message">
		<i class="times icon close"></i>
		{{ session('settings_message') }}
	</div>
	@endif

	<div class="ui fluid divider"></div>

	<div class="one column grid translation" id="settings">
		<div class="column">
			<div class="field">
				<div  class="ui selection search floating dropdown">
					<input type="hidden" name="__lang__" value="{{ old('__lang__') }}">
					<div class="default text">{{ __('Select language file') }}</div>
					<i class="dropdown icon"></i>
					<div class="menu">
						@foreach($langs as $lang)
						<div class="item" data-value="{{ $lang }}">{{ mb_ucfirst($lang) }}</div>
						@endforeach
					</div>
				</div>
			</div>

			<small>{{ __('* Red parameters must be kept unchanged.') }}</small>

			<div class="table wrapper">
				<table class="ui basic fluid unstackable table">
					<thead>
						<tr>
							<th>{{ __('Base') }}</th>
							<th>
								{{ __('Translation') }} 
								<sup>
									<div class="ui read-only checkbox">
									  <input type="checkbox" name="rtl" {{ old('rtl') ? 'checked' : '' }}>
									  <label>{{ __('RTL') }} </label>
									</div>
								</sup>
							</th>
						</tr>
					</thead>
					<tbody>
						@foreach($base as $key => $value)
						<tr>
							<td class="eight columns wide">{!! $key !!}</td>
							<td><input type="text" name="translation[{{ $value }}]"></td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			<div class="ui divider mt-2"></div>

			<div class="field mt-2">
				<button class="ui yellow large button circular" id="add-line" type="button">{{ __('Add new line') }}</button>
			</div>

			<div class="new-lines mt-1">
				<div class="wrapper table mt-0">
					<table class="ui basic fluid unstackable table">
						<tbody>
							<tr>
								<td><input type="text" name="new[key][]" placeholder="{{ __('Key') }}"></td>
								<td><input type="text" name="new[value][]" placeholder="{{ __('Translation') }}"></td>
								<td class="right aligned"><button class="ui red circular icon delete button mx-0" type="button"><i class="close icon"></i></button></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</form>

<script type="application/javascript">
	'use strict';

	$(document).on('change', 'input[name="__lang__"]', function()
	{
		$.post('{{ route('get_translation') }}', {lang: $(this).val()})
		.done(function(response)
		{
			for(var k in response.base)
			{
				$('input[name="translation['+ k +']"]').val(response.lang[k] || '').toggleClass('empty', false);

				if(!(response.lang[k] || '').length)
				{
					$('input[name="translation['+ k +']"]').toggleClass('empty', true)
				}
			}
		})
	})

	$('input[name="rtl"]').on('change', function()
	{
		$('.translation tbody input').attr('dir', $(this).prop('checked') ? 'rtl' : 'ltr')
	})

	@if(old('__lang__'))
	$('input[name="__lang__"]').change()
	@endif

	@if(old('rtl') === 'on')
	$('.translation tbody input').attr('dir', 'rtl')
	@endif

	$('#add-line').on('click', function()
	{
		$('.new-lines tbody').append('<tr> \
			<td><input type="text" name="new[key][]" placeholder="{{ __('Key') }}"></td> \
			<td><input type="text" name="new[value][]" placeholder="{{ __('Translation') }}"></td> \
			<td class="right aligned"><button class="ui red circular icon delete button mx-0" type="button"><i class="close icon"></i></button></td> \
		</tr>');
	})

	$(document).on('click', '.delete.button', function()
	{
		if($('.new-lines tbody tr').length > 1)
		{
			$(this).closest('tr').remove()
		}
	})

	$('#settings input, #settings textarea').on('keydown', function(e) 
	{
	    if((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
	    {		        
	        $('form.main').submit();

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