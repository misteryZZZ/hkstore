@extends('back.master')

@section('title', __('Social login settings'))


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'social_login') }}">

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
	
	<div class="ui fluid divider mb-0"></div>
	
	<div class="one column grid" id="settings">

		<div class="ui three stackable cards mt-1">
			<div class="ui card mt-0">
				<div class="content">
					<h3 class="header">
						<i class="circular google icon mr-1"></i>Google

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="google[enabled]"
						    	@if(!empty(old('google.enabled')))
									{{ old('google.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->google->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="google[client_id]" placeholder="..." value="{{ old('google.client_id', $settings->google->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="google[secret_id]" placeholder="..." value="{{ old('google.secret_id', $settings->google->secret_id ?? null) }}">
					</div>
				</div>
			</div>

			<div class="ui card mt-0">
				<div class="content">
					<h3 class="header">
						<i class="circular twitter icon mr-1"></i>Twitter

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="twitter[enabled]"
						    	@if(!empty(old('twitter.enabled')))
									{{ old('twitter.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->twitter->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="twitter[client_id]" placeholder="..." value="{{ old('twitter.client_id', $settings->twitter->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="twitter[secret_id]" placeholder="..." value="{{ old('twitter.secret_id', $settings->twitter->secret_id ?? null) }}">
					</div>
				</div>
			</div>

			<div class="ui card mt-0">
				<div class="content">
					<h3 class="header">
						<i class="circular facebook icon mr-1"></i>Facebook

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="facebook[enabled]"
						    	@if(!empty(old('facebook.enabled')))
									{{ old('facebook.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->facebook->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="facebook[client_id]" placeholder="..." value="{{ old('facebook.client_id', $settings->facebook->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="facebook[secret_id]" placeholder="..." value="{{ old('facebook.secret_id', $settings->facebook->secret_id ?? null) }}">
					</div>
				</div>
			</div>

			<div class="ui card mt-0">
				<div class="content">
					<h3 class="header">
						<i class="circular github icon mr-1"></i>Github

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="github[enabled]"
						    	@if(!empty(old('github.enabled')))
									{{ old('github.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->github->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="github[client_id]" placeholder="..." value="{{ old('github.client_id', $settings->github->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="github[secret_id]" placeholder="..." value="{{ old('github.secret_id', $settings->github->secret_id ?? null) }}">
					</div>
				</div>
			</div>

			<div class="ui card mt-0">
				<div class="content">
					<h3 class="header">
						<i class="circular linkedin icon mr-1"></i>Linkedin

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="linkedin[enabled]"
						    	@if(!empty(old('linkedin.enabled')))
									{{ old('linkedin.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->linkedin->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('Client ID') }}</label>
						<input type="text" name="linkedin[client_id]" placeholder="..." value="{{ old('linkedin.client_id', $settings->linkedin->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="linkedin[secret_id]" placeholder="..." value="{{ old('linkedin.secret_id', $settings->linkedin->secret_id ?? null) }}">
					</div>
				</div>
			</div>

			<div class="ui card mt-0">
				<div class="content">
					<h3 class="header">
						<i class="circular vk icon mr-1"></i>VKontakte (VK)

						<div class="checkbox-wrapper">
							<div class="ui fitted toggle checkbox">
						    <input 
						    	type="checkbox" 
						    	name="vkontakte[enabled]"
						    	@if(!empty(old('vkontakte.enabled')))
									{{ old('vkontakte.enabled') ? 'checked' : '' }}
									@else
									{{ ($settings->vkontakte->enabled ?? null) ? 'checked' : '' }}
						    	@endif
						    >
						    <label></label>
						  </div>
						</div>
					</h3>
				</div>

				<div class="content">
					<div class="field">
						<label>{{ __('App Key') }}</label>
						<input type="text" name="vkontakte[client_id]" placeholder="..." value="{{ old('vkontakte.client_id', $settings->vkontakte->client_id ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Secret ID') }}</label>
						<input type="text" name="vkontakte[secret_id]" placeholder="..." value="{{ old('vkontakte.secret_id', $settings->vkontakte->secret_id ?? null) }}">
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