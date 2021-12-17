@extends(view_path('master'))

@section('additional_head_tags')
<style>
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
	<div  class="ui bottom attached basic segment" id="top-search">
		<div class="ui middle aligned grid m-0">
			<div class="row">
				<div class="column center aligned">
					
					@if(config('app.search_header'))
					<h1>{{ config('app.search_header') }}</h1>
					<br>
					@endif

					@if(config('app.search_subheader'))
					<h3 class="marquee">{{ config('app.search_subheader') }}</h3>
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
					
					@if(config('home_categories'))
					<div class="categories mt-2 @if(count(config('home_categories')) > 20) large @endif">
				    <div class="ui labels">
				    	@foreach(config('home_categories') as $home_category)
							<a class="ui basic label" href="{{ $home_category->url }}">{{ $home_category->name }}</a>
				    	@endforeach
				    </div>
			    </div>
			    @endif
				</div>
			</div>
		</div>
	</div>

	@if(config('app.users_notif'))
	<div id="users-notif" class="mb-2" v-cloak v-if="usersNotifRead != '{{ config('app.users_notif') }}'">
		{{ config('app.users_notif') }}
		<i class="close circular icon mx-0" @click="markUsersNotifAsRead"></i>
	</div>
	@endif
@endsection


@section('body')
	
	<div class="row home-items">
		
		<!--  FEATURED PRODUCTS -->
		@if($featured_products->count())
		<div class="wrapper featured">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu ml-1 pl-0">
					<a href="{{ route('home.products.filter', 'featured') }}" class="item my-1 featured">
						{!! __('Featured items') !!}
					</a>
				</div>
			</div>
			
			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.default.featured.items_per_line', '4')) }} doubling cards @if(config('app.masonry_layout')) is_masonry @endif px-1">
					@cards('item-card', $featured_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1, 'home' => 1])
				</div>
			</div>
		</div>
		@endif

		
		<!--  TRENDING PRODUCTS -->
		@if($trending_products->count())
		<div class="wrapper trending">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu ml-1 pl-0">
					<a href="{{ route('home.products.filter', 'trending') }}" class="item my-1 trending">
						{!! __('Trending items') !!}
					</a>
				</div>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.default.trending.items_per_line', '4')) }} doubling cards @if(config('app.masonry_layout')) is_masonry @endif  px-1">
					@cards('item-card', $trending_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1, 'home' => 1])
				</div>
			</div>
		</div>
		@endif
		

		<!--  NEWEST PRODUCTS -->
		@if($newest_products->count())
		<div class="wrapper newest">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu ml-1 pl-0">
					<a href="{{ route('home.products.filter', 'newest') }}" class="item my-1 newest">
						{!! __('Newest items') !!}
					</a>
				</div>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.default.newest.items_per_line', '4')) }} doubling cards @if(config('app.masonry_layout')) is_masonry @endif  px-1">
					@cards('item-card', $newest_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1, 'home' => 1])
				</div>
			</div>
		</div>
		@endif
		
		
		<!--  FREE PRODUCTS -->
		@if($free_products->count())
		<div class="wrapper free">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu ml-1 pl-0">
					<a href="{{ route('home.products.filter', 'free') }}" class="item my-1 free">
						{!! __('Free items') !!}
					</a>
				</div>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.default.free.items_per_line', '4')) }} doubling cards @if(config('app.masonry_layout')) is_masonry @endif px-1">
					@cards('item-card', $free_products, 'item', ['category' => 1, 'sales' => 0, 'rating' => 1, 'home' => 1])
				</div>
			</div>
		</div>
		@endif


		<!-- POSTS -->
		@if($posts->count())
		<div class="wrapper posts">
			<div class="sixteen wide column mx-auto selection-title">
				<div class="ui menu ml-1 pl-0">
					<a href="{{ route('home.blog') }}" class="item my-1 blog" href="{{ route('home.blog') }}">
						{!! __('Posts From Our Blog') !!}
					</a>
				</div>
			</div>

			<div class="sixteen wide column mx-auto">
				<div class="ui {{ number_to_word(config('app.homepage_items.default.posts.items_per_line', '5')) }} doubling cards px-1">
					@foreach($posts as $post)
					<div class="card">
						<div class="content p-0">
							<a href="{{ route('home.post', $post->slug) }}">
								<img src="{{ asset_("storage/posts/{$post->cover}") }}" alt="cover">
							</a>
							<time>{{ $post->updated_at->diffForHumans() }}</time>
						</div>
						<div class="content title">
							<a href="{{ route('home.post', $post->slug) }}">{{ $post->name }}</a>
						</div>
						<div class="content description">
							{{ mb_substr($post->short_description, 0, 120).'...' }}
						</div>
						<div class="content tags">
							@foreach(array_slice(explode(',', $post->tags), 0, 3) as $tag)
							<a class="tag" href="{{ route('home.blog.tag', slug($tag)) }}">{{ trim($tag) }}</a><br>
							@endforeach
						</div>
					</div>
					@endforeach
				</div>
			</div>
		</div>
		@endif

	</div>

@endsection