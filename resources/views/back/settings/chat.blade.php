@extends('back.master')

@section('title', __('Chat settings'))


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'chat') }}">

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
			<div class="ui fluid card">
				<div class="content">
					<h3 class="header">
						<a href="https://www.twak.to/" target="_blank"><img src="{{ asset_('assets/images/tawk-sitelogo.png') }}" alt="Twak.to" class="ui small avatar mr-1">Twak.to</a>
						<input type="hidden" name="twak[name]" value="twak">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="twak[enabled]"
						    	@if(!empty(old('twak.enabled')))
									{{ old('twak.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->twak->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">				
					<div class="field">
						<label>{{ __('Property ID') }}</label>
						<input type="text" name="twak[property_id]" value="{{ old('twak.property_id', $settings->twak->property_id ?? null) }}" placeholder="E.g. 64ca1d876503fa8a2a8e39a1s">
					</div>
				</div>
			</div>

			<div class="ui fluid card">
				<div class="content">
					<h3 class="header">
						<a href="https://www.twak.to/" target="_blank"><img src="{{ asset_('assets/images/gist.png') }}" alt="Gist" class="ui small avatar mr-1">Getgist.com</a>
						<input type="hidden" name="gist[name]" value="gist">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="gist[enabled]"
						    	@if(!empty(old('gist.enabled')))
									{{ old('gist.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->gist->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">				
					<div class="field">
						<label>{{ __('Workspace ID') }}</label>
						<input type="text" name="gist[workspace_id]" value="{{ old('gist.workspace_id', $settings->gist->workspace_id ?? null) }}" placeholder="E.g. ejt925x5">
					</div>
				</div>
			</div>

			<div class="ui fluid card">
				<div class="content">
					<h3 class="header">
						<a>Other service</a>
						<input type="hidden" name="other[name]" value="other">

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="other[enabled]"
						    	@if(!empty(old('other.enabled')))
									{{ old('other.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->other->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">				
					<div class="field">
						<textarea name="other[code]" placeholder="{{ __('Code') }}" cols="30" rows="5">{{ old('other.code', $settings->other->code ?? null) }}</textarea>
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