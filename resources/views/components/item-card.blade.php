@if($item->type_is('-'))

<div class="ui card product {{ $item->type }} type-- {{ out_of_stock($item, true) }} {{ has_promo_time($item, true) }} {{ has_promo_price($item, true) }}">
	
	@if(item_has_badge($item))
	<div class="ui left corner large label {{ item_has_badge($item) }}" title="{{ __(mb_ucfirst(item_has_badge($item))) }}"><i class="tag rotated icon"></i></div>
	@endif
	
	@if($item->has_preview('video'))
	<div class="content cover preview">
		{!! preview($item) !!}
	</div>
	@else
	<a class="content cover" href="{{ item_url($item) }}" title="{{ $item->name }}">
		<img src="{{ asset_("storage/covers/{$item->cover}") }}" alt="cover">
	</a>
	@endif
	
	<div class="content title {{ $item->for_subscriptions ? 'padded' : '' }}">
		<div>
			<a href="{{ item_url($item) }}" title="{{ $item->name }}">
		    {{ $item->name }}
			</a>

			@if($category ?? null)
			<span class="category">
	    {{ rand_subcategory($item->subcategories, $item->category_name) }}
    	</span>
    	@endif

    	@if(($rating ?? null))
    	<div class="image rating mt-1">{!!  item_rating($item->rating) !!}</div>
		@endif

		@if(!$item->for_subscriptions && ($sales ?? null))
		<div class="sales mt-1">{{ __(':count Sales', ['count' => $item->sales]) }}</div>
		@endif
		
		@if($item->promotional_time && $item->promotional_price && !$item->for_subscriptions)
		<div data-json="{{ $item->promotional_time }}" class="promo-count mt-1-hf">{{ __('Ends in') }} <span></span></div>
	    @endif
		</div>
	</div>

	@if(!$item->for_subscriptions)
	<div class="content bottom p-1-hf">
		@if(!out_of_stock($item))
		<div class="price-wrapper {{ $item->promotional_price ? 'has-promo' : '' }}">
			<div class="price mr-0 {{ !$item->price ? 'free' : '' }}">
				{{ price($item->price) }}
			</div>

			@if($item->promotional_price)
			<div class="promo-price">{{ price($item->promotional_price) }}</div>
			@endif
		</div>

		<div class="action" @click="addToCartAsync({{ json_encode($item) }}, $event)">
			<img src="{{ asset_('assets/images/cart-1.png') }}" class="ui image">
		</div>

		<div class="action like" :class="{ active: itemInCollection({{ $item->id }}) }" @click="collectionToggleItem($event, {{ $item->id }})">
			<img src="{{ asset_('assets/images/heart-0.png') }}" class="ui heart outline image">
			<img src="{{ asset_('assets/images/heart-1.png') }}" class="ui heart image">
		</div>
		@else
		<div class="out-of-stock">{{ __('Out of stock') }}</div>
		@endif
	</div>
	@endif
</div>

@elseif($item->type_is('audio'))

