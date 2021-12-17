@extends(view_path('master'))

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

	<div class="ui one column shadowless celled grid my-0" id="support">
		<div class="column">

			<div class="title-wrapper">
				<h1>{{ __('Support') }}</h1>
				<div class="ui big breadcrumb">
					<a href="/" class="section">{{ __('Home') }}</a>
					<i class="right chevron icon divider"></i>
					<span class="active section">Support</span>
				</div>
			</div>

			<div class="faq p-2">
				<div class="title">{{ __('Frequently asked questions') }}</div>
				<div class="content">
					<div class="ui borderless segments">
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
			</div>

			<div class="contact p-2">
				<div class="title">{{ __('Contact us') }}</div>



				<form action="{{ route('home.support') }}" method="post" class="ui large form">
					@csrf

					@if(session('support_response'))
					<div class="ui small bold positive message mx-auto">
						{{ session('support_response') }}
					</div>
					@endif
					
					<div class="field">
						<label>{{ __('Email') }}</label>
						<input type="email" value="{{ old('email', request()->user()->email ?? '') }}" name="email" placeholder="Your email..." required>
					</div>

					<div class="field">
						<label>{{ __('Subjet') }}</label>
						<input type="text" name="subject" value="{{ old('subject') }}" placeholder="..." required>
					</div>

					<div class="field">
						<label>{{ __('Question') }}</label>
						<textarea name="message" cols="30" rows="10" placeholder="..." required>{{ old('message') }}</textarea>
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
						<button class="ui circular large yellow button" type="submit">{{ __('Submit') }}</button>
					</div>
				</form>
			</div>
		</div>

	</div>

@endsection