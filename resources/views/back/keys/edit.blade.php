@extends('back.master')

@section('title', __('Edit :code', ['code' => $key->id]))

@section('content')
<form class="ui large form" method="post" action="{{ route('keys.update', ['id' => $key->id]) }}">
	@csrf

	<div class="field">
		<button class="ui icon labeled pink large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Save') }}
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
				<textarea name="code" placeholder="..."  cols="30" rows="3" autofocus>{{ old('code', $key->code) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Product') }}</label>
				<div class="ui fluid floating search selection dropdown">
					<input type="hidden" name="product_id" value="{{ old('product_id', $key->product_id) }}" required>
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