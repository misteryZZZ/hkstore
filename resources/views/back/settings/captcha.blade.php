@extends('back.master')

@section('title', __('Captcha settings'))


@section('content')

<form class="ui large main form" method="post" spellcheck="false" action="{{ route('settings.update', 'captcha') }}">

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
				<label>{{ __('Enable captcha on') }}</label>
				<div class="ui selection multiple floating dropdown search">
					<input type="hidden" name="captcha[enable_on]" value="{{ old('captcha.enable_on', $settings->enable_on ?? null) }}">
					<div class="text"></div>
					<i class="dropdown icon"></i>
					<div class="menu">
						<a class="item" data-value="register">{{ __('Registration form') }}</a>
						<a class="item" data-value="login">{{ __('Login form') }}</a>
						<a class="item" data-value="contact">{{ __('Contact form') }}</a>
					</div>
				</div>
			</div>

			<div class="ui two doubling stackable cards mt-2">
				<div class="ui fluid card">
					<div class="content">
						<h3 class="header">
							<a href="https://www.google.com/recaptcha" target="_blank">{{ __('Google Recaptcha') }}</a>
							<div class="checkbox-wrapper">
								<div class="ui fitted toggle checkbox">
							    <input 
							    	type="checkbox" 
							    	name="captcha[google][enabled]"
							    	@if(!empty(old('captcha.google.enabled')))
										{{ old('captcha.google.enabled') ? 'checked' : '' }}
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
							<label>{{ __('Captcha secret') }}</label>
							<input type="text" name="captcha[google][secret]" value="{{ old('captcha.google.secret', $settings->google->secret ?? null) }}">
						</div>

						<div class="field">
							<label>{{ __('Captcha sitekey') }}</label>
							<input type="text" name="captcha[google][sitekey]" value="{{ old('captcha.google.sitekey', $settings->google->sitekey ?? null) }}">
						</div>

						<div class="field">
							<label>{{ __('Theme') }}</label>
							<div class="ui selection floating dropdown">
								<input type="hidden" name="captcha[google][attributes][data-theme]" value="{{ old('captcha.google.attributes.data-theme', $settings->google->attributes->{'data-theme'} ?? 'light') }}">
								<div class="text"></div>
								<div class="menu">
									<a class="item" data-value="light">{{ __('Light') }}</a>
									<a class="item" data-value="dark">{{ __('Dark') }}</a>
								</div>
							</div>
						</div>

						<div class="field">
							<label>{{ __('Theme') }}</label>
							<div class="ui selection floating dropdown">
								<input type="hidden" name="captcha[google][attributes][data-size]" value="{{ old('captcha.google.attributes.data-size', $settings->google->attributes->{'data-size'} ?? 'compact') }}">
								<div class="text"></div>
								<div class="menu">
									<a class="item" data-value="compact">{{ __('Compact') }}</a>
									<a class="item" data-value="normal">{{ __('Normal') }}</a>
								</div>
							</div>
						</div>

						<input type="hidden" name="captcha[google][options][timeout]" value="{{ old('captcha.google.options.timeout', $settings->google->options->timeout ?? 30) }}">
					</div>
				</div>

				<div class="ui fluid card">
					<div class="content">
						<h3 class="header">
							<a href="https://github.com/mewebstudio/captcha" target="_blank">{{ __('Mewebstudio captcha') }}</a>
							<div class="checkbox-wrapper">
								<div class="ui fitted toggle checkbox">
							    <input 
							    	type="checkbox" 
							    	name="captcha[mewebstudio][enabled]"
							    	@if(!empty(old('captcha.mewebstudio.enabled')))
										{{ old('captcha.mewebstudio.enabled') ? 'checked' : '' }}
										@else
										{{ ($settings->mewebstudio->enabled ?? null) ? 'checked' : '' }}
							    	@endif
							    >
							    <label></label>
							  </div>
							</div>
						</h3>
					</div>

					<div class="content">				
						<div class="field">
							<label>{{ __('Length') }}</label>
							<input type="text" name="captcha[mewebstudio][length]" value="{{ old('captcha.mewebstudio.length', $settings->mewebstudio->length ?? '5') }}">
						</div>

						<div class="field">
							<label>{{ __('Enable math') }}</label>
							<div class="ui selection floating dropdown">
								<input type="hidden" name="captcha[mewebstudio][math]" value="{{ old('captcha.mewebstudio.math', $settings->mewebstudio->math ?? 'true') }}">
								<div class="text"></div>
								<div class="menu">
									<a class="item" data-value="true">{{ __('Yes') }}</a>
									<a class="item" data-value="false">{{ __('No') }}</a>
								</div>
							</div>
						</div>

						<div class="field">
							<label>{{ __('Width') }}</label>
							<input type="text" name="captcha[mewebstudio][width]" value="{{ old('captcha.mewebstudio.width', $settings->mewebstudio->width ?? '120') }}">
						</div>

						<div class="field">
							<label>{{ __('Height') }}</label>
							<input type="text" name="captcha[mewebstudio][height]" value="{{ old('captcha.mewebstudio.height', $settings->mewebstudio->height ?? '36') }}">
						</div>

						<div class="field">
							<label>{{ __('Quality') }}</label>
							<input type="text" name="captcha[mewebstudio][quality]" value="{{ old('captcha.mewebstudio.quality', $settings->mewebstudio->quality ?? '90') }}">
						</div>
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