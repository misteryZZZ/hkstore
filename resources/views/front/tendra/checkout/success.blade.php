{{-- TENDRA --}}

@extends(view_path('master'))

@section('additional_head_tags')
@if(session('guest_token'))
<script type="application/javascript" src="{{ asset_("assets/FileSaver.2.0.4.min.js") }}"></script>
@endif

<meta name="robots" value="noindex;nofollow">
@endsection

@section('body')
	<div class="column row w-100 success" id="checkout-response">
		<div class="ui fluid card">
			<div class="content title">
				{{ __('Order completed') }}
			</div>
			
			<div class="content">
				<div class="header icon"><i class="check icon mx-0"></i></div>
				<div class="ui header text">
					{{ __('Thank you for your order!') }}
					@if(session('processor') !== 'offline')
					<div class="sub header mt-1">
						@if(!session('guest_token'))
						{{ __('You will receive an email once your payment is confirmed.') }}
						@else
						{{ __('Your purchase will take effect once your payment is confirmed.') }}
						@endif
					</div>
					@endif
				</div>
			</div>
			
			@if(session('guest_token'))
			<script type="application/javascript">
				'use strict';

				var tokenDownloaded = false;
				var content  = "{{ session('guest_token') }}";
				var filename = "{{ session('transaction_id') }}-access-token.txt";
				var blob 		 = new Blob([content], {type: "text/plain;charset=utf-8"});

				function redirectHomePage()
				{
					if(!tokenDownloaded)
					{
						saveAs(blob, filename);
					}
					
					location.href = "/";
				}

				function downloadAccessToken()
				{
					saveAs(blob, filename);

					tokenDownloaded = true;
				}
			</script>

			<div class="content guest-token">
				<div class="header">{{ __('Access token') }} <i class="exclamation circle icon mr-0 ml-1" title="{{ __('Make sure to keep this token in a safe place, you will need it to download the items you purchased as guest.') }}"></i></div>
				<div class="token">
					{{ session('guest_token') }}
					<a class="download" onclick="downloadAccessToken()">{{ __('Download') }}</a>
				</div>
			</div>
			@endif

			<div class="content center aligned">
				@if(session('guest_token'))
				<a onclick="redirectHomePage()" class="ui yellow circular big button mx-0">{{ __('Back to Home page') }}</a>
				@else
				<a href="/" class="ui yellow circular big button mx-0">{{ __('Back to Home page') }}</a>
				@endif
			</div>
		</div>
	</div>
@endsection