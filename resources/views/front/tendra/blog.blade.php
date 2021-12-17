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
	<div id="blog">
		
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

		<div class="ui one column shadowless celled grid posts my-0">
			<div class="column">
				@if($posts->count())
				<div class="ui three doubling cards px-0">
					@foreach($posts as $post)
					<div class="ui fluid card">
						<a class="content p-0" href="{{ route('home.post', $post->slug) }}">
							<img src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ __('cover') }}">
							<time>{{ $post->updated_at->format('M d, Y') }}</time>
						</a>
						<div class="content title">
							<a href="{{ route('home.post', $post->slug) }}">{{ $post->name }}</a>
						</div>
						<div class="content description">
							{{ shorten_str($post->short_description, 120) }}
						</div>
						<div class="content tags">
							@foreach(array_slice(explode(',', $post->tags), 0, 3) as $tag)
							<a class="tag" href="{{ route('home.blog.tag', slug($tag)) }}">{{ trim($tag) }}</a><br>
							@endforeach
						</div>
					</div>
					@endforeach
				</div>
				
				{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->onEachSide(1)->links() }}
				{{ $posts->appends(request()->q ? ['q' => request()->q] : [])->links('vendor.pagination.simple-semantic-ui') }}
				@endif
			</div>
		</div>

	</div>

@endsection