<div class="ui card product {{ $item->type }} type-audio {{ out_of_stock($item, true) }} {{ has_promo_time($item, true) }} {{ has_promo_price($item, true) }}">
	
	@if(item_has_badge($item))
	<div class="ui left corner large label {{ item_has_badge($item) }}" title="{{ __(mb_ucfirst(item_has_badge($item))) }}"><i class="tag rotated icon"></i></div>
	@endif
	
	<div class="content top">
		<a class="cover" href="{{ item_url($item) }}" title="{{ $item->name }}">
			<img src="{{ asset_("storage/covers/{$item->cover}") }}" alt="cover">
		</a>

		@if(item_has_info($item, ['bpm', 'label', 'authors']))
		<div class="info">
			@if($item->authors)
			<span class="author">{{ trim(explode(',', $item->authors)[0]) }}</span>
			@endif

			@if($item->bpm)
			<span><i class="circle outline icon"></i>{{ __(':count BPM', ['count' => $item->bpm]) }}</span>
			@endif
			
			@if($item->bpm)
			<span><i class="circle outline icon"></i>{{ __('Bit rate :count', ['count' => $item->bit_rate]) }}</span>
			@endif

			@if($item->label)
			<span><i class="circle outline icon"></i>{{ $item->label }}</span>
			@endif
		</div>
		@endif
	</div>

	<div class="content p-0 mb-1">
		{!! preview($item) !!}
	</div>
	
	<div class="content title {{ $item->for_subscriptions ? 'padded' : '' }}">
		<div>
			<a href="{{ item_url($item) }}" title="{{ $item->name }}">
		    {{ $item->name }}
			</a>

			@if($category ?? null)
			<span class="category">
	    {{ rand_subcategory($item->subcategories, $item->category_name) }}
    	</span>
    	@endif

    	@if(($rating ?? null))
    	<div class="image rating mt-1">{!!  item_rating($item->rating) !!}</div>
			@endif

			@if(!$item->for_subscriptions && ($sales ?? null))
			<div class="sales mt-1">{{ __(':count Sales', ['count' => $item->sales]) }}</div>
			@endif
		</div>
		
		@if($item->promotional_time && $item->promotional_price  && !$item->for_subscriptions)
		<div data-json="{{ $item->promotional_time }}" class="promo-count mt-1-hf">{{ __('Ends in') }} <span></span></div>
	    @endif
	</div>

	@if(!$item->for_subscriptions)
	<div class="content bottom p-1-hf">
		@if(!out_of_stock($item))
		<div class="price-wrapper {{ $item->promotional_price ? 'has-promo' : '' }}">
			<div class="price mr-0 {{ !$item->price ? 'free' : '' }}">
				{{ price($item->price) }}
			</div>

			@if($item->promotional_price)
			<div class="promo-price">{{ price($item->promotional_price) }}</div>
			@endif
		</div>

		<div class="action" @click="addToCartAsync({{ json_encode($item) }}, $event)">
			<img src="{{ asset_('assets/images/cart-1.png') }}" class="ui image">
		</div>

		<div class="action like" :class="{ active: itemInCollection({{ $item->id }}) }" @click="collectionToggleItem($event, {{ $item->id }})">
			<img src="{{ asset_('assets/images/heart-0.png') }}" class="ui heart outline image">
			<img src="{{ asset_('assets/images/heart-1.png') }}" class="ui heart image">
		</div>
		@else
		<div class="out-of-stock">{{ __('Out of stock') }}</div>
		@endif
	</div>
	@endif
</div>

@elseif($item->type_is('ebook'))

