@extends('front.default.master')

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
						<a href="{{ route('home.products.category', $category->slug) }}" class="parent header item">
							<span>{{ $category->name }}</span>
						</a> 
						@if($subcategories = config("categories.category_children.{$category->id}", []))
						<div class="children">
							<div class="wrapper">
							@foreach($subcategories as $subcategory)
								<a href="{{ route('home.products.category', $category->slug.'/'.$subcategory->slug) }}" 
									 class="item @if(request()->subcategory_slug === $subcategory->slug) active @endif">
									<span>{{ $subcategory->name }}</span>
								</a>
							@endforeach
							</div>
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
						@foreach(config('app.countries_cities') as $_country => $_cities)
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

			@if($products->count() ?? null)
			<div class="ui filter shadowless borderless menu">
				@if(!request()->selection)
				<div class="ui dropdown button floating selection item">
					<input type="hidden" value="{{ str_ireplace('_', ' ', request()->query('ob')) }}">
					<div class="default text">{{ __('Sort by') }}</div>
					<i class="sort amount @if(request()->query('o') == 'asc') up @else down @endif icon ml-1-hf"></i>
					<div class="menu rounded-corner">
						<a href="{{ filter_url(filter_is_selected('date_asc') ? 'date_desc' : 'date_asc') }}" class="item">{{ __('Release date') }}</a>
						<a href="{{ filter_url(filter_is_selected('price_asc') ? 'price_desc' : 'price_asc') }}" class="item">{{ __('Price') }}</a>
						<a href="{{ filter_url(filter_is_selected('trending_asc') ? 'trending_desc' : 'trending_asc') }}" class="item">{{ __('Trending') }}</a>
						<a href="{{ filter_url(filter_is_selected('featured_asc') ? 'featured_desc' : 'featured_asc') }}" class="item">{{ __('Featured') }}</a>
						<a href="{{ filter_url(filter_is_selected('rating_asc') ? 'rating_desc' : 'rating_asc') }}" class="item">{{ __('Rating') }}</a>
					</div>
				</div>
				@endif

				<form class="ui right aligned search item large search-form" method="get" action="{{ route('home.products.q') }}">
		      <div class="ui transparent icon input">
		        <input class="prompt" type="text" name="q" placeholder="{{ __('Search') }} ...">
		        <i class="search link icon"></i>
		      </div>
		    </form>

		    <a class="item icon left-column-toggler"><i class="bars icon mx-0"></i></a>
			</div>
			
			<div class="ui three doubling cards mt-1 @if(config('app.masonry_layout')) is_masonry @endif">
				@cards('item-card', $products, 'item', ['category' => 1, 'sales' => 1, 'rating' => 0, 'home' => 0])
			</div>
		
			@if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
			<div class="my-1"></div>
			{{ $products->appends(request()->query())->onEachSide(1)->links() }}
			{{ $products->appends(request()->query())->links('vendor.pagination.simple-semantic-ui') }}
			@endif

			@else
			<div class="ui large rounded-corner message">
				{{ __('No items found') }}
			</div>
			@endif
		</div>
	</div>
</div>

@endsection