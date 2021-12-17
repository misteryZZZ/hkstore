@extends('back.master')

@section('title', __('Advertisement'))


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'adverts') }}">

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
				<label>{{ __('Responsive ad') }}</label>
				<textarea name="responsive_ad" cols="30" rows="3">{{ old('responsive_ad', $settings->responsive_ad ?? null) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Auto ad') }}</label>
				<textarea name="auto_ad" cols="30" rows="3">{{ old('auto_ad', $settings->auto_ad ?? null) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Ad 728x90') }}</label>
				<textarea name="ad_728x90" cols="30" rows="3">{{ old('ad_728x90', $settings->ad_728x90 ?? null) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Ad 468x60') }}</label>
				<textarea name="ad_468x60" cols="30" rows="3">{{ old('ad_468x60', $settings->ad_468x60 ?? null) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Ad 300x250') }}</label>
				<textarea name="ad_300x250" cols="30" rows="3">{{ old('ad_300x250', $settings->ad_300x250 ?? null) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Ad 320x100') }}</label>
				<textarea name="ad_320x100" cols="30" rows="3">{{ old('ad_320x100', $settings->ad_320x100 ?? null) }}</textarea>
			</div>

			<div class="field">
				<label>{{ __('Popup Ad') }}</label>
				<textarea name="popup_ad" cols="30" rows="3">{{ old('popup_ad', $settings->popup_ad ?? null) }}</textarea>
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