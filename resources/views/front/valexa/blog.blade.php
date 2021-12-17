@extends(view_path('master'))

@section('additional_head_tags')
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
	
	{!! place_ad('ad_728x90') !!}
	
	<div class="ui two columns shadowless celled grid my-0" id="posts">
		<div class="column left">
			<div class="ui secondary menu">
				<div class="item header">
					@if(request()->category || request()->tag || request()->query('q'))
					{!! __(':total Posts found for :name',
							['total' => $posts->total(), 'name' => '<span><a href="'.route('home.blog').'"><i class="close icon"></i></a>'.$filter->value.'</span>']) !!}	
					@else
					{{ __(':total Posts found.', ['total' => $posts->total()]) }}	
					@endif
				</div>

				<div class="right menu">
					<div class="ui search item">
						<form class="ui icon input" action="{{ route('home.blog.q') }}" method="get">
							<input type="text" name="q" value="{{ request()->query('q') }}" placeholder="{{ __('Find a post') }}" class="prompt"> 
							<i class="search link icon"></i>
						</form>
					</div>

					<div class="item ui dropdown">
						<i class="bars icon mx-0"></i>
						<div class="menu">
							@foreach($posts_categories ?? [] as $posts_category)
							<a href="{{ blog_category_url($posts_category->slug) }}" class="item">{{ $posts_category->name }}</a>
							@endforeach
						</div>
					</div>
				</div>
			</div>

			
			@if($posts->count())
			<div class="ui three doubling stackable cards px-0">
				@each('components.blog-card', $posts, 'post')
			</div>
			
			
			<div class="ui hidden divider"></div>

			{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->onEachSide(1)->links() }}
			{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->links('vendor.pagination.simple-semantic-ui') }}
			@endif
		</div>
	
		<div class="column right ">
			<div class="items-wrapper latest-posts">
				<div class="items-title">
					<h3>{{ __('Latest posts') }}</h3>
				</div>

				<div class="items-list">
					@foreach($latest_posts as $latest_post)
					<div class="item">
						<a href="{{ route('home.post', $latest_post->slug) }}" style="background-image: url({{ asset_("storage/posts/{$latest_post->cover}") }})"></a>
						<div class="content">
							<a href="{{ route('home.post', $latest_post->slug) }}">{{ $latest_post->name }}</a>
							<p class="m-0">
								<a href="{{ route('home.blog.category', $latest_post->category_slug) }}">{{ $latest_post->category_name }}</a>
								<span>/</span>
								<span>{{ $latest_post->updated_at->format('M d, Y') }}</span>
							</p>
						</div>
					</div>
					@endforeach
				</div>
			</div>

			<div class="ui hidden divider"></div>

			<div class="items-wrapper tags">
				<div class="items-title">
					<h3>{{ __('Tags') }}</h3>
				</div>

				<div class="items-list">
					@foreach($tags as $tag)
					<a href="{{ route('home.blog.tag', $tag) }}" class="tag">{{ $tag }}</a>
					@endforeach
				</div>
			</div>
		</div>
	</div>

@endsection