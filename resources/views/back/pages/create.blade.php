@extends('back.master')

@section('title', $title)

@section('additional_head_tags')

<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>

@endsection


@section('content')
<form class="ui large form" method="post" action="{{ route('pages.store') }}">
	@csrf

	<div class="field">
		<button class="ui icon labeled pink large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('pages') }}">
			<i class="times icon"></i>
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

	<div class="ui fluid divider"></div>

	<div class="one column grid" id="page">
		<div class="column">
			<div class="field">
				<label>{{ __('Name') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name') }}" autofocus required>
			</div>
			<div class="field">
				<label>{{ __('Short description') }}</label>
				<textarea name="overview" cols="30" rows="5">{{ old('short_description') }}</textarea>
			</div>
			<div class="field">
				<label>{{ __('Content') }}</label>
				<textarea name="content" required class="summernote" cols="30" rows="20">{{ old('content') }}</textarea>
			</div>
			<div class="field">
				<label>{{ __('Tags') }}</label>
				<input type="text" name="tags" value="{{ old('tags') }}">
			</div>
		</div>
	</div>
</form>

<script>
	'use strict';
	
  $('.summernote').summernote({
    placeholder: '...',
    tabsize: 2,
    height: 450,
    tooltip: false
  });
</script>

@endsection