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

	@if(config('app.tendra_top_cover_mask'))
	#top-search {
		-webkit-mask-image: url('{{ asset_('storage/images/'.config('app.tendra_top_cover_mask')) }}');
    -webkit-mask-position: bottom center;
    -webkit-mask-size: cover;
    padding-bottom: 6rem;
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
	<div class="ui bottom attached basic segment" id="top-search">
		<div class="ui middle aligned grid m-0">
			<div class="row">
				<div class="column center aligned">
					
					@if(config('app.search_header'))
					<h1>{{ __(config('app.search_header')) }}</h1>
					<br>
					@endif

					@if(config('app.search_subheader'))
					<h3 class="marquee">{{ __(config('app.search_subheader')) }}</h3>
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
@endsection


@section('body')
	
	<div class="row home-items">
		
		<!--  NEWEST PRODUCTS -->
		@if($newest_products->count())
		<div class="newest wrapper">
			<div class="ui header">
				{{ __('Our Newest Items') }}
				<div class="sub header">
					{{ __('Explore our newest Digital Products, from :first_category to :last_category, we always have something interesting for you.',
					['first_category' => collect(config('categories.category_parents'))->first()->name ?? null, 
					 'last_category' => collect(config('categories.category_parents'))->last()->name ?? null]) }}
				</div>
			</div>

			<div class="ui {{ number_to_word(config('app.homepage_items.tendra.newest.items_per_line', '8')) }} items {{ is_single_prdcts_type() }}">
				@foreach($newest_products as $newest_product)
				<a href="{{ item_url($newest_product) }}" class="item" style="background-image: url({{ asset_("storage/covers/{$newest_product->cover}") }})" data-detail="{{ json_encode($newest_product) }}">
				</a>
				@endforeach
			</div>
		</div>

		<div class="ui popup newest-item">
			<div class="ui fluid card">
				<div class="image">
					<img src="">
					<div class="price"></div>
					<div class="play">
						<a><img src="{{ asset_('assets/images/play.png') }}"></a>
					</div>
				</div>
				<div class="content">
					<div class="name"></div>
				</div>
			</div>
		</div>
		@endif


		<!--  FEATURED PRODUCTS -->
		@if($featured_products)
		<div class="featured wrapper" id="featured-items">
			<div class="ui header">
				{{ __('Featured Items Of The Week') }}
				<div class="sub header">
					{{ __('Explore our best items of the week. :categories and more.',
					['categories' => implode(', ', array_map(function($category)
					{
						return __($category->name ?? null);
					}, config('categories.category_parents') ?? []))]) }}
				</div>
			</div>
			
			<div class="ui secondary menu">
    			@foreach($featured_products as $category_slug => $items_list)
        			@foreach(config('categories.category_parents') ?? [] as $category)
            			@if($category_slug == $category->slug)
        				<a class="item tab {{ $loop->parent->first ? 'active' : '' }}" data-category="{{ $category->slug }}">{{ $category->name }}</a>
        				@endif
    				@endforeach
    			@endforeach
			</div>

			@foreach($featured_products as $category_slug => $items_list)
			<div class="ui {{ number_to_word(config('app.homepage_items.tendra.featured.items_per_line', '3')) }} doubling cards mt-0 {{ $category_slug }} {{ $loop->first ? 'active' : '' }}">
				@cards('item-card', $items_list, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1, 'home' => 1])
			</div>
			@endforeach

			<div class="ui segment borderless shadowless center aligned mt-2">
				<a href="/items/category/{{ array_keys($featured_products)[0] ?? null }}" class="ui teal big circular button mx-0 more-items">{{ __('More items') }}</a>
			</div>
		</div>
		@endif
	

		<!-- FREE ITEMS -->
		@if($free_products->count())
		<div class="free wrapper mt-4">
			<div class="ui header">
				{{ __('Our Free Items') }}
				<div class="sub header">
					{{ __('Explore our free items of the week') }}
				</div>
			</div>

			<div class="ui {{ number_to_word(config('app.homepage_items.tendra.free.items_per_line', '6')) }} doubling cards">
				@foreach($free_products as $free_product)
				<a class="fluid card" href="{{ item_url($free_product) }}">
					<div class="image">
						<div class="thumbnail" style="background-image: url('{{ asset_("storage/covers/{$free_product->cover}") }}')"></div>
					</div>
					<div class="title">{{ $free_product->name }}</div>
				</a>
				@endforeach
			</div>
		</div>
		@endif

		<!-- SUBSCRIPTION PLANS -->
		@if(config('app.subscriptions.enabled') && $subscriptions->count())
		<div class="pricing container">
			<div class="pricing wrapper">
				<div class="ui header">
					{{ __('Our Pricing Plans') }}
					<div class="sub header">
						{{ __('Explore our pricing plans, from :first to :last, choose the one that meets your needs.', ['first' => $subscriptions->first()->name, 'last' => $subscriptions->last()->name]) }}
					</div>
				</div>
				
				<div class="ui {{ number_to_word(config('app.homepage_items.tendra.pricing_plans.items_per_line', '3')) }} doubling cards mt-2">
					@foreach($subscriptions as $subscription)
					<div class="card">
						<div class="contents">
							<div class="content price">
								<div style="color: {{ $subscription->color ?? '#000' }}">
									{{ price($subscription->price) }}
									@if($subscription->title)<span>/ {{ __($subscription->title) }}</span>@endif
								</div>
							</div>

							<div class="content description">
								@foreach(explode("\n", $subscription->description) as $note)
								<div><i class="check blue icon"></i>{{ $note }}</div>
								@endforeach
							</div>

							<div class="content buy">
								<a href="{{ pricing_plan_url($subscription) }}" class="ui large circular button mx-0" style="background: {{ $subscription->color ?? '#667694' }}">
									{{ __('Get started') }}
								</a>
							</div>

							<div class="name" style="background: {{ $subscription->color ?? '#667694' }}">
								<span>{{ __($subscription->name) }}</span>
							</div>
						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>
		@endif


		<!-- POSTS -->
		@if(config('app.blog.enabled'))
		@if($posts->count())
			<div class="posts wrapper">
				<div class="ui header">
					{{ __('Our Latest News') }}
					<div class="sub header">
						{{ __('Explore our latest articles for more ideas and inspiration, technology, design, tutorials, business and much more.') }}
					</div>
				</div>

				<div class="ui {{ number_to_word(config('app.homepage_items.tendra.posts.items_per_line', '3')) }} doubling cards mt-2">
					@foreach($posts as $post)
					<div class="card">
						<a class="image" href="{{ route('home.post', $post->slug) }}">
							<img src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ $post->name }}">
						</a>

						<div class="content metadata">
							<div class="left">
								<div>{{ $post->updated_at->format('d') }}</div>
								<div>{{ $post->updated_at->format('M, Y') }}</div>
							</div>
							<div class="right">
								<a href="{{ route('home.post', $post->slug) }}" title="{{ $post->name }}">{{ shorten_str($post->name, 60) }}</a>
							</div>
						</div>

						<div class="content description">
							{{ shorten_str($post->short_description, 100) }}
						</div>

						<div class="content action">
							<a href="{{ route('home.post', $post->slug) }}">{{ __('Read more') }}<i class="plus icon ml-1-hf"></i></a>
						</div>
					</div>
					@endforeach
				</div>
			</div>
		@endif
		@endif
		
	</div>

@endsection