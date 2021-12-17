{{-- VALExA TEMPLATE --}}

@extends(view_path('master'))

@section('additional_head_tags')
<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "Product",
	"url": "{{ url()->current() }}",
  "name": "{{ $product->name }}",
  "image": "{{ $meta_data->image }}",
  "description": "{!! $product->short_description !!}",
  @if($product->reviews_count)
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ $product->rating }}",
    "reviewCount": "{{ $product->reviews_count }}",
    "bestRating": "5",
    "worstRating": "0"
  },
  "review": [
		@foreach($reviews as $review)
		{
			"@type": "Review",
			"author": "{{ $review->name ?? $review->alias_name }}",
			"datePublished" : "{{ (new DateTime($review->created_at))->format('Y-m-d') }}",
			"description": "{{ $review->content }}",
			"name": "-",
			"reviewRating": {
        "@type": "Rating",
        "bestRating": "5",
        "ratingValue": "{{ $review->rating }}",
        "worstRating": "0"
      }
		}@if(next($reviews)),@endif
		@endforeach
  ],
  @endif
  "category": "{!! $product->category !!}",
  "offers": {
    "@type": "AggregateOffer",
    "lowPrice": "{{ number_format($product_prices[$product->license_id]['price'] ?? 0, 2) }}",
    "highPrice": "{{ number_format($product_prices[$product->license_id]['price'] ?? 0, 2) }}",
    "priceCurrency": "{{ config('payments.currency_code') }}",
    "offerCount": "1"
  },
  "brand": "-",
  "sku": "-",
  "mpn": "{{ $product->id }}"
}
</script>

<script>
	'use strict';

  window.props['product'] = {
  	screenshots: {!! json_encode($product->screenshots) !!},
		id: {{ $product->id }},
		name: '{{ $product->name }}',
		cover: '{{ asset("storage/covers/{$product->cover}") }}',
		quantity: 1,
		license_id: '{{ $product->license_id }}',
		license_name: '{{ $product->license_name }}',
		url: '{{ item_url($product) }}',
		price: {{ $product_prices[$product->license_id]['price'] ?? '0' }},
		slug: '{{ $product->slug }}'
  }

  window.props['licenseId'] = {{ $product->license_id }};
  window.props['itemPrices'] = @json($product_prices);

  window.props['products'] = @json($similar_products->reduce(function ($carry, $item) 
																	{
																	  $carry[$item->id] = $item;
																	  return $carry;
																	}, []));
</script>
@endsection


@section('body')
	
{!! place_ad('ad_728x90') !!}

