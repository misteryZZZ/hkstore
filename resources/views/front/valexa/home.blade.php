@extends(view_path('master'))

@section('additional_head_tags')
<style>
	@if(config('app.top_cover_color'))
	#top-search:before {
		background: {{ config('app.top_cover_color')  }}	
	}
	@endif
	
	@if(config('app.top_cover'))
	#top-search {
		background-image: url('{{ asset_('storage/images/'.config('app.top_cover')) }}')
	}
	@endif
</style>

<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "WebSite",
	  "name": "{{ $meta_data->title }}",
	  "url": "{{ $meta_data->url }}",
	  "image": "{{ $meta_data->image }}",
	  "keywords": "{{ config('app.keywords') }}"
}
</script>

<script type="application/javascript">
	'use strict';
	window.props['products'] = @json($products, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
</script>
@endsection

@section('top-search')
<div class="row" id="top-search">
	<div  class="ui bottom attached basic segment borderless shadowless">
		<div class="ui middle aligned grid m-0">
			<div class="row">
				<div class="column center aligned">
					
					@if(config('app.search_header'))
					<h1>{{ config('app.search_header') }}</h1>
					<br>
					@endif
					
					<form class="ui huge form fluid search-form" id="live-search" method="get" action="{{ route('home.products.q') }}">
						<div class="ui icon input fluid">
						  <input type="text" name="q" placeholder="{{ __('Search') }}...">
						  <i class="search link icon"></i>
						</div>
						<div class="products" vhidden>
							<a :href="'/item/'+ item.id + '/' + item.slug" v-for="item of liveSearchItems" class="item">
								@{{ item.name }}
							</a>
						</div>
			    </form>
				</div>
			</div>
		</div>
	</div>

	<div class="border bottom"></div>
</div>

@if(config('app.users_notif'))
<div id="users-notif" class= mb-2" v-if="usersNotifRead != '{{ config('app.users_notif') }}'" v-cloak>
	<div class="notif"><span class="@if(mb_strlen(config('app.users_notif')) > 100) marquee @endif">{{ config('app.users_notif') }}</span></div>
	<i class="close circular icon mx-0" @click="markUsersNotifAsRead"></i>
</div>
@endif
@endsection


@section('body')
	
	{!! place_ad('ad_728x90') !!}
	
	<div class="row home-items">
		
		<!--  FEATURED PRODUCTS -->
		@if($featured_products->count())
		<div class="wrapper featured">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu featured">
					<div class="item">
						{{ __('Featured items') }}
					</div>

					<div class="right menu">
						<a href="{{ route('home.products.filter', 'featured') }}" class="item">{{ __('Browse all') }}</a>
					</div>
				</div>
			</div>
			
			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.valexa.featured.items_per_line', '4')) }} doubling cards @if(config('app.masonry_layout')) is_masonry @endif px-1">					
					@cards('item-card', $featured_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1])
				</div>
			</div>
		</div>
		@endif

		
		<!--  NEWEST PRODUCTS -->
		@if($newest_products->count())
		<div class="wrapper newest">
			<div class="border top"></div>

			<div class="sixteen wide column mx-auto selection-title">
				<a class="header" href="{{ route('home.products.filter', 'newest') }}">{{ __('Our newest items') }}</a>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.valexa.newest.items_per_line', '10')) }} items">
					@foreach($newest_products as $newest_product)
					<a href="{{ item_url($newest_product) }}" class="item" style="background-image: url({{ asset_("storage/covers/{$newest_product->cover}") }})" data-detail="{{ json_encode($newest_product) }}">
					</a>
					@endforeach
				</div>
			</div>

			<div class="border bottom"></div>
		</div>
		@endif

		<!--  TRENDING PRODUCTS -->
		@if($trending_products->count())
		<div class="wrapper trending">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu trending">
					<div class="item">
						{{ __('Trending items') }}
					</div>

					<div class="right menu">
						<a href="{{ route('home.products.filter', 'trending') }}" class="item">{{ __('Browse all') }}</a>
					</div>
				</div>
			</div>
			
			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.valexa.trending.items_per_line', '4')) }} doubling cards @if(config('app.masonry_layout')) is_masonry @endif px-1">
					@cards('item-card', $trending_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1])
				</div>
			</div>
		</div>
		@endif
		
		
		<!--  FLASH PRODUCTS -->
		@if($flash_products->count())
		<div class="wrapper flash">
			<div class="border top"></div>

			<div class="sixteen wide column mx-auto selection-title">
				<a class="header" href="{{ route('home.products.filter', 'flash') }}">{{ __('Flash items') }}</a>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui unstackable items">
					@foreach($flash_products as $flash_product)
					<div class="item {{ out_of_stock($flash_product, true) }}">

						<a class="image" href="{{ item_url($flash_product) }}">
							<div style="background-image: url({{ asset_("storage/covers/{$flash_product->cover}") }})"></div>
						</a>
						<div class="content">
							@if(out_of_stock($flash_product))
							<div class="out-of-stock">{{ __('Out of stock') }}</div>
							@endif

							<div class="name" title="{!! $flash_product->name !!}">{!! shorten_str($flash_product->name, 35) !!}</div>
							<div class="price">
								<div class="price">{{ price($flash_product->price) }}</div>
								<div class="promo">{{ price($flash_product->promotional_price) }}</div>
							</div>
							<div class="actions">
								@if(!out_of_stock($flash_product))
								<div class="action" @click="addToCartAsync({{ json_encode($flash_product) }}, $event)"><i class="cart icon mx-0"></i></div>
								<div class="action like" @click="collectionToggleItem($event, {{ $flash_product->id }})">
									<i class="heart icon link mx-0" :class="{active: itemInCollection({{ $flash_product->id }})}"></i>
								</div>
								@endif
							</div>
						</div>
					</div>
					@endforeach
				</div>
			</div>

			<div class="border bottom"></div>
		</div>
		@endif


		<!--  FREE PRODUCTS -->
		@if($free_products->count())
		<div class="wrapper free">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu free">
					<div class="item">
						{{ __('Free items') }}
					</div>

					<div class="right menu">
						<a href="{{ route('home.products.filter', 'free') }}" class="item">{{ __('Browse all') }}</a>
					</div>
				</div>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.valexa.free.items_per_line', '4')) }} doubling cards @if(config('app.masonry_layout')) is_masonry @endif px-1">
					@cards('item-card', $free_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1])
				</div>
			</div>
		</div>
		@endif


		@if(config('app.blog.enabled'))
		<!-- POSTS -->
		@if($posts->count())
		<div class="wrapper posts">
			<div class="border top"></div>

			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu posts">
					<a href="{{ route('home.blog') }}" class="item" href="{{ route('home.blog') }}">
						{{ __('Posts From Our Blog') }}
					</a>
				</div>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.valexa.posts.items_per_line', '4')) }} doubling cards px-1">	
					@each('components.blog-card', $posts, 'post')
				</div>
			</div>
		</div>
		@endif
		@endif

	</div>

@endsection