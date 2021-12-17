{{-- TENDRA --}}

@extends(view_path('master'))

@section('additional_head_tags')
<script type="application/javascript" src="{{ asset_("assets/FileSaver.2.0.4.min.js") }}"></script>

<script type="application/javascript">
	'use strict';
	window.props['itemId'] = null;
	window.props['keycodes'] = @json($keycodes ?? []);
</script>
@endsection

@section('body')
<div class="ui shadowless celled one column grid my-0" id="user">	
	<div class="column title rounded-corner">
		<div class="ui secondary menu">
			<a href="{{ route('home.profile') }}" class="header item {{ route_is('home.profile') ? 'active' : '' }}">{{ __('Profile') }}</a>
			<a href="{{ route('home.notifications') }}" class="header item {{ route_is('home.notifications') ? 'active' : '' }}">{{ __('Notifications') }}</a>
			<a href="{{ route('home.favorites') }}" class="header item {{ route_is('home.favorites') ? 'active' : '' }}">{{ __('Collection') }}</a>
			<a href="{{ route('home.purchases') }}" class="header item {{ route_is('home.purchases') ? 'active' : '' }}">{{ __('Purchases') }}</a>
			@if(config('app.subscriptions.enabled'))
			<a href="{{ route('home.user_subscriptions') }}" class="header item {{ route_is('home.user_subscriptions') ? 'active' : '' }}">{{ __('Subscriptions') }}</a>
			@endif
			<a href="{{ route('home.invoices') }}" class="header item {{ route_is('home.invoices') ? 'active' : '' }}">{{ __('Invoices') }}</a>
		</div>
	</div>

	@if($errors->any())
  @foreach ($errors->all() as $error)
     <div class="ui negative fluid circular-corner bold w-100 large message">
     	<i class="times icon close"></i>
     	{{ $error }}
     </div>
  @endforeach
	@endif
	
	@if(route_is('home.purchases'))

		<div class="column items purchases px-0 mt-1">
			@if($products)

			<div class="items-list">
				<div class="titles">
					<div class="cover">{{ __('Cover') }}</div>
					<div class="name">{{ __('Name') }}</div>
					<div class="category">{{ __('Category') }}</div>
					<div class="rating">{{ __('Rating') }}</div>
					<div class="purchased_at">{{ __('Purchased at') }}</div>
					<div class="updated_at">{{ __('Updated at') }}</div>
					<div>-</div>
				</div>
				@foreach($products ?? [] as $product)
				<div class="content">
					<div class="cover">
						<a href="{{ item_url($product) }}" style="background-image: url({{ asset_("storage/covers/{$product->cover}") }})"></a>
					</div>
					<div class="name">
						<a href="{{ item_url($product) }}">{{ $product->name }}</a>
					</div>
					<div class="category capitalize">
						<a href="{{ category_url($product->category_slug) }}">{{ $product->category_name }}</a>
					</div>
					<div class="rating">
						<span class="image rating">{!!  item_rating($product->rating) !!}</span>
					</div>
					<div class="purchased_at">{{ format_date($product->purchased_at, 'jS M Y \a\\t H:i') }}</div>
					<div class="updated_at">{{ format_date($product->updated_at, 'jS M Y \a\\t H:i') }}</div>
					<div class="download">
						@if(!$product->refunded)
							@if($product->is_dir)
								@if($product->enable_license || $product->key_code)
								<div class="ui floating default yellow button large circular dropdown mx-0">
									<div class="text">{{ __('Action') }}</div>
									<div class="menu">
									    @if($product->file_name)
										<a class="item" href="{{ item_folder_sync($product) }}">{{ __('Open Folder') }}</a>
										@endif
										@if($product->enable_license)
										<a class="item" @click="downloadLicense({{ $product->id }}, '#download-license')">{{ __('License key') }}</a>
										@endif
										@if($product->key_code)
										<a class="item" @click="downloadKey('{{ $product->key_id }}', '{{ $product->slug }}')">{{ __('Key code') }}</a>
										@endif
									</div>
								</div>
								@elseif($product->file_name)
									<a class="ui yellow button large circular" href="{{ item_folder_sync($product) }}">
										{{ __('Open Folder') }}
									</a>
								@endif
							@else
								@if($product->enable_license || $product->key_code)
								<div class="ui floating default yellow button large circular dropdown mx-0">
									<div class="text">{{ __('Download') }}</div>
									<div class="menu">
									  @if($product->file_name)
										  @if(config("app.direct_download_links.enabled"))
										  <a class="item" target="_blank" href="{!! get_direct_download_link($product->id) !!}">{{ __('Files') }}</a>
										  @else
											<a class="item" @click="downloadItem({{ $product->id }})">{{ __('Files') }}</a>
											@endif
										@endif
										@if($product->enable_license)
										<a class="item" @click="downloadLicense({{ $product->id }}, '#download-license')">{{ __('License key') }}</a>
										@endif
										@if($product->key_code)
										<a class="item" @click="downloadKey('{{ $product->key_id }}', '{{ $product->slug }}')">{{ __('Key code') }}</a>
										@endif
									</div>
								</div>
								@elseif($product->file_name)
									@if(config("app.direct_download_links.enabled"))
								  <a class="ui yellow button large circular mx-0" target="_blank" href="{!! get_direct_download_link($product->id) !!}">
									  {{ __('Download') }}
									</a>
								  @else
								  <a class="ui yellow button large circular mx-0" @click="downloadItem({{ $product->id }})">{{ __('Download') }}</a>
								  @endif
								@endif
							@endif
						@else
							<div class="ui red big basic label circular">{{ __('Refunded') }}</div>
						@endif
					</div>
				</div>
				@endforeach
				<div class="content">
					@if($products->hasPages())
					{{ $products->appends(request()->query())->onEachSide(1)->links() }}
					{{ $products->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
					@endif
				</div>
			</div>

			<form action="{{ route('home.download') }}" class="d-none" method="post" id="download-form">
				@csrf
				<input type="hidden" name="itemId" v-model="itemId">
			</form>

			<form action="{{ route('home.download_license') }}" class="d-none" method="post" id="download-license">
				@csrf
				<input type="hidden" name="itemId" v-model="itemId">
			</form>

			@else

			<div class="ui fluid large white rounded-corner bold message m-1">
				{{ __('You have not purchased any item yet.') }}
			</div>

			@endif
		</div>

	@elseif(route_is('home.favorites'))
	
		<div class="column items favorites px-0 mt-1">
			<div class="wrapper w-100" v-if="Object.keys(favorites).length" v-cloak>

				<div class="item" v-for="product in favorites">

					<div class="cover">
						<a :title="product.name" :href="'item/' + product.id + '/' + product.slug" :style="'background-image: url(storage/covers/' + product.cover + ')'">
								</a>
					</div>

					<div class="name">
						<a :title="product.name" :href="'item/' + product.id + '/' + product.slug">@{{ product.name }}</a>
					</div>

					<div class="category">
						<a class="ui pink circular button" :href="'/items/category/' + product.category_slug">@{{ product.category_name }}</a>
					</div>

					<div class="sales">@{{ __(':count Sales', {'count': product.sales}) }}</div>

					<div class="price">@{{ price(product.price) }}</div>

					<div class="actions">
						<button class="ui yellow circular button" @click="addToCartAsync(product, $event)">@{{ __('Add to cart') }}</button>
						<button class="ui red circular button" @click="collectionToggleItem($event, product.id)">@{{ __('Remove') }}</button>
					</div>

				</div>

			</div>
			
			<div class="ui fluid large white rounded-corner bold message m-1" v-else v-cloak>
				{{ __('Your collection is empty.') }}
			</div>
		</div>

	@elseif(route_is('home.user_subscriptions') && config('app.subscriptions.enabled'))
	
		<div class="column items subscriptions px-0 mt-1">
			@if($user_subscriptions->count())

			<div class="wrapper">
				<div class="item titles">
					<div class="name">{{ __('Name') }}</div>
					<div class="date">{{ __('Starts at') }}</div>
					<div class="date">{{ __('Expires at') }}</div>
					<div class="days">{{ __('Remaining days') }}</div>
					<div class="downloads">{{ __('Downloads') }}</div>
					<div class="downloads">{{ __('Daily Downloads') }}</div>
					<div class="status">{{ __('Status') }}</div>
				</div>

				@foreach($user_subscriptions as $user_subscription)
				<div class="item">
					<div class="name">{{ $user_subscription->name }}</div>
					<div class="date">{{ format_date($user_subscription->starts_at, 'jS M Y \a\\t H:i') }}</div>
					<div class="date">
						@if($user_subscription->ends_at)
						{{ format_date($user_subscription->ends_at, 'jS M Y \a\\t H:i') }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="days">
						@if($user_subscription->ends_at)
						{{ $user_subscription->remaining_days }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="downloads">
						@if($user_subscription->limit_downloads > 0)
						{{ "{$user_subscription->downloads}/{$user_subscription->limit_downloads}" }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="downloads">
						@if($user_subscription->limit_downloads_per_day > 0)
						{{ "{$user_subscription->daily_downloads}/{$user_subscription->limit_downloads_per_day}" }}
						@else
						{{ __('Unlimited') }}
						@endif
					</div>
					<div class="status">
						@if($user_subscription->expired)
						<div class="ui red basic circular large label">{{ __('Expired') }}</div>
						@elseif(!$user_subscription->payment_status)
						<div class="ui orange basic circular large label">{{ __('Pending') }}</div>
						@else
						<div class="ui teal basic circular large label">{{ __('Active') }}</div>
						@endif
					</div>
				</div>
				@endforeach
			</div>

			@else

			<div class="ui fluid large white rounded-corner bold message m-1">
				{{ __("You don't have any subscription.") }}
			</div>

			@endif
		</div>

	@elseif(route_is('home.notifications'))
	
		<div class="column items notifications mt-1">
	    @if($notifications->count())
	    
	    <div class="items">
	      @foreach($notifications as $notification)
	      <a class="item mx-0 @if(!$notification->read) unread @endif"
	          data-id="{{ $notification->id }}"
	          data-href="{{ route('home.product', ['id' => $notification->product_id, 'slug' => $notification->slug . ($notification->for == 1 ? '#support' : ($notification->for == 2 ? '#reviews' : ''))]) }}">

	        <div class="image" style="background-image: url({{ asset_("storage/".($notification->for == 0 ? 'covers' : 'avatars')."/{$notification->image}") }})"></div>

	        <div class="content pl-1">
	          <p>{!! __($notification->text, ['product_name' => "<strong>{$notification->name}</strong>"]) !!}</p>
	          <time>{{ \Carbon\Carbon::parse($notification->updated_at)->diffForHumans() }}</time>
	        </div>
	      </a>
	      @endforeach
	    </div>

	    @if($notifications->hasPages())
	    <div class="ui divider"></div>

	    {{ $notifications->onEachSide(1)->links() }}
		  {{ $notifications->links('vendor.pagination.simple-semantic-ui') }}
	    @endif
	    @else
	    
	    <div class="ui fluid large white rounded-corner bold message m-1">
				{{ __("You don't have any notification.") }}
			</div>
	    @endif
		</div>

	@elseif(route_is('home.profile'))
	
		<div class="column items profile p-1 mt-1">
			<form class="ui large form w-100" action="{{ route('home.profile') }}" enctype="multipart/form-data" method="post">
				@csrf

				<div class="field avatar">
					<div class="ui unstackable items">
						<div class="item">
							<div class="content">
								<div class="ui circular image">
									<img src="{{ asset_("storage/avatars/".($user->avatar ?? 'default.jpg').'?v='.time()) }}">
								</div>

								<button class="ui yellow circular button mx-0" type="button" 
												onclick="$('#user .profile input[name=\'avatar\']').click()">{{ __('Upload') }}</button>
								<input type="file" name="avatar" class="d-none">
							</div>

							<div class="content">
								<div class="name">{{ $user->name ?? null }}</div>
								<div class="country">{{ $user->country ?? null}}</div>
								<div class="member-since">{{ format_date($user->created_at, 'd F Y') }}</div>
								<div class="email">
									{{ $user->email }}
									@if(config('app.email_verification'))
									@if($user->email_verified_at)
									<sup class="verified">({{ __('Verified') }})</sup>
									@else
									<sup>({{ __('Unverified') }} - <a @click="sendEmailVerificationLink('{{ $user->email }}')">{{ __('Send verification link') }}</a>)</sup>
									@endif
									@endif
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('First name') }}</label>
						<input type="text" name="firstname" value="{{ old('firstname', $user->firstname ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('Last name') }}</label>
						<input type="text" name="lastname" value="{{ old('lastname', $user->lastname ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('Username') }}</label>
						<input type="text" name="name" value="{{ old('name', $user->name ?? null) }}" required>
					</div>
					<div class="field">
						<label>{{ __('Affiliate name') }}</label>
						<input type="text" name="affiliate_name" value="{{ old('affiliate_name', $user->affiliate_name ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('Country') }}</label>
						<input type="text" name="country" value="{{ old('country', $user->country ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('City') }}</label>
						<input type="text" name="city" value="{{ old('city', $user->city ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('Address') }}</label>
						<input type="text" name="address" value="{{ old('address', $user->address ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('Zip code') }}</label>
						<input type="text" name="zip_code" value="{{ old('zip_code', $user->zip_code ?? null) }}">
					</div>
				</div>

				<div class="two fields">
					<div class="field">
						<label>{{ __('ID number') }}</label>
						<input type="text" name="id_number" value="{{ old('id_number', $user->id_number ?? null) }}">
					</div>
					<div class="field">
						<label>{{ __('Phone') }}</label>
						<input type="text" name="phone" value="{{ old('phone', $user->phone ?? null) }}">
					</div>	
				</div>
				
				<div class="field">
					<label>{{ __('State') }}</label>
					<input type="text" name="state" value="{{ old('state', $user->state ?? null) }}">
				</div>

				@if(config('affiliate.enabled') && mb_strlen($user->affiliate_name))
				<div class="field">
					<label>{{ __('Earnings cashout method') }}</label>
					<div class="ui floating selection fluid dropdown">
						<input type="hidden" value="{{ old('cashout_method', $user->cashout_method) }}" name="cashout_method">
						<div class="text"></div>
						<div class="menu">
							@if(config('affiliate.cashout_methods.paypal_account'))
							<a class="item" data-value="paypal_account">{{ __('Paypal account') }}</a>
							@endif

							@if(config('affiliate.cashout_methods.bank_account'))
							<a class="item" data-value="bank_account">{{ __('Bank Transfer') }}</a>
							@endif
						</div>
					</div>
				</div>

				<div class="option paypal_account {{ $user->cashout_method != 'paypal_account' ? 'd-none' : '' }} mb-1">
					<div class="field">
						<label>{{ __('PayPal Email Address') }}</label>
						<input type="text" name="paypal_account" value="{{ old('paypal_account', $user->paypal_account ?? null) }}">
					</div>
				</div>

				<div class="option bank_account {{ $user->cashout_method != 'bank_account' ? 'd-none' : '' }} mb-1">
					<div class="field">
						<label>{{ __('Bank address') }}</label>
						<input type="text" name="bank_account[bank_address]" value="{{ old('bank_account.bank_address', $user->bank_account->bank_address ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Account holder name') }}</label>
						<input type="text" name="bank_account[holder_name]" value="{{ old('bank_account.holder_name', $user->bank_account->holder_name ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Account holder address') }}</label>
						<input type="text" name="bank_account[holder_address]" value="{{ old('bank_account.holder_address', $user->bank_account->holder_address ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('Account number') }}</label>
						<input type="text" name="bank_account[account_number]" value="{{ old('bank_account.account_number', $user->bank_account->account_number ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('IBAN Code') }}</label>
						<input type="text" name="bank_account[iban]" value="{{ old('bank_account.iban', $user->bank_account->iban ?? null) }}">
					</div>

					<div class="field">
						<label>{{ __('SWIFT Code') }}</label>
						<input type="text" name="bank_account[swift]" value="{{ old('bank_account.swift', $user->bank_account->swift ?? null) }}">
					</div>
				</div>
				@endif

				<div class="field">
					<label>{{ __('Receive notifications via email') }}</label>
					<div class="ui floating selection fluid dropdown">
						<input type="hidden" value="{{ old('receive_notifs', $user->receive_notifs ?? '1') }}" name="receive_notifs">
						<div class="text"></div>
						<div class="menu">
							<a class="item" data-value="1">{{ __('Yes') }}</a>
							<a class="item" data-value="0">{{ __('No') }}</a>
						</div>
					</div>
				</div>
				
				<div class="ui fluid yellow shadowless segment">
					<h4 class="ui red header">{{ __('Change password') }}</h4>

					<div class="two fields mb-0">
						<div class="field">
							<label>{{ __('Old password') }}</label>
							<input type="text" name="old_password" value="{{ old('old_password') }}">
						</div>
						<div class="field">
							<label>{{ __('New password') }}</label>
							<input type="text" name="new_password" value="{{ old('old_password') }}">
						</div>
					</div>	
				</div>
				
				<div class="ui fluid divider"></div>

				<div class="field">
					<button class="ui blue circular button" type="submit">{{ __('Save changes') }}</button>
				</div>
			</form>
		</div>

	@elseif(route_is('home.invoices'))

		<div class="column items invoices mt-1">
			@if($invoices)

			<div class="table wrapper">
				<table class="ui basic large unstackable table">
					<thead>
						<tr>
							<th>{{ __('Reference') }}</th>
							<th>{{ __('Date') }}</th>
							<th>{{ __('Amount') }}</th>
							<th>{{ __('Export PDF') }}</th>
						</tr>
					</thead>
					<tbody>
						@foreach($invoices ?? [] as $invoice)
						<tr>
							<td>{{ $invoice->reference_id }}</td>
							<td>{{ $invoice->created_at }}</td>
							<td>{{ $invoice->currency .' '. $invoice->amount }}</td>
							<td><button class="ui large yellow circular button" type="button" @click="downloadItem({{ $invoice->id }}, '#download-invoice')">{{ __('Export') }}</button></td>
						</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			@if($invoices->hasPages())
			<div class="ui hidden divider"></div>
			{{ $invoices->appends(request()->query())->onEachSide(1)->links() }}
			{{ $invoices->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
			@endif

			<form action="{{ route('home.export_invoice') }}" class="d-none" method="post" id="download-invoice">
				@csrf
				<input type="hidden" name="itemId" v-model="itemId">
			</form>

			@else

			<div class="ui fluid large white rounded-corner bold message m-1">
				{{ __('No invoice found.') }}
			</div>

			@endif
		</div>
	@endif
</div>

@endsection