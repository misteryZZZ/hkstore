@extends('back.master')

@section('title', __('Create key, account, license ...'))

@section('content')
<form class="ui large form" method="post" action="{{ route('keys.store') }}" enctype="multipart/form-data">
	@csrf

	<div class="field">
		<button class="ui icon labeled pink large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('keys') }}">
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

	<div class="one column grid" id="key">
		<div class="column">
			<div class="field">
				<label>{{ __('Code') }}</label>
				<div class="ui left action input">
				  <button class="ui button left-circular-corner" type="button" onclick="this.nextElementSibling.click()">{{ __('Create keys in bluk.') }}</button>
				  <input class="d-none" type="file" name="codes" accept=".txt">
				  <div class="ui basic floating dropdown button mw-20">
				  	<input type="hidden" name="separator" value="{{ old('separator') }}">
				    <div class="text">{{ __('Separator') }}</div>
				    <i class="dropdown icon"></i>
				    <div class="menu">
				      <div class="item" data-value="\r\n|\r|\n">{{ __('Newline') }}</div>
				      <div class="item" data-value="\r\n\r\n|\r\r|\n\n">{{ __('Double newline') }}</div>
				      <div class="item" data-value=",">{{ __('Comma') }} <sup>( , )</sup></div>
				      <div class="item" data-value=";">{{ __('Semicolon') }} <sup>( ; )</sup></div>
				      <div class="item" data-value="|">{{ __('Pipe') }} <sup>( | )</sup></div>
				    </div>
				  </div>
				</div>

				<textarea class="mt-1" name="code" placeholder="..."  cols="30" rows="3" autofocus>{{ old('code') }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Product') }}</label>
				<div class="ui fluid floating search selection dropdown">
					<input type="hidden" name="product_id" value="{{ old('product_id') }}" required>
					<div class="default text"></div>
					<div class="menu">
						@foreach($products as $product)
						<a class="item capitalize" data-value="{{ $product->id }}">{!! $product->name !!}</a>
						@endforeach
					</div>
				</div>
			</div>
		</div>
	</div>
</form>

@endsection