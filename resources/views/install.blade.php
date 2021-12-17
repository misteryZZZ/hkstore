<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
	<head>
		<meta charset="UTF-8">
		<meta name="language" content="{{ str_replace('_', '-', app()->getLocale()) }}">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>{{ __('Installation') }}</title>
		<link rel="icon" href="{{ asset_("assets/images/favicon.png") }}">
		
		<!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
		
		<!-- jQuery -->
		<script type="application/javascript" src="{{ asset_('assets/jquery/jquery-3.5.1.min.js') }}"></script>
		
		<!-- Semantic-UI -->
    <link rel="stylesheet" href="{{ asset_('assets/semantic-ui/semantic.min.2.4.2-'.locale_direction().'.css') }}">
    <script type="application/javascript" src="{{ asset_('assets/semantic-ui/semantic.min.2.4.2.js') }}"></script>

    <!-- Spacing CSS -->
		<link rel="stylesheet" href="{{ asset_('assets/css-spacing/spacing-'.locale_direction().'.css') }}">

		{{-- VueJS --}}
		<script src="{{ asset_('assets/vue.min.js') }}"></script>

		<script type="application/javascript">
			'use strict';

			window.props = {}
		</script>

		<style>
			.main.container {
				max-width: 460px !important;
				width: 100%;
				margin: auto;
				height: 100vh;
		    justify-content: center;
		    align-items: center;
		    display: flex;
			}

			* {
				font-size: 1.2rem;
			}

			input, .ui.selection.dropdown, .ui.selection.dropdown.active, table, button {
			  border-radius: 1rem !important;
			}

			.step {
				display: none;
			}

			.card .content.header .step.active {
				display: flex;
				align-items: center;
			}

			.card .content.body .step.active {
				display: block;
			}

			.ui.selection.dropdown .menu {
			  border-radius: 1rem !important;
			  box-shadow: none !important;
			  border: 1px solid lightgrey !important;
			  margin-top: 1rem;
			}

			.top.attached.steps {
				border-radius: 1rem 1rem 0 0;
				overflow: hidden;
				border: none;
				border-bottom: 1px solid rgba(34,36,38,.15);
			}

			.top.attached.steps .step {
				border: none;
			}

			#app .grid {
				margin: 1rem 0;
			}

			.ui.form.grid {
				box-shadow: 0 6px 20.1px 4.9px rgba(176,191,238,.12)!important;
    		border: none !important;
    		border-radius: 1rem;
			}

			#app input[type="file"] {
				display: none;
			}

			.fields {
				width: 100%;
    		margin: 1rem .5rem !important;
			}

			.shadowless {
				box-shadow: none !important;
			}

			.bordered {
				border: 1px solid #eaeaea !important;
			}

			button[type="submit"] {
				display: block;
				float: left;
				margin-top: 1rem !important;
				width: 120px;
			}

			.button.yellow {
				background-color: #fff429 !important;
				color: #000 !important;
			}

			.button.yellow:hover {
				background-color: #fff206 !important;
				color: #000 !important;
			}
			
			.ui.card {
				border-radius: 1.5rem !important;
				width: 400px !important;
			}

			.card .content.header .content {
				margin-left: .5rem;
			}

			.card .content.header .content * {
				font-size: 1.3rem;
		    line-height: 1.4;
		    font-weight: 600;
			}

			.card .content.footer {
				display: flex;
			}

			.card .content.footer button:first-child {
				flex: 1;
				margin-right: .5rem;
			}

			.card .content.footer button:last-child {
				flex: 1;
				margin-left: .5rem;
			}

			.table.wrapper {
				width: 100%;
				overflow-y: visible;
				overflow-x: auto;
				border-radius: 1rem;
				max-height: 300px;
			}

			.table.wrapper table {
				border: none !important;
			}

			.table thead th {
        text-align: center;
      }

      td.compatible {
			  color: #00b5ad;
			  font-weight: 600;
			}

			td.not-compatible {
			  color: #ff6b6b;
			  font-weight: 600;
			}

      .table thead th tr:first-child th {
        background: #f8f8ff;
        font-size: 1.1rem;
      }

      .table thead th tr:first-child th {
        background: #f8f8ff;
        font-size: 1.1rem;
      }


		  .table.wrapper tr td:first-child {
	      font-size: 1.2rem;
	      background: ghostwhite;
	    }
		</style>
	</head>
	<body>
		
		<div class="ui main container" id="app">
			<div class="ui one column grid">

				<div class="column">
					@if($errors->any())
				    @foreach ($errors->all() as $error)
						<div class="ui negative fluid small message">
							<i class="times icon close"></i>
							{{ $error }}
						</div>
				    @endforeach
					@endif

					<form class="ui grid form m-0" method="post" action="{{ route('home.install_app') }}" enctype="multipart/form-data">
						
						<div class="ui fluid card">
							<div class="content header">
								<div class="step" :class="{active: stepIsActive(1)}">
									<i class="cog big icon"></i>
							    <div class="content">
							      <div class="title">{{ __('Requirements') }}</div>
							      <div class="description">{{ __('Script requirements') }}</div>
							    </div>
								</div>

								<div class="step" :class="{active: stepIsActive(2)}">
									<i class="cog big icon"></i>
							    <div class="content">
							      <div class="title">{{ __('General') }}</div>
							      <div class="description">{{ __('General settings') }}</div>
							    </div>
								</div>

								<div class="step" :class="{active: stepIsActive(3)}">
									<i class="database big icon"></i>
									<div class="content">
									  <div class="title">{{ __('Database') }}</div>
									  <div class="description">{{ __('Database settings') }}</div>
									</div>
								</div>

								<div class="step" :class="{active: stepIsActive(4)}">
									<i class="user big icon"></i>
									<div class="content">
									  <div class="title">{{ __('Admin access') }}</div>
									  <div class="description">{{ __('Admin account') }}</div>
									</div>
								</div>
							</div>

							<div class="content body">
								<div class="step requirements" :class="{active: stepIsActive(1)}">
									<div class="table wrapper">
										<table class="ui celled table">
											<tbody>
												<tr>
													<td class="six column wide">{{ __('PHP version') }}</td>
													<td>>= {{ $requirements['php']['version'] }}</td>
													<td class="{{ $requirements['php']['version'] <= $requirements['php']['current'] ? 'compatible' : 'not-compatible' }}">
														{{ $requirements['php']['current'] }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>

									<div class="table wrapper mt-1">
										<table class="ui celled table">
											<tbody>
												<tr>
													<td class="six column wide">{{ __('MySQL version') }}</td>
													<td>>= {{ $requirements['mysql']['version'] }}</td>
													<td class="{{ $requirements['mysql']['current']['compatible'] ? 'compatible' : 'not-compatible' }}">
														{{ $requirements['mysql']['current']['distrib'].' v'.$requirements['mysql']['current']['version'] }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>

									<div class="table wrapper mt-1">
										<table class="ui celled table">
											<thead>
												<tr>
													<th>{{ __('PHP Extension') }}</th>
													<th class="center aligned">{{ __('Enabled') }}</th>
												</tr>
											</thead>

											<tbody>
												@foreach($requirements['php_extensions'] as $name => $enabled)
												<tr>
													<td>{{ ucfirst($name) }}</td>
													<td class="center aligned">{!! $enabled ? '<i class="check teal circle large outline icon mx-0"></i>' : '<i class="circle red large outline icon mx-0"></i>' !!}</td>
												</tr>
												@endforeach
											</tbody>
										</table>
									</div>
								</div>

								<div class="step general" :class="{active: stepIsActive(2)}">
									<div class="field">
								  	<label>{{ __('Name') }}</label>
								  	<input type="text" name="site[name]" value="{{ old('site.name', request()->input('site.name')) }}">
								  </div>

								  <div class="field">
								  	<label>{{ __('Title') }}</label>
								  	<input type="text" name="site[title]" value="{{ old('site.title', request()->input('site.title')) }}">
								  </div>

								  <div class="field">
								  	<label>{{ __('Items Per Page') }}</label>
								  	<input type="number" name="site[items_per_page]" value="{{ old('site.items_per_page', request()->input('site.items_per_page')) }}">
								  </div>

									<div class="field">
								  	<label>{{ __('Purchase code') }}</label>
								  	<input type="text" required name="site[purchase_code]" value="NullJungle">
								  </div>
								</div>

								<div class="step database" :class="{active: stepIsActive(3)}">
									<div class="field">
								  	<label>{{ __('Database host') }}</label>
								  	<input type="text" required name="database[host]" value="{{ old('database.host', request()->input('database.host')) }}">
								  </div>

									<div class="field">
								  	<label>{{ __('Database username') }}</label>
								  	<input type="text" required name="database[username]" value="{{ old('database.username', request()->input('database.username')) }}">
								  </div>

								  <div class="field">
								  	<label>{{ __('Database password') }}</label>
								  	<input type="text" required name="database[password]" value="{{ old('database.password', request()->input('database.password')) }}">
								  </div>

								  <div class="field">
								  	<label>{{ __('Database name') }}</label>
								  	<input type="text" required name="database[database]" value="{{ old('database.database', request()->input('database.database')) }}">
								  </div>

								  <div class="field">
								  	<button class="ui basic big fluid button rounded mx-0" type="button" @click="testDBConnection($event)">{{ __('Test connection') }}</button>
								  </div>
								</div>

								<div class="step admin" :class="{active: stepIsActive(4)}">
									<div class="field">
								  	<label>{{ __('Admin username') }}</label>
								  	<input type="text" required name="admin[username]" value="{{ old('admin.username', request()->input('admin.username')) }}">
								  </div>

								  <div class="field">
								  	<label>{{ __('Admin email') }}</label>
								  	<input type="email" required name="admin[email]" value="{{ old('admin.email', request()->input('admin.email')) }}">
								  </div>

								  <div class="field">
								  	<label>{{ __('Admin password') }}</label>
								  	<input type="text" required name="admin[password]" value="{{ old('admin.password', request()->input('admin.password')) }}">
								  </div>

								  <div class="field">
								  	<label>{{ __('Admin avatar') }}</label>
								  	<button class="ui basic big fluid button rounded" type="button" onclick="this.nextElementSibling.click()">{{ __('Browse') }}</button>
										<input type="file" name="admin[avatar]" accept="image/*">
								  </div>
								</div>
							</div>

							<div class="content footer">
								<button class="ui large button ml-0 mr-auto" @click="navigateSteps(-1)" type="button" :class="{disabled: stepIsActive(1)}">{{ __('Previous') }}</button>
								<button class="ui large button ml-auto mr-0" @click="navigateSteps(1)" type="button" v-if="step <= 3">{{ __('Next') }}</button>
								<button class="ui large yellow button ml-auto mr-0" type="button" @click="submitForm" v-if="step == 4">{{ __('Submit') }}</button>
							</div>
						</div>

					</form>
				</div>
				
			</div>
		</div>

<script>
	'use strict';
	
	var app = new Vue({
		el: '#app',
		data: {
			step: 1
		},
		methods: {
			navigateSteps: function(number)
			{
				if(this.step + number > 4 || this.step + number < 1)
				{
					return false;
				}

				this.step += number;
			},
			stepIsActive: function(step)
			{
				return this.step == step;
			},
			submitForm: function()
			{	
				$('form').submit()
			},
			testDBConnection: function(e)
			{
				$(e.target).toggleClass('loading', true);

				var formData = $('form').serializeArray();
				var config = {
							host: null,
							username: null,
							password: null,
							database: null,
						};

				for(var i of formData)
				{
				    if(/database\[.+\]/i.test(i.name))
				    {
				        var name = i.name.replaceAll(/(database\[|\])/ig, '');
				        config[name] = i.value;
				    }
				}

				$.post('/admin/settings/check_database_connection', {database : config, installation: true})
				.done(function(data)
				{
					alert(data.status)
				})
				.always(function()
				{
					$(e.target).toggleClass('loading', false);
				})
			}
		}
	})

	$('.tabular.steps .step').tab()
	$('.ui.dropdown').dropdown()
	$('.ui.checkbox').checkbox()

	$(document).on('click', '.ui.message i.close', function()
	{
		$(this).closest('.ui.message').hide();
	})

	$('input').on('keypress', function(e)
	{
		if(e.keyCode === 13)
		{
			e.preventDefault();
			return false;
		}
	})
</script>
	</body>
</html>