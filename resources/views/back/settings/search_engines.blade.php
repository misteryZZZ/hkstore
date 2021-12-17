@extends('back.master')

@section('title', __('Search engines settings'))


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'search_engines') }}">

	<div class="field">
		<button type="submit" class="ui pink large circular labeled icon button mx-0">
		  <i class="save outline icon mx-0"></i>
		  {{ __('Update') }}
		</button>
	</div>

	@if($errors->any())
      @foreach ($errors->all() as $error)
         <div class="ui negative fluid small message">
         	<i class="times icon close"></i>
         	{{ $error }}
         </div>
      @endforeach
	@endif

	@if(session('settings_message'))
	<div class="ui positive fluid message">
		<i class="times icon close"></i>
		{{ session('settings_message') }}
	</div>
	@endif
	
	<div class="ui fluid divider"></div>

	<div class="one column grid" id="settings">
		<div class="column">

			<div class="field">
				<label>{{ __('Site verification') }}</label>

				<input type="text" name="google" placeholder="{{ __('Google code') }}..." value="{{ old('google', $settings->google ?? null) }}">

				<input class="mt-1" type="text" name="bing" placeholder="{{ __('Bing code') }}..." value="{{ old('bing', $settings->bing ?? null) }}">

				<input class="mt-1" type="text" name="yandex" placeholder="{{ __('Yandex code') }}..." value="{{ old('yandex', $settings->yandex ?? null) }}">
			</div>

			<div class="field">
				<label>{{ __('Google analytics') }}</label>
				<textarea name="google_analytics" cols="30" rows="5" placeholder="...">{{ old('google_analytics', $settings->google_analytics ?? null) }}</textarea>
			</div>
		
			<div class="field">
				<label>{{ __('Robots') }}</label>
				<div class="ui dropdown floating selection">
					<input type="hidden" name="robots" value="{{ old('robots', $settings->robots ?? 'follow, index') }}">
					<div class="default text">...</div>
					<div class="menu">
						<div class="item" data-value="follow, index">{{ __('Follow and Index') }}</div>
						<div class="item" data-value="follow, noindex">{{ __('Follow but do not Index') }}</div>
						<div class="item" data-value="nofollow, index">{{ __('Do not Follow but Index') }}</div>
						<div class="item" data-value="nofollow, noindex">{{ __('Do not Follow and do not Index') }}</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</form>

<script>
	'use strict';

	$(function()
	{
		$('#settings input, #settings textarea').on('keydown', function(e) 
		{
		    if((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
		    {		        
		        $('form.main').submit();

		  			e.preventDefault();

		        return false;
		    }
		    else
		    {
		        return true;
		    }
		})
	})
</script>

@endsection