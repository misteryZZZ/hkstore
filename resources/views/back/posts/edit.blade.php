@extends('back.master')

@section('title', $title)

@section('additional_head_tags')

<link href="{{ asset_('assets/admin/summernote-lite-0.8.12.css') }}" rel="stylesheet">
<script src="{{ asset_('assets/admin/summernote-lite-0.8.12.js') }}"></script>

@endsection


@section('content')
<form class="ui large form" method="post" action="{{ route('posts.update', $post->id) }}" enctype="multipart/form-data">
	@csrf

	<div class="field">
		<button class="ui icon labeled purple large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Update') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('posts') }}">
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

	<div class="one column grid" id="post">
		<div class="column">
			<div class="field">
				<label>{{ __('Name') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name', $post->name) }}" autofocus required>
			</div>
			<div class="field">
				<label>{{ __('Cover') }}</label>
				<div class="ui placeholder rounded-corner" onclick="this.children[1].click()">
					<div class="ui image">
						<img src="{{ asset_("storage/posts/{$post->cover}?v=".time()) }}">
					</div>
					<input type="file" class="d-none" name="cover" accept=".jpg,.jpeg,.png,.gif,.svg">
				</div>
			</div>
			<div class="field">
				<label>{{ __('Category') }}</label>
				<div class="ui selection dropdown floating">
				  <input type="hidden" name="category" value="{{ old('category', $post->category) }}">
				  <i class="dropdown icon"></i>
				  <div class="default text">-</div>
				  <div class="menu">
				  	@foreach($categories as $category)
						<div class="item" data-value="{{ $category->id }}">
							{{ ucfirst($category->name) }}
						</div>
				  	@endforeach
				  </div>
				</div>
				<input class="mt-1" type="text" name="new_category" placeholder="{{ __('Add new category') }} ..." value="{{ old('new_category') }}">
			</div>
			<div class="field">
				<label>{{ __('Short description') }}</label>
				<textarea name="short_description" cols="30" rows="5">{{ old('short_description', $post->short_description) }}</textarea>
			</div>
			<div class="field">
				<label>{{ __('Content') }}</label>
				<textarea name="content" required class="summernote" cols="30" rows="20">{{ old('content', $post->content) }}</textarea>
			</div>
			<div class="field">
				<label>{{ __('Tags') }}</label>
				<input type="text" name="tags" value="{{ old('tags', $post->tags) }}">
			</div>
		</div>
	</div>
</form>

<script type="application/javascript">
	'use strict';
  $('.summernote').summernote({
    placeholder: '...',
    tabsize: 2,
    height: 300,
    tooltip: false
  });
</script>

@endsection