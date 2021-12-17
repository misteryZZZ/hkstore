<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="UTF-8">
		<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
		<title>{{ config('app.name') }}</title>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex,nofollow">
		<link rel="icon" href="{{ asset_("storage/images/".config('app.favicon')) }}">
		<link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.css') }}">
		<link href="https://fonts.googleapis.com/css?family=Kodchasan:400,500,700" rel="stylesheet">

		<!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
		<style>
			body, .grid
			{
				margin: 0 !important;
				overflow: hidden;
				height: 100vh;
			}

			* {
				font-family: 'Kodchasan' !important;
			}

			.column {
				text-align: center;
			}

			h1 {
				font-size: 4rem;
				color: skyblue;
			}

			h3 {
			    color: #909090;
			}

			.secondary.menu {
				display: block !important;
			}
	
			.ui.secondary.menu a {
			  display: inline-block !important;
			  margin: 0 !important;
			  text-align: center;
			}
		</style>		
	</head>

	<body>
		<div class="ui middle aligned grid">
			<div class="column">
				<h2>{!! $message !!}</h2>

				<div class="ui hidden divider"></div>
				<div class="ui secondary menu">
					<a href="{{ config('app.url') }}" class="item">{{ __('Home') }}</a>

				  @foreach(config('categories')['category_parents'] ?? [] as $category)
					<a href="{{ route('home.products.category', $category->slug) }}" class="item">{{ $category->name }}</a>
				  @endforeach
				</div>
			</div>
		</div>
	</body>
</html>
