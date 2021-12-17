<title>{!! $meta_data->title !!}</title>

<link rel="canonical" href="{{ preg_replace('/https?\:/i', '', $meta_data->url) }}">

<meta name="description" content="{!! $meta_data->description !!}">

<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:title" content="{!! $meta_data->title !!}">
<meta property="og:type" content="Website">
<meta property="og:url" content="{{ $meta_data->url }}">
<meta property="og:description" content="{!! $meta_data->description !!}">
<meta property="og:image" content="{{ $meta_data->image }}">

<meta name="twitter:title" content="{!! $meta_data->title !!}">
<meta name="twitter:url" content="{{ $meta_data->url }}">
<meta name="twitter:description" content="{!! $meta_data->description !!}">
<meta name="twitter:site" content="{{ config('app.url') }}">
<meta name="twitter:image" content="{{ $meta_data->image }}">

<meta itemprop="title" content="{!! $meta_data->title !!}">
<meta itemprop="name" content="{{ config('app.name') }}">
<meta itemprop="url" content="{{ $meta_data->url }}">
<meta itemprop="description" content="{!! $meta_data->description !!}">
<meta itemprop="image" content="{{ $meta_data->image }}">

<meta property="fb:app_id" content="{{ config('app.fb_app_id') }}">
<meta name="og:image:width" content="590">
<meta name="og:image:height" content="auto">