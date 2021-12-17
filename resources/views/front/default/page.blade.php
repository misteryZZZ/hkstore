@extends('front.default.master')

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

	<div class="row w-100" id="single-page">
		<div class="sixteen wide column">

			<div class="title-wrapper rounded-corner">
				<div class="ui shadowless fluid segment rounded-corner">
					<h1>{{ $page->name }}</h1>
				</div>
			</div>

			<div class="page-content p-2">
				{!! $page->content !!}
			</div>
		</div>
	</div>

@endsection