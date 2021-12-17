@extends(view_path('master'))

@section('additional_head_tags')
<script type="application/ld+json">
{
	"@context": "http://schema.org",
	"@type": "WebPage",
	"image": "{{ $meta_data->image }}",
	"name": "{{ $meta_data->title }}",
  "url": "{{ $meta_data->url }}",
  "description": "{{ $meta_data->description }}"
}
</script>
@endsection


@section('body')
	<div class="one column row w-100" id="single-page">
		<div class="column">

			<div class="title-wrapper">
				<h1>{{ $page->name }}</h1>
				<div class="ui big breadcrumb">
					<a href="/" class="section">{{ __('Home') }}</a>
					<i class="right chevron icon divider"></i>
					<span class="active section">{{ $page->name }}</span>
				</div>
			</div>

			<div class="page-content p-2">
				{!! $page->content !!}
			</div>
		</div>
	</div>

@endsection