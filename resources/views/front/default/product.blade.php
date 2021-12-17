{{-- DEFAULT TEMPLATE --}}

@extends('front.default.master')

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
    "lowPrice": "{{ number_format($product->price, 2) }}",
    "highPrice": "{{ number_format($product->price, 2) }}",
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
		license_id: {{ $product->license_id }},
		url: '{{ item_url($product) }}',
		price: {{ $product->price ?? '0' }},
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
	
<div class="ui two columns shadowless celled grid my-0 px-1" id="item">	

	<div class="row">
		<div class="column mx-auto l-side" vhidden>
			<div class="row menu">
				<div class="ui top secondary menu p-1">
				  <a class="item" id="item-r-side-toggler">{{ __('Buy') }}</a>
				  <a class="active item ml-0" data-tab="details">{{ __('Details') }}</a>
				  @if($product->hidden_content && ($valid_subscription || $product->purchased || $product->free))
				  <a class="item" data-tab="hidden-content">{{ __('Hidden content') }}</a>
				  @endif
				  @if($product->table_of_contents)
				  <a class="item" data-tab="table_of_contents">{{ __('Table of contents') }}</a>
				  @endif
				  <a class="item" data-tab="support">{{ __('Support') }}</a>
				  <a class="item" data-tab="reviews">{{ __('Reviews') }}</a>
				  <a class="item mr-0" data-tab="faq">{{ __('FAQ') }}</a>
				  @if(isFolderProcess())
				  <a class="item mr-0" data-tab="files" @click="getFolderContent">{{ __('Files') }}</a>
				  @endif
				</div>
			</div>

			<div class="row item">
				<div class="sixteen wide column details">
					<div class="row center aligned cover">
						<div class="title">
							{{ $product->name }}
						</div>

						@if(preg_match('/^video|graphic$/i', $product->type))
			  		    <div class="video">
							{!! preview($product) !!}
						</div>
						@else
						<div class="ui image w-100">
							<img src="{{ asset_("storage/covers/{$product->cover}") }}" class="type-{{ $product->type }} w-100">
						</div>
						@endif
					</div>

					@if($product->type_is('audio'))
					<div class="audio-container" data-src="{{ preview_link($product) }}" data-id="{{ $product->id }}">
						<div class="player">
							<div class="timeline"><div class="wave"></div></div>

							<div class="actions">
								<button class="ui link circular play button visible mr-1-hf">
									{{ __('Play') }}
								</button>
								<button class="ui link circular pause button mr-1-hf">
									{{ __('Pause') }}
								</button>
								<button class="ui link circular stop button mr-1-hf">
									{{ __('Stop') }}
								</button>
								<a class="ui circular blue large button" href="{{ preview_link($product) }}" download="{{ $product->slug }}.mp3">
									{{ __('Download preview') }}
								</a>
							</div>

							<div class="duration">00:00</div>
						</div>
					</div>
					@endif

					@if($product->for_subscriptions) 
					<div class="ui rounded-corner fluid blue bold message d-table mx-auto">
						{{ __('This item is available via subscriptions only.') }}
					</div>
					@endif

					@if(out_of_stock($product))
					<div class="ui rounded-corner fluid negative bold message d-table mx-auto">
						{{ __('This item is out of stock.') }}
					</div>
					@endif
                    
						<div class="mt-1 d-flex mx-auto justify-content-center">
    					@if(!$product->is_dir && ($product->free || $product->purchased || auth_is_admin()) || $valid_subscription)
    					<button class="ui button rounded-corner download large mx-1-hf" @click="downloadItem({{ $product->id }}, '#download')">{{ __('Download') }} 
    						@if($product->remaining_downloads)
    						<sup>({{ $product->remaining_downloads }})</sup>
    						@endif
    					</button>
    					@endif
    					
    					@if($product->preview_url)
    					<a class="ui button rounded-corner pink large mx-1-hf" target="_blank" href="{{ $product->preview_url }}">{{ __('Preview') }}</a>
    					@endif
					</div>

					@if($product->screenshots)
					<div class="ui fluid card mt-3">
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
					<div class="ui fluid card overview">
						<div class="content body">
							{!! $product->overview !!}
						</div>
					</div>
					@endif
				</div>

				<!-- Hidden Content -->
				@if($product->hidden_content && ($valid_subscription || $product->purchased || $product->free))
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

					<div class="ui fluid shadowless green basic large rounded-corner message">
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
								<div class="item parent">
									<div class="ui tiny circular image">
										<img src="{{ asset_("storage/avatars/".$comment->avatar) }}">
									</div>

									<div class="content description body">
										<h3>
											{{ $comment->name ?? $comment->alias_name ?? $comment->fullname }} 
											<span class="floated right">{{ $comment->created_at->diffForHumans() }}</span>
										</h3>

										{!! nl2br($comment->body) !!}
										
										<div class="ui hidden divider mt-0"></div>

										<div class="ui form right floated">
											<button class="ui mini circular red button mr-0 uppercase"
															@click="setReplyTo('{{ $comment->name ?? $comment->alias_name ?? $comment->fullname }}', {{ $comment->id }})">
												{{ __('Reply') }}
											</button>
										</div> 
									</div>
								</div>

								@foreach($comment->children as $child)
								<div class="item children">
									<div class="ui tiny circular image">
										<img src="{{ asset_("storage/avatars/".$child->avatar) }}">
									</div>

									<div class="content description body">
										<h3>
											{{ $child->name ?? $child->alias_name ?? $child->fullname }} 
											<span class="floated right">{{ $child->created_at->diffForHumans() }}</span>
										</h3>

										{!! nl2br($child->body) !!}

										<div class="ui hidden divider mt-0"></div>

										<div class="ui form right floated">
											<button class="ui mini circular red button mr-0 uppercase" 
															@click="setReplyTo('{{ $child->name ?? $child->alias_name ?? $child->fullname }}', {{ $comment->id }})">
												{{ __('Reply') }}
											</button>
										</div>
									</div>
								</div>
								@endforeach
							</div>
							@endforeach
						</div>
						
						@auth

						<form class="item ui form" method="post" action="{{ item_url($product) }}">
							@csrf

							<div class="ui tiny avatar image">
					    	<img src="{{ asset_("storage/avatars/" . (auth()->user()->avatar ?? 'default.jpg')) }}">
					    	<input type="hidden" name="type" value="support" class="none">
							  <input type="hidden" name="comment_id" :value="replyTo.commentId" class="d-none">
					    </div>
					    	
					    <div class="content pl-1">
								<div class="ui tiny blue basic label mb-1-hf rounded-corner capitalize" v-if="replyTo.userName !== null">
									@{{ replyTo.userName }}
									<i class="delete icon" @click="resetReplyTo"></i>
								</div>

								<textarea rows="5" name="comment" placeholder="{{ __('Your comment') }} ..."></textarea>

								<button type="submit" class="ui tiny yellow circular button right floated mt-1-hf">{{ __('Submit') }}</button>
							</div>

						</form>

						@else

						<div class="ui fluid blue shadowless large rounded-corner message">
							{!! __(':sign_in to post a comment', ['sign_in' => '<a href="'.route('login', ['redirect' => url()->current()]).'">'.__("Login").'</a>']) !!}
						</div>

						@endauth
					</div>

				</div>

				<!-- Reviews -->
				<div class="sixteen wide column reviews">
					@if(session('review_response'))
					<div class="ui fluid shadowless  green basic large rounded-corner message">
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
								<img src="{{ asset_("storage/avatars/".$review->avatar) }}">
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
					
					<div class="ui items ">
						<form class="item ui form" method="post" action="{{ item_url($product) }}">
							@csrf
	
							<div class="ui tiny avatar image">
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
				
					<div class="ui fluid blue shadowless large rounded-corner message">
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
					<div class="ui fluid shadowless large rounded-corner message">
						{{ __('No Questions / Answers added yet.') }}
					</div>
					@endif
				</div>
	
				@if(isFolderProcess())

				<!-- FILES -->
				<div class="sixteen wide column files">
					<div id="files-list" v-if="folderContent !== null">
						<div class="item" v-for="file in folderContent">
							<div class="file-icon">
								<i class="big icon mx-0" :class="getFolderFileIcon(file)"></i>
							</div>
							<div class="file-name">
								@{{ file.name }}
							</div>
						</div>
					</div>
				</div>

				@endif

			</div>
		</div>
	
		<div class="column mx-auto r-side" vhidden>
			@if(!$product->is_dir && ($product->free || $product->purchased || auth_is_admin()) || $valid_subscription)
				<form action="{{ route('home.download') }}" class="d-none" id="download" method="post">
					@csrf
					<input type="hidden" name="itemId" v-model="itemId">
				</form>
			@endif

			@if(!$product->for_subscriptions)
			<div class="ui fluid card paid rounded-corner mt-0">
				<div class="content price">
			    <div class="header uppercase" :class="{itemHasPromo}">
			    	{{ __('Price') }}
			    	<div class="right floated">
			    		@if($product->free)
			    		<span class="reduced">{{ __('Free') }}</span>
			    		@else
			    		<span class="reduced" v-if="itemHasPromo">@{{ price(itemPrices[licenseId]['promo_price'], 1, 1) }}</span>
			    		<span class="normal" v-if="!itemIsFree()">@{{ price(itemPrices[licenseId]['price'], 1, 1) }}</span>
			    		<span class="normal" v-else>@{{ __('Free') }}</span>
			    		@endif
			    	</div>
			    </div>

			    @if(!is_null($product->minimum_price) && config('pay_what_you_want.enabled') && config('pay_what_you_want.for.products'))
			    <div class="content minimum-item-price ui large form mt-1">
			    	<div class="field">
			    		<label class="ml-1-hf">{{ __('Custom price') }}</label>
			    		<input class="circular-corner" type="number" step="0.0001" v-model="customItemPrice" placeholder="{{ __('Minimum :price', ['price' => price($product->minimum_price, false)]) }}">
			    	</div>
			    </div>
			    @endif

			    @if($product->free && $product->free_time && !out_of_stock($product))
			    <div class="card promo mt-0">
						<div class="promo-count" data-json="{{ $product->free_time }}">{{ __('Ends in') }} <span></span></div>
					</div>
			    @else
			    <div class="card promo mt-0" v-if="itemHasPromo">
						<div class="promo-count" data-json="{{ collect($product_prices)->where('promotional_time', '!=', null)->first()['promotional_time'] ?? null }}">{{ __('Ends in') }} <span></span></div>
					</div>
					@endif
			  </div>

			  @if(!$product->free && !out_of_stock($product))
			  <div class="content {{ count($product_prices) <= 1 ? 'd-none disabled' : '' }}">
				  <div class="ui fluid large floating circular button dropdown selection licenses">
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
			  </div>

			  <div class="content">
			  	<div class="buttons">
			  		@if(!$product->free)
			  		<button class="ui big circular blue button" v-if="!itemIsFree()" @click="buyNow(product, $event)">{{ __('Buy Now') }}</button>
				  	@endif
				  	<button class="ui big circular yellow button" @click="addToCartAsync(product, $event)">{{ __('Add To Cart') }}</button>
			  	</div>
			  </div>
			  @endif
			</div>
			@endif

			<table class="ui unstackable table basic item-info">
				@if($product->version)
				<tr>
					<td>{{ __('Version') }}</td>
					<td>{{ $product->version }}</td>
				</tr>
				@endif

				@if($product->category)
				<tr>
					<td>{{ __('Category') }}</td>
					<td>{{ $product->category }}</td>
				</tr>
				@endif

				@if($product->release_date)
				<tr>
					<td>{{ __('Release date') }}</td>
					<td>{{ $product->release_date }}</td>
				</tr>
				@endif
				
				@if($product->last_update)
				<tr>
					<td>{{ __('Latest update') }}</td>
					<td>{{ $product->last_update }}</td>
				</tr>
				@endif

				@if($product->included_files)
				<tr>
					<td>{{ __('Included files') }}</td>
					<td>{{ $product->included_files }}</td>
				</tr>
				@endif

				@if($product->authors)
				<tr>
					<td>{{ __('Authors') }}</td>
					<td>{{ $product->authors }}</td>
				</tr>
				@endif

				@if($product->pages)
				<tr>
					<td>{{ __('Pages') }}</td>
					<td>{{ $product->pages }}</td>
				</tr>
				@endif

				@if($product->words)
				<tr>
					<td>{{ __('Words') }}</td>
					<td>{{ $product->words }}</td>
				</tr>
				@endif

				@if($product->language)
				<tr>
					<td>{{ __('Language') }}</td>
					<td>{{ $product->language }}</td>
				</tr>
				@endif

				@if($product->bpm)
				<tr>
					<td>{{ __('BPM') }}</td>
					<td>{{ $product->bpm }}</td>
				</tr>
				@endif

				@if($product->bit_rate)
				<tr>
					<td>{{ __('Bit rate') }}</td>
					<td>{{ $product->bit_rate }}</td>
				</tr>
				@endif

				@if($product->label)
				<tr>
					<td>{{ __('Label') }}</td>
					<td>{{ $product->label }}</td>
				</tr>
				@endif

				@if($product->tags)
				<tr>
					<td>{{ __('Tags') }}</td>
					<td>
						@foreach($product->tags ?? [] as $tag)
						<a href="/items/search?tags={{ $tag }}">{{ $tag }}</a>
						@endforeach
					</td>
				</tr>
				@endif

				@if($product->compatible_browsers)
				<tr>
					<td>{{ __('Compatible browsers') }}</td>
					<td>{{ $product->compatible_browsers }}</td>
				</tr>
				@endif

				<tr>
					<td>{{ __('Comments') }}</td>
					<td>{{ $product->comments_count }}</td>
				</tr>

				@if($product->rating)
				<tr>
					<td>{{ __('Rating') }}</td>
					<td><div class="image rating">{!!  item_rating($product->rating) !!}</div></td>
				</tr>
				@endif

				@if($product->high_resolution)
				<tr>
					<td>{{ __('High resolution') }}</td>
					<td>{{ $product->high_resolution ? 'Yes' : 'No' }}</td>
				</tr>
				@endif

				<tr>
					<td>{{ __('Sales') }}</td>
					<td>{{ $product->sales }}</td>
				</tr>
			</table>

			<div class="ui fluid card share-on">
				<div class="content pb-0">
					<h3 class="ui header py-1 px-0">{{ __('Share on') }}</h3>
				</div>
				<div class="content px-0 ">
					<div class="buttons">
						<button class="ui icon button" onclick="window.open('{{ share_link('pinterest', $product) }}', 'Pinterest', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="pinterest icon"></i>
						</button>	
						<button class="ui icon button" onclick="window.open('{{ share_link('twitter') }}', 'Twitter', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="twitter icon"></i>
						</button>	
						<button class="ui icon button" onclick="window.open('{{ share_link('facebook') }}', 'Facebook', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="facebook icon"></i>
						</button>
						<button class="ui icon button" onclick="window.open('{{ share_link('tumblr') }}', 'tumblr', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="tumblr icon"></i>
						</button>
						<button class="ui icon button" onclick="window.open('{{ share_link('vk') }}', 'VK', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						  <i class="vk icon"></i>
						</button>
						<button class="ui icon button" onclick="window.open('{{ share_link('linkedin') }}', 'Linkedin', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
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
</div>

@if($similar_products->count())
<div class="row w-100" id="similar-items">
	<div class="header">
		<div>{{ __('Similar items') }}</div>
	</div>

	<div class="ui five doubling cards @if(config('app.masonry_layout')) is_masonry @endif">
		@cards('item-card', $similar_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1, 'home' => 0])
	</div>
</div>
@endif

@endsection