@extends('back.master')

@section('title', __(':item_type - :name', ['item_type' => $license->item_type, 'name' => $license->name]))

@section('content')
<form class="ui large form" method="post" action="{{ route('licenses.update', ['id' => $license]) }}">
	@csrf

	<div class="field">
		<button class="ui icon labeled pink large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Save') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('licenses') }}">
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

	<div class="one column grid" id="license">
		<div class="column">
			<div class="field">
	      <div class="ui toggle checkbox">
	        <input type="checkbox" name="regular" {{ $license->regular ? 'checked' : '' }} tabindex="0" class="hidden">
	        <label>{{ __('Mark as regular license') }}</label>
	      </div>
	    </div>

			<div class="field">
				<label>{{ __('Name') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name', $license->name) }}" autofocus required>
			</div>

			<div class="field">
				<label>{{ __('Item type') }}</label>
				<div class="ui selection floating search dropdown">
				  <input type="hidden" name="item_type" value="{{ old('item_type', $license->item_type) }}">
				  <div class="default text">-</div>
				  <div class="menu">
				  	@foreach(config('app.item_types') ?? [] as $k => $v)
						<div class="item" data-value="{{ $k }}">{{ __($v) }}</div>
						@endforeach
				  </div>
				</div>
			</div>
		</div>
	</div>
</form>

<script>
	$(function()
	{
		$('.ui.toggle.checkbox').checkbox()
	})
</script>

@endsection