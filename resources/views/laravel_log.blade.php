<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ __('Laravel Log') }}</title>
	<link rel="icon" href="{{ asset_("assets/images/favicon.png") }}">
	
	<!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">
	
	<!-- jQuery -->
	<script type="application/javascript" src="{{ asset_('assets/jquery/jquery-3.5.1.min.js') }}"></script>
	
	<!-- Semantic-UI -->
  <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
  <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

  <style>
  	* {
  		font-size: 1.2rem;
			line-height: 1.5;
  	}

  	i {
  		line-height: 1.5 !important;
  	}

  	body {
	    margin: 0 !important;
	    background: aliceblue;
  	}

  	.ui.container {
  		margin: 0 auto !important;
			max-height: 100vh;
			overflow: hidden;
			padding: 0 1.5rem;
  	}

  	.ui.items {
		  margin: 1.5em 0;
		  background: #fff;
		  border-radius: 1rem;
		  height: calc(100vh - 112px);
		}

  	.ui.items .item {
			background: #fff;
			border-radius: 1rem;
			padding: 1.5rem;
			font-family: system-ui;
			word-break: break-word;
			height: calc(100vh - 112px);
			min-height: calc(100vh - 112px);
			margin-bottom: 1.5rem !important;
			overflow: auto;
			margin-top: 0 !important;
			display: none;
  	}

  	.ui.items .item.active {
  		display: block;
  	}

  	.ui.selection.dropdown {
  		margin: 0 !important;
  		background: #fff !important;
    	border-radius: 1rem !important;
  	}

		.ui.selection.dropdown .menu {
		  border-radius: 1rem !important;
		  box-shadow: none !important;
		  border: none !important;
		  background: #475cd1 !important;
		}

		.ui.menu .item.ui.button {
			background: #ff7272;
			color: #fff;
			font-weight: 600;
			border-radius: .75rem;
			margin-left: 1rem;
		}

		.ui.menu .item.ui.button:hover {
			background: #f76a6a;
			color: #fff;
			font-weight: 600;
		}

		.ui.menu {
			margin-top: 1.5rem !important;
		}

		.ui.message {
			padding: 1rem;
			font-weight: 500;
			border-radius: 1rem;
		}

  	.ui.menu .ui.dropdown .menu>.item {
  		color: #fff !important;
  		border-radius: 0 !important;
  	}

  	.ui.menu .ui.dropdown .menu>.item:hover, .ui.menu .ui.dropdown .menu>.active.item {
  		color: #fff !important;
  	}

  	.ui.menu .ui.dropdown .menu>.active.item {
  		background: rgb(47 61 140)!important;
	    font-weight: 600!important;
	    color: rgb(255 255 255 / 95%)!important;
  	}

  	.ui.dropdown .text {
  		line-height: 1 !important;
  	}
  </style>
</head>
<body>
	<div class="ui container">
		<div class="ui secondary menu">
			<div class="item ui scrolling selection dropdown">
				<div class="text">{{ str_replace(['[', ']'], '', $log[0][0] ?? '-') }}</div>
				<i class="dropdown icon"></i>
				<div class="menu">
					@foreach($log as $k => $error)
					<a class="item {{ $k == 0 ? 'active' : '' }}" data-value="t-{{ $k.'-'.str_replace(['-', ':', ' ', '[', ']'], '', $error[0]) }}">{{ str_replace(['[', ']'], '',$error[0]) }}</a>
					@endforeach		
				</div>
			</div>

			<a href="/laravel_log?delete=1" class="item ui large red button ml-1">{{ __('Delete Log File') }}</a>	
		</div>

		<div class="ui items">
			@if($log)
			@foreach($log as $k => $error)
			<div class="item {{ $k == 0 ? 'active' : '' }} t-{{ $k.'-'.str_replace(['-', ':', ' ', '[', ']'], '', $error[0]) }}">
				<div class="content">
					<div class="header">
						{{ str_replace(['[', ']'], '', $error[0] ?? '-') }}
					</div>

					<div class="description">
						{{ $error[1] ?? '...' }}
					</div>
				</div>
			</div>
			@endforeach
			@else
			<div class="item active">
				<div class="ui fluid yellow large message">{{ __('The error log file is empty.') }}</div>
			</div>
			@endif
		</div>

		<script>
			'use strict';

			$(function()
			{
				$('.ui.dropdown').dropdown({
					onChange: function(value, text, $choice)
					{
						$('.ui.items .item').toggleClass('active', false).siblings('.'+value).toggleClass('active', true);
					}
				})
			})
		</script>
	</div>
</body>
</html>