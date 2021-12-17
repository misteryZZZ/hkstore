@extends('back.master')

@section('title', $title)

@section('content')

<form class="ui large form" id="subscription" method="post" action="{{ route('subscriptions.update', [$subscription->id]) }}">
	@csrf
	
	<div class="field">
		<button class="ui icon labeled purple large circular button" type="submit">
		  <i class="save outline icon"></i>
		  {{ __('Update') }}
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
		<input type="text" name="name" required autofocus value="{{ old('name', $subscription->name) }}">
		<small>{{ __('e.g. : Plan 1, Basic, Ultimate, ... etc') }}.</small>
	</div>

	<div class="field">
		<label>{{ __('Duration title') }}</label>
		<input type="text" name="title" value="{{ old('title', $subscription->title) }}">
		<small>{{ __('e.g. : Month, Day, 45 Days, Year, ... etc') }}</small>
	</div>

	<div class="field">
		<label>{{ __('Price') }}</label>

		<div class="ui right action input">
		  <input type="number" step="0.01" name="price" value="{{ old('price', $subscription->price) }}">
			<div class="ui teal icon button" style="background: {{ $subscription->color ?? '#00B5AD' }}" onclick="this.nextElementSibling.click()">{{ __('Color') }}</div>
		  <input type="color" class="d-none" name="color" value="{{ old('color', $subscription->color) }}">
		</div>
	</div>

	<div class="field">
		<label>{{ __('Days') }}</label>
		<input type="number" name="days" value="{{ old('days', $subscription->days) }}">
		<small>{{ __('Number of days for the subscription') }}.</small>
		<div class="ui hidden my-0"></div>
	</div>

	<div class="field">
		<label>{{ __('Limit total downloads') }}</label>
		<input type="number" name="limit_downloads" value="{{ old('limit_downloads', $subscription->limit_downloads) }}">
		<small>{{ __('Limit of downloads per X days') }}.</small>
	</div>

	<div class="field">
		<label>{{ __('Limit downloads per day') }}</label>
		<input type="number" name="limit_downloads_per_day" value="{{ old('limit_downloads_per_day', $subscription->limit_downloads_per_day) }}">
	</div>

	<div class="field">
		<label>{{ __('Limit downloads of the same item during the subscription') }}</label>
    <input type="number" name="limit_downloads_same_item" value="{{ old('limit_downloads_same_item', $subscription->limit_downloads_same_item ?? null) }}">
	</div>

	<div class="field">
		<label>{{ __('Description') }} <i class="exclamation blue inverted circle icon" title="{{ __('You can use HTML') }}"></i></label>
		<textarea name="description" cols="30" rows="5" placeholder="{{ __('Line') }} 1&#10;{{ __('Line') }} 2&#10;{{ __('Line') }} 3">{!! old('description', $subscription->description) !!}</textarea>
		<small>{{ __('HTML code is allowed') }}.</small>
	</div>

	<div class="field">
		<label>{{ __('Products') }}</label>
		<div class="ui search multiple floating selection dropdown">
			<input type="hidden" name="products" value="{{ old('products', $subscription->products) }}">
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
		<input type="number" name="position" value="{{ old('position', $subscription->position) }}">
		<small>{{ __('Whether to show first, 2nd ... last.') }}.</small>
	</div>

</form>

@endsection