<div class="ui card product {{ $item->type }} type-ebook {{ out_of_stock($item, true) }} {{ has_promo_time($item, true) }} {{ has_promo_price($item, true) }}">
    
    @if(item_has_badge($item))
	<div class="ui left corner large label {{ item_has_badge($item) }}" title="{{ __(mb_ucfirst(item_has_badge($item))) }}"><i class="tag rotated icon"></i></div>
	@endif
	
	<div class="content top">
		<a class="cover" href="{{ item_url($item) }}" title="{{ $item->name }}">
			<img src="{{ asset_("storage/covers/{$item->cover}") }}" alt="cover">
		</a>

		@if(item_has_info($item, ['authors', 'pages', 'words', 'language']))
		<div class="info">
			@if($item->authors)
			<span class="author">{{ trim(explode(',', $item->authors)[0]) }}</span>
			@endif

			@if($item->pages)
			<span><i class="circle outline icon"></i>{{ __(':count pages', ['count' => $item->pages]) }}</span>
			@endif

			@if($item->words)
			<span><i class="circle outline icon"></i>{{ __(':count words', ['count' => $item->words]) }}</span>
			@endif

			@if($item->language)
			<span><i class="circle outline icon"></i>{{ __(mb_ucfirst($item->language)) }}</span>
			@endif
		</div>
		@endif
	</div>

	<div class="content title {{ $item->for_subscriptions ? 'padded' : '' }}">
		<div>
			<a href="{{ item_url($item) }}" title="{{ $item->name }}">
		    {{ $item->name }}
			</a>

			@if($category ?? null)
			<span class="category">
	    {{ rand_subcategory($item->subcategories, $item->category_name) }}
    	</span>
    	@endif

    	@if(($rating ?? null))
    	<div class="image rating mt-1">{!!  item_rating($item->rating) !!}</div>
			@endif

			@if(!$item->for_subscriptions && ($sales ?? null))
			<div class="sales mt-1">{{ __(':count Sales', ['count' => $item->sales]) }}</div>
			@endif
		</div>
		
		@if($item->promotional_time && $item->promotional_price && !$item->for_subscriptions)
		<div data-json="{{ $item->promotional_time }}" class="promo-count mt-1-hf">{{ __('Ends in') }} <span></span></div>
	    @endif
	</div>

	@if(!$item->for_subscriptions)
	<div class="content bottom p-1-hf">
		@if(!out_of_stock($item))
		<div class="price-wrapper {{ $item->promotional_price ? 'has-promo' : '' }}">
			<div class="price mr-0 {{ !$item->price ? 'free' : '' }}">
				{{ price($item->price) }}
			</div>

			@if($item->promotional_price)
			<div class="promo-price">{{ price($item->promotional_price) }}</div>
			@endif
		</div>

		<div class="action" @click="addToCartAsync({{ json_encode($item) }}, $event)">
			<img src="{{ asset_('assets/images/cart-1.png') }}" class="ui image">
		</div>

		<div class="action like" :class="{ active: itemInCollection({{ $item->id }}) }" @click="collectionToggleItem($event, {{ $item->id }})">
			<img src="{{ asset_('assets/images/heart-0.png') }}" class="ui heart outline image">
			<img src="{{ asset_('assets/images/heart-1.png') }}" class="ui heart image">
		</div>
		@else
		<div class="out-of-stock">{{ __('Out of stock') }}</div>
		@endif
	</div>
	@endif
</div>

@elseif($item->type_is('graphic'))

<div class="ui card product {{ $item->type }} type-graphic {{ out_of_stock($item, true) }} {{ has_promo_time($item, true) }} {{ has_promo_price($item, true) }}">
	
	@if(item_has_badge($item))
	<div class="ui left corner large label {{ item_has_badge($item) }}" title="{{ __(mb_ucfirst(item_has_badge($item))) }}"><i class="tag rotated icon"></i></div>
	@endif
	
	@if($item->has_preview('video'))
	<div class="content cover preview">
		{!! preview($item) !!}
	</div>
	@else
	<a class="content cover" href="{{ item_url($item) }}" title="{{ $item->name }}">
		<img src="{{ asset_("storage/covers/{$item->cover}") }}" alt="cover">
	</a>
	@endif
	
	<div class="content title {{ $item->for_subscriptions ? 'padded' : '' }}">
		<div>
			<a href="{{ item_url($item) }}" title="{{ $item->name }}">
		    {{ $item->name }}
			</a>

			@if($category ?? null)
			<span class="category">
	    {{ rand_subcategory($item->subcategories, $item->category_name) }}
    	</span>
    	@endif

    	@if(($rating ?? null))
    	<div class="image rating mt-1">{!!  item_rating($item->rating) !!}</div>
			@endif

			@if(!$item->for_subscriptions && ($sales ?? null))
			<div class="sales mt-1">{{ __(':count Sales', ['count' => $item->sales]) }}</div>
			@endif
		</div>
		
		@if($item->promotional_time && $item->promotional_price && !$item->for_subscriptions)
		<div data-json="{{ $item->promotional_time }}" class="promo-count mt-1-hf">{{ __('Ends in') }} <span></span></div>
	    @endif
	</div>

	@if(!$item->for_subscriptions)
	<div class="content bottom p-1-hf">
		@if(!out_of_stock($item))
		<div class="price-wrapper {{ $item->promotional_price ? 'has-promo' : '' }}">
			<div class="price mr-0 {{ !$item->price ? 'free' : '' }}">
				{{ price($item->price) }}
			</div>

			@if($item->promotional_price)
			<div class="promo-price">{{ price($item->promotional_price) }}</div>
			@endif
		</div>

		<div class="action" @click="addToCartAsync({{ json_encode($item) }}, $event)">
			<img src="{{ asset_('assets/images/cart-1.png') }}" class="ui image">
		</div>

		<div class="action like" :class="{ active: itemInCollection({{ $item->id }}) }" @click="collectionToggleItem($event, {{ $item->id }})">
			<img src="{{ asset_('assets/images/heart-0.png') }}" class="ui heart outline image">
			<img src="{{ asset_('assets/images/heart-1.png') }}" class="ui heart image">
		</div>
		@else
		<div class="out-of-stock">{{ __('Out of stock') }}</div>
		@endif
	</div>
	@endif
