@extends('back.master')

@section('title', $title)

@section('content')

<form class="ui large form" id="subscription" method="post" action="{{ route('subscriptions.store') }}">
	@csrf
	
	<div class="field">
		<button class="ui icon labeled purple large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Create') }}
		</button>
		<a class="ui icon labeled yellow large circular button" href="{{ route('subscriptions') }}">
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

	<div class="field">
		<label>{{ __('Name') }}</label>
		<input type="text" name="name" required autofocus value="{{ old('name') }}">
		<small>{{ __('e.g. : Plan 1, Basic, Ultimate, ... etc') }}.</small>
	</div>

	<div class="field">
		<label>{{ __('Duration title') }}</label>
		<input type="text" name="title" value="{{ old('title') }}">
		<small>{{ __('e.g. : Month, Day, 45 Days, Year, ... etc') }}</small>
	</div>

	<div class="field">
		<label>{{ __('Price') }}</label>

		<div class="ui right action input">
		  <input type="number" step="0.01" name="price" value="{{ old('price') }}">
			<div class="ui teal icon button" onclick="this.nextElementSibling.click()">{{ __('Color') }}</div>
		  <input type="color" class="d-none" name="color" value="{{ old('color') }}">
		</div>
	</div>
	
	<div class="field">
		<label>{{ __('Days') }} </label>
		<input type="number" name="days" value="{{ old('days') }}">
		<small>{{ __('Number of days for the subscription') }}.</small>
		<div class="ui hidden my-0"></div>
	</div>

	<div class="field">
		<label>{{ __('Limit total downloads') }}</label>
		<input type="number" name="limit_downloads" value="{{ old('limit_downloads') }}">
		<small>{{ __('Limit of downloads per X days') }}.</small>
	</div>

	<div class="field">
		<label>{{ __('Limit downloads per day') }}</label>
		<input type="number" name="limit_downloads_per_day" value="{{ old('limit_downloads_per_day') }}">
	</div>

	<div class="field">
		<label>{{ __('Limit downloads of the same item during the subscription') }}</label>
    <input type="number" name="limit_downloads_same_item" value="{{ old('limit_downloads_same_item') }}">
	</div>

	<div class="field">
		<label>{{ __('Description') }}</label>
		<textarea name="description" cols="30" rows="5" placeholder="{{ __('Line') }} 1&#10;{{ __('Line') }} 2&#10;{{ __('Line') }} 3">{{ old('description') }}</textarea>
		<small>{{ __('HTML code allowed') }}.</small>
	</div>

	<div class="field">
		<label>{{ __('Products') }}</label>
		<div class="ui search multiple floating selection dropdown">
			<input type="hidden" name="products" value="{{ old('products') }}">
			<div class="text">...</div>
			<div class="menu">
				@foreach(\App\Models\Product::where('active', 1)->get() as $product)
				<a class="item capitalize" data-value="{{ $product->id }}">{{ $product->name }}</a>
				@endforeach
			</div>
		</div>
		<small>{{ __('Products applicable for this plan. (Default: all)') }}</small>
	</div>

	<div class="field">
		<label>{{ __('Position') }}</label>
		<input type="number" name="position" value="{{ old('position') }}">
		<small>{{ __('Whether to show first, 2nd ... last.') }}.</small>
	</div>

</form>

@endsection