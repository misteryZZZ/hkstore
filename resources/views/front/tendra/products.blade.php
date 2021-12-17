@extends(view_path('master'))

@section('additional_head_tags')
<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "WebSite",
	"image": "{{ $meta_data->image }}",
	"name": "{{ $meta_data->title }}",
  "url": "{{ $meta_data->url }}"
	@if($category->description ?? null)
  ,"description": "{{ $meta_data->description }}"
  @elseif(request()->q)
  ,"potentialAction": {
		"@type": "SearchAction",  
		"target": "{!! route('home.products.q').'?q={query}' !!}",
		"query-input": "required name=query"
	}
  @endif
}
</script>

<script type="application/javascript"> 
	'use strict';

	window.props['products'] = @json($products->reduce(function ($carry, $item) 
																	{
																	  $carry[$item->id] = $item;
																	  return $carry;
																	}, []));
</script>
@endsection

@section('body')
<div class="ui shadowless celled grid my-0" id="items">

	<div class="row">
		<div class="column left">
			<div class="categories">
				<div class="title">{{ __('Categories') }}</div>

				@if(config('categories.category_parents'))
				<div class="ui vertical fluid menu shadowless borderless">
					@foreach(config('categories.category_parents', []) as $category)
					<div class="category @if(request()->category_slug === $category->slug) active @endif">
						<a href="{!! category_url($category->slug) !!}" class="parent header item">
							<span>{{ $category->name }}</span>
						</a> 
						@if($subcategories = config("categories.category_children.{$category->id}", []))
						<div class="children">
							@foreach($subcategories as $subcategory)
								<a href="{!! category_url($category->slug, $subcategory->slug) !!}" 
									 class="item @if(request()->subcategory_slug === $subcategory->slug) active @endif">
									<span class="left floated"><i class="caret right icon"></i></span>{{ $subcategory->name }}
								</a>
							@endforeach
						</div>
						@endif
					</div>
					@endforeach
				</div>
				@endif

			</div>

			@if($tags ?? [])
			<div class="ui hidden divider"></div>

			<div class="filter tags">
				<div class="title">{{ __('Tags') }}</div>

				<div class="ui vertical fluid menu shadowless borderless form">
					@foreach($tags ?? [] as $tag)
					<a href="{{ tag_url($tag) }}" class="item capitalize">
						<span class="ui checkbox radio {{ tag_is_selected($tag) ? 'checked' : '' }}">
						  <input type="checkbox">
						  <label><span>{{ $tag }}</span></label>
						</span>
					</a>
					@endforeach
				</div>
			</div>
			@endif

			@if(config('app.products_by_country_city'))
			<div class="ui hidden divider"></div>

			<div class="filter countries">
				<div class="title">{{ __('Country') }}</div>

				<div class="ui floating search selection fluid dropdown countries">
					<input type="hidden" name="country" value="{{ country_url($country) }}">
					<div class="text">...</div>
					<i class="dropdown icon"></i>
					<div class="menu">
						@foreach(config('app.countries_cities', []) as $_country => $_cities)
						<a href="{{ country_url($_country) }}" class="item capitalize" data-value="{{ country_url($_country) }}">{{ __(mb_ucfirst($_country)) }}</a>
						@endforeach
					</div>
				</div>
			</div>

			@if($country)
			<div class="ui hidden divider"></div>

			<div class="filter cities">
				<div class="title">{{ __('Cities') }}</div>

				<div class="ui floating multiple search selection fluid dropdown cities">
					<input type="hidden" name="cities" value="{{ $cities }}">
					<div class="text">...</div>
					<i class="dropdown icon"></i>
					<div class="menu">
						@foreach(_sort(config("app.countries_cities.{$country}", [])) as $city)
							<div class="item capitalize" data-value="{{ $city }}">{{ __($city) }}</div>
						@endforeach
					</div>
				</div>
			</div>
			@endif
			@endif

			@if(!request()->filter)
			<div class="ui hidden divider"></div>

			<div class="price">
				<div class="title">{{ __('Price range') }}</div>

				<div class="ui form">
					<div class="three fields">
						<div class="field w-100">
							<label>{{ __('Min') }}</label>
							<input type="number" step="0.1" name="min" value="{{ priceRange('min') }}" class="circular-corner">
						</div>
						<div class="field w-100">
							<label>{{ __('Max') }}</label>
							<input type="number" step="0.1" name="max" value="{{ priceRange('max') }}" class="circular-corner">
						</div>
						<div class="field">
							<label>&nbsp;</label>
							<a @click="applyPriceRange" class="ui pink circular icon button"><i class="right angle icon mx-0"></i></a>
						</div>
					</div>
				</div>
			</div>
			@endif
		</div>

		<div class="column right">

			<div class="ui results shadowless borderless menu">
				<div class="item header">
					{{ __(':total results found.', ['total' => $products->total()]) }}
				</div>
				
				@if(array_intersect(array_keys(request()->query()), ['price_range', 'tags', 'sort']))
				<div class="right menu">
					<a href="{{ reset_filters() }}" class="item remove"><i class="close icon"></i>{{ __('Filter') }}</a>
				</div>
				@endif
			</div>

			@if(!request()->filter)
			<div class="ui filter shadowless borderless menu">
				<a href="{{ filter_url('relevance_desc') }}" class="item @if(filter_is_selected('relevance_desc')) selected @endif">
					{{ __('Best match') }}
				</a>
				
				<a href="{{ filter_url(filter_is_selected('rating_asc') ? 'rating_desc' : 'rating_asc') }}" class="item {{ (filter_is_selected('rating_asc') || filter_is_selected('rating_desc')) ? 'selected' : '' }}">{{ __('Rating') }}</a>

				<a href="{{ filter_url(filter_is_selected('price_asc') ? 'price_desc' : 'price_asc') }}" class="item {{ (filter_is_selected('price_asc') || filter_is_selected('price_desc')) ? 'selected' : '' }}">{{ __('Price') }}</a>

				<a href="{{ filter_url('trending_desc') }}" class="item @if(filter_is_selected('trending_desc')) selected @endif">
					{{ __('Trending') }}
				</a>

				<a href="{{ filter_url(filter_is_selected('date_asc') ? 'date_desc' : 'date_asc') }}" class="item {{ (filter_is_selected('date_asc') || filter_is_selected('date_desc')) ? 'selected' : '' }}">{{ __('Release date') }}</a>

				<form class="ui right aligned search item search-form" method="get" action="{{ route('home.products.q') }}">
		      <div class="ui transparent icon input">
		        <input class="prompt" type="text" name="q" value="{{ request()->q }}" placeholder="{{ __('Search') }} ...">
		        <i class="search link icon"></i>
		      </div>
		    </form>

		    <a class="item icon left-column-toggler mobile-only ml-1"><i class="bars icon mx-0"></i></a>
			</div>
			@endif

			<div class="ui fluid divider"></div>

			<div class="ui three doubling cards @if(config('app.masonry_layout')) is_masonry @endif">
				@cards('item-card', $products, 'item', ['category' => 0, 'sales' => 1, 'rating' => 1, 'home' => 0])
			</div>
		
			@if($products->count())
			<div class="mt-2"></div>
			{{ $products->appends(request()->query())->onEachSide(1)->links() }}
			{{ $products->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
			@endif
		</div>
	</div>
</div>

@endsection