<div class="ui two columns shadowless celled grid my-0 px-1 type-{{ $product->type }}" id="item" vhidden>	

	<div class="row">
		<div class="column mx-auto l-side">
			<div class="row menu">
				<div class="ui top secondary menu p-1">
				  <a class="active item ml-0" data-tab="details">{{ __('Details') }}</a>
				  @if($product->hidden_content && ($valid_subscription || $product->purchased || $product->free))
				  <a class="item" data-tab="hidden-content">{{ __('Hidden content') }}</a>
				  @endif

				  @if($product->table_of_contents)
				  <a class="item mr-0" data-tab="table_of_contents">{{ __('Table of contents') }}</a>
				  @endif

				  <a class="item" data-tab="support">{{ __('Support') }}</a>
				  <a class="item" data-tab="reviews">{{ __('Reviews') }}</a>
				  <a class="item mr-0" data-tab="faq">{{ __('FAQ') }}</a>
				  @if(isFolderProcess() && $product->file_name)
				  <a class="item mr-0" data-tab="files" @click="getFolderContent">{{ __('Files') }}</a>
				  @endif
				</div>
			</div>

			<div class="row item">
				<div class="sixteen wide column details">
					<div class="row center aligned cover">
						@if($product->type_is('ebook'))
						<div class="cover-wrapper ebook">
							<div class="left">
								<div class="ui image">
									<img src="{{ asset_("storage/covers/{$product->cover}") }}">
								</div>

								@if(!out_of_stock($product) && !$product->for_subscriptions)
									<div class="price-holder" :class="{reduced: itemHasPromo}">
										@if($product->free)
						    		<div class="price free">{{ __('Free') }}</div>
						    		@else
						    		<div class="price promo" v-if="itemHasPromo">@{{ price(itemPrices[licenseId]['promo_price']) }}</div>
						    		<div class="price" v-if="!itemIsFree()">@{{ price(itemPrices[licenseId]['price']) }}</div>
						    		<div class="price free" v-else>@{{ __('Free') }}</div>
						    		@endif
									</div>
								@endif
							</div>

							<div class="right">
								<div class="title">
									{!! $product->name !!}
								</div>
								
								@if($product->authors)
								<div class="authors">
									<span>{{ __('Authors') }}</span>
									<span>{!! $product->authors !!}</span>
								</div>
								@endif

								@if($product->language)
								<div class="language">
									<span>{{ __('Language') }}</span>
									<span>{!! $product->language !!}</span>
								</div>
								@endif

								@if($product->words)
								<div class="words">
									<span>{{ __('Words') }}</span>
									<span>{!! $product->words !!}</span>
								</div>
								@endif

								@if($product->pages)
								<div class="pages">
									<span>{{ __('pages') }}</span>
									<span>{!! $product->pages !!}</span>
								</div>
								@endif
							</div>
						</div>
						@elseif($product->type_is('audio'))

						<div class="cover-wrapper audio">
							<div class="left">
								<div class="ui image" style="background-image: url({{ asset_("storage/covers/{$product->cover}") }})"></div>
								@if(!out_of_stock($product) && !$product->for_subscriptions)
									<div class="price-holder" :class="{reduced: itemHasPromo}">
										@if($product->free)
						    		<div class="price free">{{ __('Free') }}</div>
						    		@else
						    		<div class="price promo" v-if="itemHasPromo">@{{ price(itemPrices[licenseId]['promo_price']) }}</div>
						    		<div class="price" v-if="!itemIsFree()">@{{ price(itemPrices[licenseId]['price']) }}</div>
						    		<div class="price free" v-else>@{{ __('Free') }}</div>
						    		@endif
								</div>
								@endif
							</div>
							<div class="right">
								<div class="title">
									{!! $product->name !!}
								</div>
								
								@if($product->authors)
								<div class="authors">
									<span>{{ __('Authors') }}</span>
									<span>{!! $product->authors !!}</span>
								</div>
								@endif

								@if($product->label)
								<div class="label">
									<span>{{ __('Label') }}</span>
									<span>{!! $product->label !!}</span>
								</div>
								@endif

								@if($product->bpm)
								<div class="bpm">
									<span>{{ __('BPM') }}</span>
									<span>{!! $product->bpm !!}</span>
								</div>
								@endif

								@if($product->bit_rate)
								<div class="bit_rate">
									<span>{{ __('Bit rate') }}</span>
									<span>{!! $product->bit_rate !!}</span>
								</div>
								@endif
							</div>
						</div>

						<div class="audio-container" data-src="{{ preview_link($product) }}" data-id="{{ $product->id }}">
							<div class="player">
								<div class="timeline"><div class="wave"></div></div>

								<div class="actions">
									<button class="ui link circular basic white play button visible">
										{{ __('Play') }}
									</button>
									<button class="ui link circular basic white pause button">
										{{ __('Pause') }}
									</button>
									<button class="ui link circular basic white stop button">
										{{ __('Stop') }}
									</button>
								</div>

								<div class="duration">00:00</div>
							</div>
						</div>

						@else
						<div class="cover-wrapper">
							@if(!out_of_stock($product) && !$product->for_subscriptions)
								<div class="price-holder" :class="{reduced: itemHasPromo}">
									@if($product->free)
					    		<div class="price free">{{ __('Free') }}</div>
					    		@else
					    		<div class="price promo" v-if="itemHasPromo">@{{ price(itemPrices[licenseId]['promo_price']) }}</div>
					    		<div class="price" v-if="!itemIsFree()">@{{ price(itemPrices[licenseId]['price']) }}</div>
					    		<div class="price free" v-else>@{{ __('Free') }}</div>
					    		@endif
								</div>
							@endif

							@if($product->has_preview('video'))
					  		<div class="video">
								{!! preview($product) !!}
							</div>
							@else
							<div class="ui image">
								<img src="{{ asset_("storage/covers/{$product->cover}") }}">
    					    </div>
    					    @endif

					    <h1>{!! $product->name !!}</h1>
						</div>
						@endif

						@if(out_of_stock($product))
						<div class="ui fluid red shadowless borderless message d-table mx-auto circular-corner bold">{{ __('This item is out of stock.') }}</div>
						@endif

						@if($product->for_subscriptions)
						<div class="ui fluid blue shadowless borderless message circular-corner d-table mx-auto bold">{{ __('This item is available via subscriptions only.') }}</div>
						@endif

						@if(!is_null($product->minimum_price) && config('pay_what_you_want.enabled') && config('pay_what_you_want.for.products'))
				    <div class="content minimum-item-price ui large form mb-1 mx-auto w-50">
				    	<div class="field">
				    		<input class="rounded-corner" type="number" step="0.0001" v-model="customItemPrice" placeholder="{{ __('Custom price (minimum :price)', ['price' => price($product->minimum_price, false)]) }}">
				    	</div>
				    </div>
				    @endif
				    
						<div class="actions">
							@if($product->file_name && ($valid_subscription || $product->purchased))
								@if(!$product->is_dir)
								<button class="ui button rounded large download" @click="downloadItem({{ $product->id }}, '#download')">{{ __('Download') }} 
									@if($product->remaining_downloads)
									<sup>({{ $product->remaining_downloads }})</sup>
									@endif
								</button>
								<form action="{{ route('home.download') }}" class="d-none" id="download" method="post">
									@csrf
									<input type="hidden" name="itemId" v-model="itemId">
								</form>
								@else
								<a class="ui open-dir large fluid rounded button" target="_blank" href="{{ route('home.product_folder_sync', $product->slug) }}">{{ __('Open folder') }}</a>
								@endif
								<div class="ui hidden divider"></div>
							@endif

							@if(!$product->for_subscriptions && !out_of_stock($product))
								<div>
									@if(!$product->free)
							  	<button class="ui purple large rounded button" v-if="!itemIsFree()" @click="buyNow(product, $event)">{{ __('Buy Now') }}</button>
							  	@endif

							  	<button class="ui blue large rounded button" @click="addToCartAsync(product, $event)">{{ __('Add To Cart') }}</button>

							  	@if($product->preview_url)
									<a class="ui pink large rounded button" target="_blank" href="{{ $product->preview_url }}">{{ __('Preview') }}</a>
									@endif
								</div>

								<div class="ui fluid large floating circular button dropdown selection licenses {{ count($product_prices) <= 1 ? 'd-none disabled' : '' }}">
									<input type="hidden" name="license_id" @change="setPrice">
									@if(count($product_prices) > 1)
									<i class="dropdown icon"></i>
									@endif
									<div class="text">{{ __(mb_ucfirst($product->license_name)) }}</div>
									<div class="menu">
										@foreach($product_prices as $product_price)
										<div class="item" data-value="{{ $product_price['license_id'] }}">{{ $product_price['license_name'] }}</div>
										@endforeach
									</div>
								</div>

								@if($product->free && $product->free_time && !out_of_stock($product))
						    <div class="card promo mt-0">
									<div class="promo-count" data-json="{{ $product->free_time }}">{{ __('Ends in') }} <span></span></div>
								</div>
						    @else
						    <div class="card promo mt-0" v-if="itemHasPromo">
									<div class="promo-count" data-json="{{ collect($product_prices)->where('promotional_time', '!=', null)->first()['promotional_time'] ?? null }}">{{ __('Ends in') }} <span></span></div>
								</div>
								@endif
					  	@endif
					  </div>
					</div>

					<div class="ui hidden divider"></div>
					
					@if($product->screenshots)
					<div class="ui fluid card">
						<div class="content images body">
							<div class="ui items">
								@foreach($product->screenshots as $screenshot)
								<a class="item screenshot" data-src="{{ $screenshot }}" style="background-image: url('{{ $screenshot }}')"></a>
								@endforeach
							</div>
						</div>
					</div>
					@endif
					
					@if($product->overview)
					<div class="ui fluid card">
						<div class="content overview body">
							{!! $product->overview !!}
						</div>
					</div>
					@endif
				</div>

				@if($product->hidden_content && ($valid_subscription || $product->purchased || $product->free))
				<!-- Hidden content -->
				<div class="sixteen wide column hidden-content">
					{!! $product->hidden_content !!}
				</div>
				@endif
				
				{{-- Table of contents --}}
				@if($product->table_of_contents)
				<div class="sixteen wide column table_of_contents">
					<div class="ui segments shadowless">
						@foreach($product->table_of_contents as $title)
							@if($title->text_type === 'header')
								<div class="ui secondary segment">
							    <p>{{ $title->text }}</p>
							  </div>
							@else
								<div class="ui segment">
									<p>
										@if($title->text_type === 'subheader')
										<i class="right blue angle icon"></i>
										@else
										<span class="ml-2"></span>
										@endif

										{{ $title->text }}
									</p>
							  </div>
							@endif
					  @endforeach
					</div>
				</div>
				@endif

				<!-- Support -->
				<div class="sixteen wide column support">

					@if(session('comment_response'))

					<div class="ui fluid shadowless borderless green message circular-corner">
						{{ request()->session()->pull('comment_response') }}
					</div>

					@elseif(!$comments->count())

					<div class="ui fluid shadowless large rounded-corner message">
						{{ __('No comments found') }}.
					</div>

					@endif

					<div class="ui divided unstackable items mt-1">
						<div class="mb-1">
							@foreach($comments as $comment)
							<div class="comments-wrapper">
								<div class="item parent main-item">
									<div class="main">
										<div class="ui tiny circular image">
											<img src="{{ asset_("storage/avatars/{$comment->avatar}") }}">
										</div>

										<div class="content description body">
											<h3>
												{{ $comment->name ?? $comment->alias_name ?? $comment->fullname }} 
												<span class="floated right">{{ $comment->created_at->diffForHumans() }}</span>
											</h3>

											{!! nl2br($comment->body) !!}
											
											<div class="ui hidden divider mt-0"></div>

											<div class="ui form">
												@auth
												<div class="ui icon bottom right white pointing dropdown button like">
													<img src="{{ asset_('assets/images/like.png') }}" class="ui image m-0">
												  <div class="menu">
												    <div class="item reactions" data-item_id="{{ $comment->id }}" data-item_type="comment">
												    	<a class="action" data-reaction="like" style="background-image: url('{{ asset_('assets/images/reactions/like.gif') }}')"></a>
												    	<a class="action" data-reaction="love" style="background-image: url('{{ asset_('assets/images/reactions/love.gif') }}')"></a>
												    	<a class="action" data-reaction="funny" style="background-image: url('{{ asset_('assets/images/reactions/funny.gif') }}')"></a>
												    	<a class="action" data-reaction="wow" style="background-image: url('{{ asset_('assets/images/reactions/wow.gif') }}')"></a>
												    	<a class="action" data-reaction="sad" style="background-image: url('{{ asset_('assets/images/reactions/sad.gif') }}')"></a>
												    	<a class="action" data-reaction="angry" style="background-image: url('{{ asset_('assets/images/reactions/angry.gif') }}')"></a>
												    </div>
												  </div>
												</div>

												@endauth

												<button class="ui blue circular button mr-0 uppercase circular"
																@click="setReplyTo('{{ $comment->name ?? $comment->alias_name ?? $comment->fullname }}', {{ $comment->id }})">
													{{ __('Reply') }}
												</button>
											</div>
										</div>
									</div>
									<div class="extra">
										@if(count($comment->reactions ?? []))
										<div class="saved-reactions" data-item_id="{{ $comment->id }}" data-item_type="comment">
											@foreach($comment->reactions as $name => $count)
											<span class="reaction" data-reaction="{{ $name }}" data-tooltip="{{ $count }}" data-inverted="" style="background-image: url('{{ asset_("assets/images/reactions/{$name}.png") }}')"></span>
											@endforeach
										</div>
										@endif

										<div class="count">
											<span>{{ __(':count Comments', ['count' => $comment->children->count()]) }}</span>
										</div>
									</div>
								</div>

								@foreach($comment->children as $child)
								<div class="item children main-item">
									<div class="main">
										<div class="ui tiny circular image">
											<img src="{{ asset_("storage/avatars/{$child->avatar}") }}">
										</div>

										<div class="content description body">
											<h3>
												{{ $child->name ?? $child->alias_name ?? $child->fullname }} 
												<span class="floated right">{{ $child->created_at->diffForHumans() }}</span>
											</h3>

											{!! nl2br($child->body) !!}

											<div class="ui hidden divider mt-0"></div>

											<div class="ui form">
												@auth
												<div class="ui icon bottom right white pointing dropdown button like">
													<img src="{{ asset_('assets/images/like.png') }}" class="ui image m-0">
												  <div class="menu">
												    <div class="item reactions" data-item_id="{{ $child->id }}" data-item_type="comment">
												    	<a class="action" data-reaction="like" style="background-image: url('{{ asset_('assets/images/reactions/like.gif') }}')"></a>
												    	<a class="action" data-reaction="love" style="background-image: url('{{ asset_('assets/images/reactions/love.gif') }}')"></a>
												    	<a class="action" data-reaction="funny" style="background-image: url('{{ asset_('assets/images/reactions/funny.gif') }}')"></a>
												    	<a class="action" data-reaction="wow" style="background-image: url('{{ asset_('assets/images/reactions/wow.gif') }}')"></a>
												    	<a class="action" data-reaction="sad" style="background-image: url('{{ asset_('assets/images/reactions/sad.gif') }}')"></a>
												    	<a class="action" data-reaction="angry" style="background-image: url('{{ asset_('assets/images/reactions/angry.gif') }}')"></a>
												    </div>
												  </div>
												</div>

												@endauth

												<button class="ui blue circular button mr-0 uppercase circular"
																@click="setReplyTo('{{ $child->name ?? $child->alias_name ?? $child->fullname }}', {{ $comment->id }})">
													{{ __('Reply') }}
												</button>
											</div>
										</div>
									</div>
									<div class="extra">
										@if(count($child->reactions ?? []))
										<div class="saved-reactions" data-item_id="{{ $child->id }}" data-item_type="comment">
											@foreach($child->reactions as $name => $count)
											<span class="reaction" data-reaction="{{ $name }}" data-tooltip="{{ $count }}" data-inverted="" style="background-image: url('{{ asset_("assets/images/reactions/{$name}.png") }}')"></span>
											@endforeach
										</div>
										@endif
									</div>
								</div>
								@endforeach
							</div>
							@endforeach
						</div>
						
						@auth

						<form class="item ui form" method="post" action="{{ item_url($product) }}">
							@csrf

							<div class="ui tiny rounded image">
					    	<img src="{{ asset_("storage/avatars/" . (auth()->user()->avatar ?? 'default.jpg')) }}">
					    	<input type="hidden" name="type" value="support" class="none">
							  <input type="hidden" name="comment_id" :value="replyTo.commentId" class="d-none">
					    </div>
					    	
					    <div class="content pl-1">
								<div class="ui tiny blue basic label rounded-corner mb-1-hf capitalize" v-if="replyTo.userName !== null">
									@{{ replyTo.userName }}
									<i class="delete icon" @click="resetReplyTo"></i>
								</div>

								<textarea rows="5" name="comment" placeholder="{{ __('Your comment') }} ..."></textarea>

								<button type="submit" class="ui tiny yellow circular button right floated mt-1-hf">{{ __('Submit') }}</button>
							</div>

						</form>

						@else

						<div class="ui fluid blue shadowless borderless message circular-corner">
							{!! __(':sign_in to post a comment', ['sign_in' => '<a href="'.route('login', ['redirect' => url()->current()]).'">'.__("Login").'</a>']) !!}
						</div>

						@endauth
					</div>

				</div>

				<!-- Reviews -->
				<div class="sixteen wide column reviews">
					@if(session('review_response'))
					<div class="ui fluid shadowless borderless green message circular-corner">
						{{ request()->session()->pull('review_response', 'default') }}
					</div>
					@elseif(!$reviews->count())
					<div class="ui fluid shadowless large rounded-corner message">
						{{ __('This item has not received any review yet') }}.
					</div>
					@endif

					@if($reviews->count())
					<div class="ui divided unstackable items">
						@foreach($reviews as $review)
						<div class="item">
							<div class="ui tiny circular image">
								<img src="{{ asset_("storage/avatars/{$review->avatar}") }}">
							</div>

							<div class="content description body">
								<h3>
									{{ $review->name ?? $review->alias_name ?? $review->fullname }} 
									<span class="floated right">{{ $review->created_at->diffForHumans() }}</span>
								</h3>

								<h4 class="mt-1-hf">
									<span class="ui star rating disabled ml-0 floated right" data-rating="{{ $review->rating }}" data-max-rating="5"></span>
								</h4>

								{{ nl2br($review->content) }}
							</div>
						</div>
						@endforeach
					</div>
					@endif

					@auth
					<!-- IF PURCHASED AND NOT REVIEWED YET -->
					@if(!$product->reviewed && $product->purchased)
					
					<div class="ui items borderless">
						<form class="item ui form" method="post" action="{{ item_url($product) }}">
							@csrf
	
							<div class="ui tiny rounded image">
								<img src="{{ asset_("storage/avatars/" . (auth()->user()->avatar ?? 'default.jpg')) }}">
								<input type="hidden" name="type" value="reviews" class="none">
							</div>
								
							<div class="content pl-1">
								<span class="ui star rating active mb-1-hf" data-max-rating="5"></span>
								<input type="hidden" name="rating" class="d-none">
											
								<textarea rows="5" name="review" placeholder="Your review ..."></textarea>
	
								<button type="submit" class="ui tiny yellow circular button right floated mt-1-hf uppercase">{{ __('Submit') }}</button>
							</div>
						</form>
					</div>
					
					@endif
					@else
				
					<div class="ui fluid blue shadowless borderless message circular-corner">
						{!! __(':sign_in to review this item', ['sign_in' => '<a href="'.route('login', ['redirect' => url()->current()]).'">'.__("Login").'</a>']) !!}
					</div>

					@endauth
				</div>
					
				<!-- FAQ -->
				<div class="sixteen wide column faq">
					@if($product->faq)
					<div class="ui divided list">
						@foreach($product->faq as $qa)
						<div class="item p-1">
							<div class="header mb-1">{{ __('Q') }}. {{ $qa->question }}</div>
							<strong>{{ __('A') }}.</strong> {{ $qa->answer }}
						</div>
						@endforeach
					</div>
					@else
					<div class="ui fluid shadowless borderless message large rounded-corner">
						{{ __('No Questions / Answers added yet.') }}
					</div>
					@endif
				</div>

				@if(isFolderProcess() && $product->file_name)

				<!-- FILES -->
				<div class="sixteen wide column files">
					<div id="files-list" v-if="folderContent !== null">
						<div class="item" v-for="file in folderContent">
							@{{ file.name }}
						</div>
					</div>
				</div>

				@endif
			</div>
		</div>
	
		<div class="column mx-auto r-side">
			<div class="ui fluid card item-details">
				<div class="content title">
					<div class="ui header">{{ __('Item details') }}</div>
				</div>
				<div class="content borderless">
					<table class="ui unstackable large table basic">
						@if($product->version)
						<tr>
							<td><strong>{{ __('Version') }}</strong></td>
							<td>{{ $product->version }}</td>
						</tr>
						@endif

						@if($product->category)
						<tr>
							<td><strong>{{ __('Category') }}</strong></td>
							<td>{{ $product->category }}</td>
						</tr>
						@endif

						@if($product->release_date)
						<tr>
							<td><strong>{{ __('Release date') }}</strong></td>
							<td>{{ $product->release_date }}</td>
						</tr>
						@endif
						
						@if($product->last_update)
						<tr>
							<td><strong>{{ __('Latest update') }}</strong></td>
							<td>{{ $product->last_update }}</td>
						</tr>
						@endif

						@if($product->included_files)
						<tr>
							<td><strong>{{ __('Included files') }}</strong></td>
							<td>{{ $product->included_files }}</td>
						</tr>
						@endif

						@if($product->compatible_browsers)
						<tr>
							<td><strong>{{ __('Compatible browsers') }}</strong></td>
							<td>{{ $product->compatible_browsers }}</td>
						</tr>
						@endif

						<tr>
							<td><strong>{{ __('Comments') }}</strong></td>
							<td>{{ $product->comments_count }}</td>
						</tr>

						@if($product->rating)
						<tr>
							<td><strong>{{ __('Rating') }}</strong></td>
							<td><div class="image rating floated right">{!! item_rating($product->rating) !!}</div></td>
						</tr>
						@endif

						<tr>
							<td><strong>{{ __('Sales') }}</strong></td>
							<td>{{ $product->sales }}</td>
						</tr>

						@if($product->software)
						<tr>
							<td><strong>{{ __('Software') }}</strong></td>
							<td>{{ $product->software }}</td>
						</tr>
						@endif

						@if($product->country ?? null)
						<tr>
							<td><strong>{{ __('Country') }}</strong></td>
							<td>{{ $product->country }}</td>
						</tr>
						@endif

						@if($product->city ?? null)
						<tr>
							<td><strong>{{ __('City') }}</strong></td>
							<td>{{ $product->city }}</td>
						</tr>
						@endif

						@if($product->authors)
						<tr>
							<td><strong>{{ __('Authors') }}</strong></td>
							<td>{!! $product->authors !!}</td>
						</tr>
						@endif

						@if($product->bpm)
						<tr>
							<td><strong>{{ __('BPM') }}</strong></td>
							<td>{{ $product->bpm }}</td>
						</tr>
						@endif

						@if($product->bit_rate)
						<tr>
							<td><strong>{{ __('Bit rate') }}</strong></td>
							<td>{{ $product->bit_rate }}</td>
						</tr>
						@endif

						@if($product->label)
						<tr>
							<td><strong>{{ __('Label') }}</strong></td>
							<td>{{ $product->label }}</td>
						</tr>
						@endif

						@if($product->formats)
						<tr>
							<td><strong>{{ __('Formats') }}</strong></td>
							<td>{{ $product->formats }}</td>
						</tr>
						@endif

						@if($product->words)
						<tr>
							<td><strong>{{ __('Words') }}</strong></td>
							<td>{{ $product->words }}</td>
						</tr>
						@endif

						@if($product->language)
						<tr>
							<td><strong>{{ __('Language') }}</strong></td>
							<td>{{ $product->language }}</td>
						</tr>
						@endif

						@if($product->pages)
						<tr>
							<td><strong>{{ __('Pages') }}</strong></td>
							<td>{{ $product->pages }}</td>
						</tr>
						@endif

						@if($product->database)
						<tr>
							<td><strong>{{ __('Database') }}</strong></td>
							<td>{{ $product->database }}</td>
						</tr>
						@endif

						@if(!is_null($product->high_resolution))
						<tr>
							<td><strong>{{ __('High resolution') }}</strong></td>
							<td>{{ $product->high_resolution ? __('Yes') : __('No') }}</td>
						</tr>
						@endif

						@foreach($product->additional_fields ?? [] as $additional_field)
						<tr>
							<td><strong>{{ __($additional_field->_name_) }}</strong></td>
							<td>{!! $additional_field->_value_ !!}</td>
						</tr>
						@endforeach
					</table>
				</div>
			</div>

			@if($product->tags)
			<div class="ui fluid card tags">
				<div class="content">
					<div class="ui header">{{ __('Item tags') }}</div>
				</div>
				<div class="content borderless">
					<div class="ui labels">
						@foreach($product->tags as $tag)
						<a href="{{ route('home.products.q', ['tags' => $tag]) }}" class="ui circular large basic label">{{ $tag }}</a>
						@endforeach
					</div>
				</div>
			</div>
			@endif

			<div class="ui hidden divider"></div>

			<div class="ui fluid card share-on">
				<div class="content borderless">
					<div class="ui large buttons">
						<button class="ui circular basic icon button" onclick="window.open('{{ share_link('pinterest', $product) }}', 'Pinterest', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="pinterest icon"></i>
						</button>	
						<button class="ui circular basic icon button" onclick="window.open('{{ share_link('twitter') }}', 'Twitter', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="twitter icon"></i>
						</button>	
						<button class="ui circular basic icon button" onclick="window.open('{{ share_link('facebook') }}', 'Facebook', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="facebook icon"></i>
						</button>
						<button class="ui circular basic icon button" onclick="window.open('{{ share_link('tumblr') }}', 'tumblr', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="tumblr icon"></i>
						</button>
						<button class="ui circular basic icon button" onclick="window.open('{{ share_link('vk') }}', 'VK', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="vk icon"></i>
						</button>
						<button class="ui circular basic icon button" onclick="window.open('{{ share_link('linkedin') }}', 'Linkedin', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="linkedin icon"></i>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="ui modal" id="screenshots" >
	  <div class="image content p-0" v-if="activeScreenshot">
			<div class="left">
				<button class="ui icon button" type="button" @click="slideScreenhots('prev')">
				  <i class="angle big left icon m-0"></i>
				</button>
			</div>

	    <img class="image" :src="activeScreenshot">

	    <div class="right">
		    <button class="ui icon button" type="button" @click="slideScreenhots('next')">
				  <i class="angle big right icon m-0"></i>
				</button>
	    </div>
	  </div>
	</div>

	<div class="ui modal" id="reactions">
		<div class="header">
			<div class="wrapper">
				<a v-for="reaction, name in usersReactions" :class="['name ' + name, usersReaction === name ? 'active' : '']" :data-reaction="name">
					<span class="label">@{{ name }}</span>
					<span class="count">@{{ reaction.length }}</span>
				</a>
			</div>
		</div>
		<div class="content">
			<div class="wrapper">
				<div v-for="reaction, name in usersReactions" :class="['users ' + name, usersReaction === name ? 'active' : '']">
					<div class="user" v-for="user in reaction" :title="user.user_name">
						<span class="avatar"><img :src="'/storage/avatars/' + user.user_avatar" class="ui avatar image"></span>
						<span class="text">@{{ user.user_name }}</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@if($similar_products->count())
<div class="row" id="similar-items">
	<div class="border top"></div>

	<div class="header">
		<div>{{ __('Similar items') }}</div>
	</div>

	<div class="ui five doubling cards @if(config('app.masonry_layout')) is_masonry @endif">
		@cards('item-card', $similar_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1])
	</div>
</div>
@endif
@endsection