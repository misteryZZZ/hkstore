@extends('back.master')

@section('title', $title)

@section('content')

<script type="application/javascript">
	'use strict';
	var parents_categories = {"0": {!! json_encode((object)$parents_posts) !!}, "1": {!! json_encode((object)$parents_products) !!}};
</script>

<form class="ui large form" id="category" method="post" action="{{ route('categories.store') }}">
	@csrf
	
	<div class="field">
		<button class="ui icon labeled pink large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('categories') }}">
			<i class="times icon"></i>
			{{ __('Cancel') }}
		</a>
	</div>

	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative bold circular-corner fluid message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	<div class="ui fluid divider"></div>

	<div class="field">
		<label>{{ __('Name') }}</label>
		<input type="text" name="name" required autofocus value="{{ old('name') }}">
	</div>
	
	<div class="field">
		<label>{{ __('For') }}</label>
		<div class="ui selection floating dropdown">
			<input type="hidden" name="for" value="{{ old('for') }}">
			<div class="text"></div>
			<div class="menu">
				@foreach(['posts', 'products'] as $key => $for)
				<a class="item" data-value="{{ $key }}">{{ __(ucfirst($for)) }}</a>
				@endforeach
			</div>
		</div>
	</div>

	<div class="field">
		<label>{{ __('Parent') }}</label>
		<div class="ui selection floating dropdown parent-category">
			<input type="hidden" name="parent" value="{{ old('parent', '0') }}">
			<div class="text"></div>
			<div class="menu"></div>
		</div>
	</div>

	<div class="field">
		<label>{{ __('Position') }}</label>
		<input type="number" name="range" value="{{ old('range', '0')}}">
	</div>

	<div class="field">
		<label>{{ __('Description') }}</label>
		<textarea name="description" cols="30" rows="5">{{ old('description') }}</textarea>
	</div>

</form>

<script>
	$(function()
	{
		'use strict';

		$('#category input[name="range"]').on('change', function()
		{
			if($(this).val() < 0)
				$(this).val('0');
		})

		$('input[name="for"]').on('change', function()
		{
			var parents = parents_categories[$(this).val()] || [];
			var options = '';

			$('.dropdown.parent-category').dropdown({values: parents});
		})

	  if($('input[name="for"]').val().length)
	  {
	  	var parentCategory = $('input[name="parent"]').val();

	  	$('input[name="for"]').change()

	  	if(parentCategory.length)
	  	{
	  		$('.dropdown.parent-category').dropdown('set selected', parentCategory)
	  	}
	  }
	})
</script>
@endsection