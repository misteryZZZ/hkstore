@extends(view_path('master'))

@section('additional_head_tags')
<meta name="robots" value="noindex;nofollow">
@endsection


@section('body')
	<div class="one column row w-100 failure" id="checkout-response">
		<div class="ui fluid card">
			<div class="content title">
				{{ $message }}
			</div>
			
			<div class="content center aligned">
				<a href="/" class="ui yellow circular big button mx-0">{{ __('Back to Home page') }}</a>
			</div>
		</div>
	</div>
@endsection