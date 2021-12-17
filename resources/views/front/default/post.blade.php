@extends('front.default.master')

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

	<div class="ui two stackable columns shadowless celled grid my-0" id="posts">
		<div class="column left post">
			<div class="post-title">
				<h1>{{ $post->name }}</h1>
				<p><i class="time icon"></i> {{ (new DateTime($post->updated_at))->format('F d, Y') }}</p>
			</div>

			<div class="social-buttons">
				<div class="ui spaced tiny buttons p-1-hf">
					<button class="ui basic button" onclick="window.open('https://twitter.com/intent/tweet?text={{ $post->short_description }}&url={{ url()->current() }}', 'Twitter', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						<i class="twitter icon"></i>
						<span>Twitter</span>
					</button>

					<button class="ui basic button" onclick="window.open('https://vk.com/share.php?url={{ url()->current() }}', 'VK', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						<i class="vk icon"></i>
						<span>VK</span>
					</button>

					<button class="ui basic button" onclick="window.open('https://www.tumblr.com/widgets/share/tool?canonicalUrl={{ url()->current() }}', 'tumblr', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						<i class="tumblr icon"></i>
						<span>Tumblr</span>
					</button>

					<button class="ui basic button" onclick="window.open('https://facebook.com/sharer.php?u={{ url()->current() }}', 'Facebook', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						<i class="facebook icon"></i>
						<span>facebook</span>
					</button>

					<button class="ui basic button" onclick="window.open('https://www.pinterest.com/pin/create/button/?url={{ url()->current() }}&media={{ asset("storage/posts/$post->cover") }}&description={{ $post->short_description }}', 'Pinterest', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						<i class="pinterest icon"></i>
						<span>Pinterest</span>
					</button>

					<button class="ui basic button" onclick="window.open('https://www.linkedin.com/cws/share?url={{ url()->current() }}', 'Linkedin', 'toolbar=0, status=0, width=\'auto\', height=\'auto\'')">
						<i class="linkedin icon"></i>
						<span>Linkedin</span>
					</button>
				</div>
			</div>
			
			<div class="post-content">
				<div class="post-body">
					{!! $post->content !!}
				</div>
			</div>
		</div>
	
		<div class="column right desktop-only">
			<div class="items-wrapper">
				<form action="{{ route('home.blog.q') }}" method="get" id="posts-search" class="search-form ui large form">
					<div class="ui icon input fluid">
					  <input type="text" name="keywords" placeholder="{{ __('Find a post') }} ..." value="{{ request()->q }}">
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