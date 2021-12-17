@extends('back.master')

@section('title', __('Cache settings'))


@section('content')

<div class="ui large main form" spellcheck="false" id="app">
	<div class="table wrapper">
		<table class="ui table">
			<tbody>
				<tr>
					<td class="px-2">{{ __('Cached template files') }}</td>
					<td class="right aligned pr-2"><button class="ui red large circular button rounded mx-0" :class="{disabled: hasCache.views == '0'}" @click="clearCache('views')">{{ __('Clear') }}</button></td>
				</tr>

				<tr>
					<td class="px-2">{{ __('Cached tokens and otder data') }}</td>
					<td class="right aligned pr-2"><button class="ui red large circular button rounded mx-0" :class="{disabled: hasCache.cache == '0'}" @click="clearCache('cache')">{{ __('Clear') }}</button></td>
				</tr>

				<tr>
					<td class="px-2">{{ __('Users sessions') }}</td>
					<td class="right aligned pr-2"><button class="ui red large circular button rounded mx-0" :class="{disabled: hasCache.sessions == '0'}" @click="clearCache('sessions')">{{ __('Clear') }}</button></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<script type="application/javascript">
	'use strict';

	var app = new Vue({
		el: "#app",
		data: {
			hasCache: {
				sessions: '{{ cache_exists('sessions') ? '1' : '0' }}',
				views: '{{ cache_exists('views') ? '1' : '0' }}',
				cache: '{{ cache_exists('cache') ? '1' : '0' }}',
			}
		},
		methods: {
			clearCache: function(cacheName)
			{
				$.post('/admin/settings/clear_cache', {name: cacheName})
				.done(function(data)
				{
					if(data.hasOwnProperty('exists'))
					{
						Vue.set(app.hasCache, cacheName, data.exists);
					}
				})
			}
		}
	});
</script>
@endsection