@extends('back.master')

@section('title', __('Licenses validation'))


@section('content')
<div class="one column grid" id="license-validation">
	<div class="column title rounded-corner">
		<div class="ui large w-100 form">
			<div class="ui action fluid input">
			  <input type="text" spellcheck="false" v-model="licenseKey" placeholder="{{ __('Enter licence key') }}">
			  <button class="ui yellow large button" @click="validateLicense">{{ __('Submit') }}</button>
			</div>
		</div>
	</div>

	<div class="column license-details">
		<div class="table wrapper">
			<table class="ui fluid large unstackable basic table">
				<thead>
					<tr>
						<th>{{ __('Name') }}</th>
						<th>{{ __ ('Value') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>{{ __('Item name') }}</td>
						<td>@{{ licenseData.name || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Purchased at') }}</td>
						<td>@{{ licenseData.purchased_at || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Buyer name') }}</td>
						<td>@{{ licenseData.buyer_name || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Buyer email') }}</td>
						<td>@{{ licenseData.buyer_email || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Payment processor') }}</td>
						<td>@{{ licenseData.processor || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Transaction ID') }}</td>
						<td>@{{ licenseData.transactions_id || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Reference ID') }}</td>
						<td>@{{ licenseData.reference_id || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Order ID') }}</td>
						<td>@{{ licenseData.order_id || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('CS token') }}</td>
						<td>@{{ licenseData.cs_token || '-' }}</td>
					</tr>
					<tr>
						<td>{{ __('Guest token') }}</td>
						<td>@{{ licenseData.guest_token || '-' }}</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="ui fluid large segment orange rounded-corner">
			<div class="ui big basic red circular label mb-1 px-1">{{ __('REST API') }}</div>
			<div class="ui large fluid form">
				<div class="two fields">
					<div class="field">
						<label>{{ __('Email') }}</label>
						<input type="email" v-model="email" disabled>
					</div>
					<div class="field">
						<label>{{ __('Password') }}</label>
						<input type="password" v-model="password" :disabled="bearerTokenSaved" placeholder="{{ __('Enter your password') }}">
					</div>
				</div>
			</div>
			<div class="d-flex" v-if="bearerToken.length">
				<p>BEARER_TOKEN : @{{ bearerToken }}</p>
				<span class="ml-1-hf">
					<button class="ui yellow large icon circular button" title="{{ __('Save') }}" @click="saveBearerToken" v-if="!bearerTokenSaved">
						<i class="save icon mx-0"></i>
					</button>
					<button class="ui red large icon circular button" title="{{ __('Delete') }}" @click="deleteBearerToken" v-else>
						<i class="trash alternate outline icon mx-0"></i>
					</button>
				</span>
			</div>

			<div class="example">
				<div class="ui circular large basic blue label mb-1-hf">{{ __('Example') }}</div>
				<div>
					curl -X POST https://@{{ host }}/api/validate_license \ <br>
					-H 'Authorization: Bearer BEARER_TOKEN' \ <br>
					-d 'licenseKey=LICENSE_KEY'
				</div>
			</div>

			<div class="response">
				<div class="ui circular large basic blue label mb-1-hf">{{ __('Response') }}</div>
				<div>
					{<br>
					    <span class="pl-2">"status":true,</span><br>
					    <span class="pl-2">"data":{</span><br>
					        <span class="pl-4">"created_at":"2020-09-18 19:32:16",</span><br>
					        <span class="pl-4">"reference_id":"1eaf9dd4-7482-6b0e-8341-a45d361463c5",</span><br>
					        <span class="pl-4">"order_id":2X377779ER373480T,</span><br>
					        <span class="pl-4">"processor":"paypal",</span><br>
					        <span class="pl-4">"transaction_id":7J24955043928512B,</span><br>
					        <span class="pl-4">"cs_token":null,</span><br>
					        <span class="pl-4">"guest_token":null,</span><br>
					        <span class="pl-4">"buyer_email":"jake05@gmail.com",</span><br>
					        <span class="pl-4">"buyer_name":"Jake2020",</span><br>
					        <span class="pl-4">"name":"E-commerce UI KIT"</span><br>
					    <span class="pl-2">}</span><br>
					}
				</div>
			</div>
		</div>
	</div>

	<div class="ui small license modal">
		<div class="content" v-html="message"></div>
	</div>
</div>


<script>
	'use strict';

	var app = new Vue({
		el: '#license-validation',
		data: {
			host: location.host,
			licenseKey: '',
			licenseData: {
				name: null,
				purchased_at: null,
				buyer_name: null,
				buyer_email: null,
				processor: null,
				transaction_id: null,
				reference_id: null,
				order_id: null,
				guest_token: null
			},
			bearerToken: localStorage.getItem('bearerToken') || '',
			bearerTokenSaved: String(localStorage.getItem('bearerToken') || '').length > 0,
			email: '{{ auth()->user()->email }}',
			password: '',
			message: ''
		},
		methods: {
			validateLicense: function()
			{
				if(this.licenseKey.length)
				{
					Vue.nextTick(function()
					{
						$.post('{{ route('validate_license') }}', {licenseKey: app.licenseKey})
						.done(function(response)
						{							
							if(response.status)
							{
								app.licenseData = response.data;
							}
							else
							{
								app.message = '{{ __("The given license key doesn't correspond to any purchase.") }}';

								Vue.nextTick(function()
								{
									$('.ui.license.modal').modal('show')
								
									app.licenseData = {
										name: null,
										purchased_at: null,
										buyer_name: null,
										buyer_email: null,
										processor: null,
										transaction_id: null,
										reference_id: null,
										order_id: null,
										guest_token: null
									};
								})
							}
						})
						.fail(function(data)
						{
							app.message = data.responseJSON.message;

							Vue.nextTick(function()
							{
								$('.ui.license.modal').modal('show')
							})
						})
					})
				}
			},
			base64Encode: function(str)
			{
			  return window.btoa(unescape(encodeURIComponent(str)));
			},
			saveBearerToken: function()
			{
				localStorage.setItem('bearerToken', this.bearerToken);
				this.bearerTokenSaved = true;
			},
			deleteBearerToken: function()
			{
				localStorage.removeItem('bearerToken');
				this.bearerTokenSaved = false;
			}
		},
		watch: {
			password: function(val)
			{
				if(val.length)
				{
					this.bearerToken = this.base64Encode(this.email+':'+this.password);
				}
				else
				{
					this.bearerToken = '';
				}
			}
		},
		mounted: function()
		{
			if(this.email.length && this.password.length)
			{
				this.bearerToken = base64Encode(this.email+':'+this.password);
			}
		}
	})
</script>
@endsection