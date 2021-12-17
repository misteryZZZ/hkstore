@extends('front.default.master')

@section('additional_head_tags')
<title>{{ $meta_data->name }}</title>

<link rel="canonical" href="{{ preg_replace('/https?\:/i', '', $meta_data->url) }}">

<meta name="description" content="{{ $meta_data->description }}">

<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{{ $meta_data->title }}">
<meta property="og:type" content="Website">
<meta property="og:url" content="{{ $meta_data->url }}">
<meta property="og:description" content="{{ $meta_data->description }}">
<meta property="og:image" content="{{ $meta_data->image }}">

<meta name="twitter:title" content="{{ $meta_data->name }}">
<meta name="twitter:url" content="{{ $meta_data->url }}">
<meta name="twitter:description" content="{{ $meta_data->description }}">
<meta name="twitter:site" content="{{ $meta_data->url }}">
<meta name="twitter:image" content="{{ $meta_data->image }}">

<meta itemprop="title" content="{{ $meta_data->name }}">
<meta itemprop="name" content="{{ config('app.name') }}">
<meta itemprop="url" content="{{ $meta_data->url }}">
<meta itemprop="description" content="{{ $meta_data->description }}">
<meta itemprop="image" content="{{ $meta_data->image }}">

<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "Blog",
	  "name": "{{ $meta_data->title }}",
	  "url": "{{ $meta_data->url }}",
	  "image": "{{ $meta_data->image }}",
	  "description": "{{ $meta_data->description }}"
	  @if(request()->q)
	  ,"potentialAction": {
			"@type": "SearchAction",  
			"target": "{!! route('home.blog.q').'?q={query}' !!}",
			"query-input": "required name=query"
		}
	  @endif
}
</script>
@endsection

@section('body')

	<div class="ui two stackable columns shadowless celled grid my-0" id="posts">
		<div class="column left">
			@if($filter)
			<div class="ui segment shadowless filter p-1-hf">
				<div class="ui labels">
				   <div class="ui basic label mb-0">
				   		<i class="filter icon"></i>{{ $filter->name }}
				   </div>
				   <div class="ui yellow label mb-0">
				      {{ $filter->value }}<i class="times icon link mr-0 ml-1-hf" onclick="location.href = '{{ route('home.blog') }}'"></i>
				   </div>
				</div>
			</div>
			@endif
			
			@if($posts->count())
			<div class="ui three doubling cards px-0">
				@foreach($posts as $post)
				<div class="card">
					<div class="content p-0">
						<a href="{{ route('home.post', $post->slug) }}">
							<img src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ __('cover') }}">
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
			
			
			<div class="ui fluid divider"></div>

			{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->onEachSide(1)->links() }}
			{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->links('vendor.pagination.simple-semantic-ui') }}
			@endif
		</div>
	
		<div class="column right desktop-only">
			<div class="items-wrapper">
				<form action="{{ route('home.blog.q') }}" method="get" id="posts-search" class="search-form">
					<div class="ui icon input fluid">
					  <input type="text" name="q" placeholder="{{ __('Find a post') }} ..." value="{{ request()->q }}">
					  <i class="search link icon"></i>
					</div>
				</form>
			</div>

			<div class="ui hidden divider"></div>

			<div class="items-wrapper">
				<div class="items-title">
					<h3>{{ __('Categories') }}</h3>
				</div>

				<div class="items-list">
					@foreach($posts_categories as $posts_category)
					<a href="{{ route('home.blog.category', $posts_category->slug) }}" class="tag">
						{{ $posts_category->name }}
					</a>
					@endforeach
				</div>
			</div>
			
		</div>
	</div>

@endsection