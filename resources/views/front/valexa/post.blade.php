@extends(view_path('master'))

@section('additional_head_tags')
<script type="application/ld+json">
{
	"@context":"http://schema.org",
	"@type": "BlogPosting",
	"image": "{{ $meta_data->image }}",
	"url": "{{ $meta_data->url }}",
	"description": "{{ $meta_data->description }}",
	"headline": "{{ $meta_data->title }}",
	"dateCreated": "{{ (new \DateTime($post->created_at))->format('Y-m-d\\TH:i:s') }}",
	"datePublished": "{{ (new \DateTime($post->created_at))->format('Y-m-d\\TH:i:s') }}",
	"dateModified": "{{ (new \DateTime($post->updated_at))->format('Y-m-d\\TH:i:s') }}",
	"inLanguage": "en-US",
	"isFamilyFriendly": "true",
	"copyrightYear": "",
	"copyrightHolder": "",
	"contentLocation": {},
	"accountablePerson": {},
	"creator": {},
	"publisher": {},
	"sponsor": {},
	"mainEntityOfPage": "True",
	"keywords": "{{ $post->keywords }}",
	"genre":["SEO","JSON-LD"],
	"articleSection": "{{ $post->category }}",
	"articleBody": "{{ strip_tags($post->content) }}",
	"author": {
		"@type": "Organization",
		"name": "{{ config('app.name') }}",
		"url": "{{ config('app.url') }}"
	}
}
</script>
@endsection

@section('body')
	
	{!! place_ad('ad_728x90') !!}
	
	<div id="posts">

		<div class="ui two columns shadowless celled grid my-0 post">
			<div class="column left">
				<div class="post-cover">
					<img src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ $post->name }}">
				</div>

				<div class="post-title">
					<h1>{{ $post->name }}</h1>
					<p><span>{{ $post->category }}</span> / <span>{{ $post->updated_at->format('M d, Y') }}</span></p>
				</div>
				
				<div class="post-content">
					<div class="post-body">
						{!! $post->content !!}
					</div>
				</div>

				<div class="ui divider"></div>

				<div class="social-buttons">
					<span>{{ __('Share on') }}</span>
					<div class="buttons">
						<button class="ui circular icon twitter button" onclick="window.open('https://twitter.com/intent/tweet?text={{ $post->short_description }}&url={{ url()->current() }}', 'Twitter', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="twitter icon"></i>
						</button>

						<button class="ui circular icon vk button" onclick="window.open('https://vk.com/share.php?url={{ url()->current() }}', 'VK', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="vk icon"></i>
						</button>

						<button class="ui circular icon tumblr button" onclick="window.open('https://www.tumblr.com/widgets/share/tool?canonicalUrl={{ url()->current() }}', 'tumblr', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="tumblr icon"></i>
						</button>

						<button class="ui circular icon facebook button" onclick="window.open('https://facebook.com/sharer.php?u={{ url()->current() }}', 'Facebook', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="facebook icon"></i>
						</button>

						<button class="ui circular icon pinterest button" onclick="window.open('https://www.pinterest.com/pin/create/button/?url={{ url()->current() }}&media={{ asset("storage/posts/$post->cover") }}&description={{ $post->short_description }}', 'Pinterest', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="pinterest icon"></i>
						</button>

						<button class="ui circular icon linkedin button" onclick="window.open('https://www.linkedin.com/cws/share?url={{ url()->current() }}', 'Linkedin', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
							<i class="linkedin icon"></i>
						</button>
					</div>
				</div>
				
				@if($related_posts->count())
				<div class="ui divider"></div>

				<div class="related-posts">
					<div class="ui header">{{ __('Related posts') }}</div>
					<div class="ui three doubling stackable cards">
						@foreach($related_posts as $post)
						<div class="ui fluid card">
							<a class="content p-0" href="{{ route('home.post', $post->slug) }}">
								<img src="{{ asset_("storage/posts/{$post->cover}") }}" alt="{{ __('cover') }}">
								<time>{{ $post->updated_at->format('M d, Y') }}</time>
							</a>
							<div class="content title">
								<a href="{{ route('home.post', $post->slug) }}">{{ $post->name }}</a>
							</div>
						</div>
						@endforeach
					</div>
				</div>
				@endif
			</div>
		
			<div class="column right ">
				<div class="items-wrapper search">
					<form action="{{ route('home.blog.q') }}" method="get" id="posts-search" class="search-form">
						<div class="ui icon input fluid">
						  <input type="text" name="q" class="circular-corner" placeholder="{{ __('Find a post') }} ..." value="{{ request()->q }}">
						  <i class="search link icon"></i>
						</div>
					</form>
				</div>
				
				<div class="ui hidden divider"></div>

				<div class="items-wrapper categories">
					<div class="items-title">
						<h3>{{ __('Categories') }}</h3>
					</div>

					<div class="items-list">
						@foreach($posts_categories as $posts_category)
						<a href="{{ route('home.blog.category', $posts_category->slug) }}" class="item">
							<i class="caret right icon"></i>{{ $posts_category->name }}
						</a>
						@endforeach
					</div>
				</div>
				
				<div class="ui hidden divider"></div>

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

	</div>

@endsection