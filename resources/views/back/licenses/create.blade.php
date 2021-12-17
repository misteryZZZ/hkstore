@extends('back.master')

@section('title', __('Create product license'))

@section('content')
<form class="ui large form" method="post" action="{{ route('licenses.store') }}">
	@csrf

	<div class="field">
		<button class="ui icon labeled pink large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
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
	        <input type="checkbox" name="regular" {{ old('regular') ? 'checked' : '' }} tabindex="0" class="hidden">
	        <label>{{ __('Mark as regular license') }}</label>
	      </div>
	    </div>
	    
			<div class="field">
				<label>{{ __('Name') }}</label>
				<input type="text" name="name" placeholder="..." value="{{ old('name') }}" autofocus required>
			</div>

			<div class="field">
				<label>{{ __('Item type') }}</label>
				<div class="ui selection floating search dropdown">
				  <input type="hidden" name="item_type" value="{{ old('item_type') }}">
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