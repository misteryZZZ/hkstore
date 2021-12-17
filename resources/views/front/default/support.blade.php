@extends('front.default.master')

@section('additional_head_tags')
<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "WebPage",
	"image": "{{ $meta_data->image }}",
	"name": "{{ $meta_data->title }}",
  "url": "{{ $meta_data->url }}",
  "description": "Frequently asked questions and support."
}
</script>

@if(captcha_is_enabled('contact') && captcha_is('google'))
{!! google_captcha_js() !!}
@endif
@endsection

@section('body')

	<div class="ui two stackable columns shadowless celled grid my-0" id="support">
		<div class="column left faq">
			<div class="title-wrapper">
				<div class="ui shadowless fluid segment">
					<h3>{{ __('Frequently asked questions') }}</h3>
				</div>
			</div>

			<div class="ui shadowless borderless segments">
				@foreach($faqs as $faq)
			  <div class="ui borderless segment">
			    <p><i class="minus icon"></i>{{ $faq->question }}</p>
			    <div>
			    	{!! $faq->answer !!}
			    </div>
			  </div>
			  @endforeach
			</div>
		</div>
	
		<div class="column right support">
			<div class="title-wrapper">
				<div class="ui shadowless fluid segment">
					<h3>{{ __('Still have a question') }} ?</h3>
				</div>
			</div>
			
			@if($errors->any())
		    @foreach ($errors->all() as $error)
				<div class="ui negative fluid small message">
					<i class="times icon close"></i>
					{{ $error }}
				</div>
		    @endforeach
			@endif

			@if(session('support_response'))
			<div class="ui fluid small bold positive message">
				{{ session('support_response') }}
			</div>
			@endif

			<form action="{{ route('home.support') }}" method="post" class="ui large form">
				@csrf

				<div class="field">
					<label>Email</label>
					<input type="email" value="{{ old('email', request()->user()->email ?? '') }}" name="email" placeholder="Your email..." required>
				</div>

				<div class="field">
					<label>{{ __('Subjet') }}</label>
					<input type="text" name="subject" value="{{ old('subject') }}" placeholder="{{ __('Subjet') }}..." required>
				</div>

				<div class="field">
					<label>{{ __('Question') }}</label>
					<textarea name="message" cols="30" rows="10" placeholder="{{ __('Your questions') }}..." required>{{ old('message') }}</textarea>
				</div>

				@error('captcha')
		      <div class="ui negative message">
		        <strong>{{ $message }}</strong>
		      </div>
		    @enderror

		    @error('g-recaptcha-response')
		      <div class="ui negative message">
		        <strong>{{ $message }}</strong>
		      </div>
		    @enderror

		    @if(captcha_is_enabled('contact'))
		    <div class="field d-flex justify-content-center">
		      {!! render_captcha() !!}

		      @if(captcha_is('mewebstudio'))
		      <input type="text" name="captcha" value="{{ old('captcha') }}" class="ml-1">
		      @endif
		    </div>
		    @endif

				<div class="field">
					<button class="ui fluid circular yellow large button" type="submit">{{ __('Submit') }}</button>
				</div>
			</form>
		</div>
	</div>

@endsection