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