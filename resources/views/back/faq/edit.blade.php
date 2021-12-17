@extends('back.master')

@section('title', $title)

@section('additional_head_tags')
<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>
@endsection

@section('content')
<form class="ui large form" method="post" action="{{ route('faq.update', $faq->id) }}">
	@csrf

	<div class="field">
		<button type="submit" class="ui purple circular large labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Update') }}
		</button>
		<a href="{{ route('faq') }}" class="ui yellow circular large right labeled icon button mx-0">
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

	<div class="ui fluid divider"></div>

	<div class="one column grid">
		<div class="column">

			<div class="field">
				<label>{{ __('Question') }}</label>
				<input type="text" name="question" placeholder="..." value="{{ old('question', $faq->question) }}" required>
			</div>

			<div class="field">
				<label>{{ __('Answer') }}</label>
				<textarea name="answer" required class="summernote" cols="30" rows="20">{{ old('answer', $faq->answer) }}</textarea>
			</div>
			
		</div>
	</div>
</form>

<script>
	$(function()
	{
		'use strict';
		
		$('.summernote').summernote({
	    placeholder: '...',
	    tabsize: 2,
	    height: 300,
	    tooltip: false
	  })
		
	})
</script>

@endsection