</div>

@elseif($item->type_is('video'))

<div class="ui card product {{ $item->type }} type-video {{ out_of_stock($item, true) }} {{ has_promo_time($item, true) }} {{ has_promo_price($item, true) }}">
	
	@if(item_has_badge($item))
	<div class="ui left corner large label {{ item_has_badge($item) }}" title="{{ __(mb_ucfirst(item_has_badge($item))) }}"><i class="tag rotated icon"></i></div>
	@endif
	
	@if($item->has_preview('video'))
	<div class="content cover preview">
		{!! preview($item) !!}
	</div>
	@else
	<a class="content cover" href="{{ item_url($item) }}" title="{{ $item->name }}">
		<img src="{{ asset_("storage/covers/{$item->cover}") }}" alt="cover">
	</a>
	@endif
	
	<div class="content title {{ $item->for_subscriptions ? 'padded' : '' }}">
		<div>
			<a href="{{ item_url($item) }}" title="{{ $item->name }}">
		    {{ $item->name }}
			</a>

			@if($category ?? null)
			<span class="category">
	    {{ rand_subcategory($item->subcategories, $item->category_name) }}
    	</span>
    	@endif

    	@if(($rating ?? null))
    	<div class="image rating mt-1">{!!  item_rating($item->rating) !!}</div>
			@endif

			@if(!$item->for_subscriptions && ($sales ?? null))
			<div class="sales mt-1">{{ __(':count Sales', ['count' => $item->sales]) }}</div>
			@endif
		</div>
		
		@if($item->promotional_time && $item->promotional_price && !$item->for_subscriptions)
		<div data-json="{{ $item->promotional_time }}" class="promo-count mt-1-hf">{{ __('Ends in') }} <span></span></div>
	    @endif
	</div>

	@if(!$item->for_subscriptions)
	<div class="content bottom p-1-hf">
		@if(!out_of_stock($item))
		<div class="price-wrapper {{ $item->promotional_price ? 'has-promo' : '' }}">
			<div class="price mr-0 {{ !$item->price ? 'free' : '' }}">
				{{ price($item->price) }}
			</div>

			@if($item->promotional_price)
			<div class="promo-price">{{ price($item->promotional_price) }}</div>
			@endif
		</div>

		<div class="action" @click="addToCartAsync({{ json_encode($item) }}, $event)">
			<img src="{{ asset_('assets/images/cart-1.png') }}" class="ui image">
		</div>

		<div class="action like" :class="{ active: itemInCollection({{ $item->id }}) }" @click="collectionToggleItem($event, {{ $item->id }})">
			<img src="{{ asset_('assets/images/heart-0.png') }}" class="ui heart outline image">
			<img src="{{ asset_('assets/images/heart-1.png') }}" class="ui heart image">
		</div>
		@else
		<div class="out-of-stock">{{ __('Out of stock') }}</div>
		@endif
	</div>
	@endif
</div>

@endif