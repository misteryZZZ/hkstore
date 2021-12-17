@extends('back.master')

@section('title', __('Bulk upload products'))

@section('content')

<form class="ui large main form" method="post" enctype="multipart/form-data" spellcheck="false" action="{{ route('settings.update', 'bulk_upload') }}">

	<div class="field">
		<button type="submit" class="ui pink large circular labeled icon button mx-0">
		  <i class="save icon mx-0"></i>
		  {{ __('Submit') }}
		</button>

		<a href="{{ route('admin') }}" class="ui yellow circular large right labeled icon button mx-0">
		  <i class="times icon mx-0"></i>
		  {{ __('Cancel') }}
		</a>
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

	<div class="one column grid" id="settings">
		<div class="column">

			<div class="field">
				<button class="ui blue large rounded button" onclick="this.nextElementSibling.click()" type="button">{{ __('CSV data file') }}</button>
				<input type="file" name="data_file" class="d-none" accept=".csv">

				<button class="ui blue large rounded button" onclick="this.nextElementSibling.click()" type="button">{{ __('Covers') }}</button>
				<input type="file" name="covers[]" multiple required class="d-none" accept=".jpg,.jpeg,.png,.svg">

				<button class="ui blue large rounded button" onclick="this.nextElementSibling.click()" type="button" title="{{ __('Main downloadable files') }}">{{ __('Main files') }}</button>
				<input type="file" name="main_files[]" multiple class="d-none" accept=".rar,.zip">
			</div>

			<div class="field">
				<div><small><strong>{{ __('CSV data file') }}</strong> : {{ __('This file contains all details about your products in csv form, including the columns names (id, name, description, ... etc.') }}</small></div> 

				<div class="mt-1"><small><strong>{{ __('Covers') }}</strong> : {{ __("This input is required, it contains the products covers.") }}</small></div>

				<div class="mt-1"><small><strong>{{ __('Main files') }}</strong> : {{ __('This input is optional, it contains the main files (zip or any archive) for the uploaded items.') }}</small></div>

				<div class="mt-1"><small><strong>{{ __('Important') }}</strong> : {{ __('Make sure that the files names for the main files and the covers are the same as in the csv data file.') }}</small></div> 
			</div> 		

			<div class="table wrapper">
				<table class="ui celled large table unstackable">
					<tbody>
						@foreach($settings->columns as $column)
							<tr>
								<td class="four wide column">
									<input type="hidden" name="columns[original][]" value="{{ $column }}">
									{{ __(mb_ucfirst(str_replace('_', ' ', $column))) }}
								</td>

								<td>
									<div class="ui selection dropdown floating scrolling rounded-corner fluid columns">
										<input type="hidden" name="columns[imported][]">
										<div class="text">{{ __('Imported column/field name') }}</div>
										<i class="dropdown icon"></i>
										<div class="menu"></div>
									</div>
								</td>
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

		</div>
	</div>
</form>


<script>
	'use strict';

	$(function()
	{
		$('input[type="file"][name="data_file"]').on('change', function()
		{
			var mainFile = $(this)[0].files[0];   
	    window.formData = new FormData();

	    formData.append('data_file', mainFile);
	    formData.append('async', true);

	    $.ajax({
	        url: '/admin/settings/bulk_upload/update', 
	        dataType: 'json',
	        cache: false,
	        contentType: false,
	        processData: false,
	        data: formData,                         
	        type: 'POST'
	     })
	    .done(function(columns)
      {
      	$('.ui.dropdown.columns').dropdown({
      		values: columns
      	})
      	.dropdown('set text', '{{ __('Imported column/field name') }}');
      });
		})

		$('.ui.dropdown.columns').on('click', function()
		{
			if(!$('.menu .item', this).length)
			{
				alert('{{ __('Please select a CSV data file first.') }}');
			}
		})
	})
</script>
@endsection