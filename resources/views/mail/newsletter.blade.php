<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

		<style>
			@if(locale_direction() === 'ltr')
			@import url('https://fonts.googleapis.com/css2?family=Spartan:wght@400;700&display=swap'); 
			p, h1, h2, h3, h4, ol, li, ul, th, td, span, a {  
				font-family: 'Spartan', sans-serif;
				line-height: 1.5;
			}
			@else 
			@import url('https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap');
			p, h1, h2, h3, h4, ol, li, ul, th, td, span, a {  
				font-family: 'Almarai', sans-serif;
				line-height: 1.5;
			}
			@endif
		</style>
	</head>

	<body dir="ltr">
		<table style="height: 100%; width: 100%; min-height: 500px; background: ghostwhite; padding: 1rem;">
			<tbody><tr><td>
				<table style="max-width: 600px;width: 600px;margin: auto;background: #ffffff;border-radius: 1rem;padding: 1.5rem;overflow: auto;">
					<thead>
						<tr>
							<th>
								<div style="padding: 1rem;font-size: 1.8rem;color: #5d5d5d; font-weight: 500">{{ config('app.name') }}</div>
								<div style="margin-bottom: 2rem;height: .25rem;background: #ffa197;border-radius: 100%;"></div>
							</th>
						</tr>

						<tr>
							<td>
								<div style="display: flex;justify-content: center;align-items: center;margin: 0;">
									<div style="display: block;padding: .25rem;margin: 0 .5rem 0 auto;"><a style="text-decoration: none;color: #404040;font-weight: 500;font-size: 1.1rem;" href="{{ route('home') }}">{{ __('Home') }}</a></div>

									@if(config('app.blog.enabled'))
									<div style="display: block;padding: .25rem;margin: 0 .5rem;"><a style="text-decoration: none;color: #404040;font-weight: 500;font-size: 1.1rem;" href="{{ route('home.blog') }}">{{ __('Blog') }}</a></div>
									@endif

									@if(config('app.subscriptions.enabled'))
									<div style="display: block;padding: .25rem;margin: 0 .5rem;"><a style="text-decoration: none;color: #404040;font-weight: 500;font-size: 1.1rem;" href="{{ route('home.subscriptions') }}">{{ __('Pricing') }}</a></div>
									@endif

									<div style="display: block;padding: .25rem;margin: 0 auto 0 0;"><a style="text-decoration: none;color: #404040;font-weight: 500;font-size: 1.1rem;" href="{{ route('home.support') }}">{{ __('Support') }}</a></div>
								</div>
							</td>
						</tr>

						<tr>
							<td>
								<div style="display:flex;padding:.5rem;margin:0;background:#fbfbff;border-radius:1rem;flex-wrap: wrap;justify-content: center;">
									@foreach(config('categories.category_parents') as $category)
									<div style="display: block;padding: .5rem;margin: .35rem;background: #4ee3f2;border-radius: .75rem;"><a style="text-decoration: none;color: #ffffff;font-weight: 500;font-size: .9rem;" href="{{ route('home.products.category', ['category_slug' => $category->slug]) }}">{{ __($category->name) }}</a></div>
									@endforeach
								</div>
							</td>
						</tr>
					</thead>

					<tbody>
						<tr>
							<td>
								@foreach($selections as $title => $items)
								<div style="margin: 2rem 0 1rem;">

									<div style="margin-bottom: 1rem;">
										<span style="padding: .5rem 1rem .5rem 0;font-size: 1.3rem;display: table;font-weight: 500;color: #333333;margin: 0;border-radius: 0 3rem 3rem 0;border: 1px solid #fe968b;border-left: none;border-right-width: 10px;">{{ $title }}</span>
									</div>

									<div>
											@foreach(array_chunk($items, 2) as $items)
											<div>
												@foreach($items as $item)
													<a href="{{ item_url($item) }}" target="_blank" style="border-radius: 1rem;overflow: hidden;background: #fff;text-decoration: none;color: #000;display: flex; {{ !$loop->last ? 'margin-bottom: 1rem;' : '' }}">
															<div style="width: 111px;height: 111px; min-width: 111px; border-radius: 1rem; background-position: center; background-size: cover; background-repeat: no-repeat; background-image: url('{{ secure_asset("storage/covers/{$item['cover']}") }}');">
															</div>

															<div style="padding: .75rem;background:ghostwhite;border-radius:1rem;margin-left:1rem;position: relative;width: 100%;">

																<div style="text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;padding:.5rem 1rem;background:#fff;border-radius:1rem;color:lightslategrey;margin-bottom: 0;font-size: 1rem;display: block;">{{ $item['name'] }}</div>

																@if($item['promotional_price'])
																<div style="margin-top: .5rem;line-height: 1;border-radius: 1rem;font-weight: 600;font-size: 0.8rem;padding: .5rem 1rem;background: #fff;color: tomato; display: table;">
																	<span style="line-height: 1;border-radius: 1rem; filter: opacity(0.6); margin-right: .5rem; text-decoration: line-through;">{{ price($item['price'], 1, 1, 2, 'code') }}</span>
																	<span style="margin-left: .25rem;">{{ price($item['promotional_price'], 1, 1, 2, 'code') }}</span>
																</div>
																@else
																    
																<div style="margin-top: .5rem;line-height: 1;border-radius: 1rem;font-weight: 600;font-size: .8rem;padding: .5rem 1rem;background: #fff; display: table; color: {{ $item['price'] == 0 ? '#02c39f' : 'tomato' }}">
																	{{ $item['price'] == 0 ? __('Free') : price($item['price'], 1, 1, 2, 'code') }}
																</div>
																@endif
															</div>
														</a>
												@endforeach
											</div>
											@endforeach
									</div>

								</div>
								@endforeach
							</td>
						</tr>
					</tbody>

					<tfoot>
						<tr>
							<td>
								<table style="margin: 1.5rem auto 1rem">
									<tbody>
										<tr>
											@if(config('app.facebook'))
											<td>
											<a style="display: block;padding: .25rem;" href="{{ config('app.facebook') }}">
												<img style="width: 40px;border-radius: 100px;height: 40px;" src="{{ asset('assets/images/facebook.png') }}" alt="facebook">
											</a>
											</td>
											@endif

											@if(config('app.twitter'))
											<td>
											<a style="display: block;padding: .25rem;" href="{{ config('app.twitter') }}">
												<img style="width: 40px;border-radius: 100px;height: 40px;" src="{{ asset('assets/images/twitter.png') }}" alt="twitter">
											</a>
											</td>
											@endif

											@if(config('app.pinterest'))
											<td>
											<a style="display: block;padding: .25rem;" href="{{ config('app.pinterest') }}">
												<img style="width: 40px;border-radius: 100px;height: 40px;" src="{{ asset('assets/images/pinterest.png') }}" alt="pinterest">
											</a>
											</td>
											@endif

											@if(config('app.youtube'))
											<td>
											<a style="display: block;padding: .25rem;" href="{{ config('app.youtube') }}">
												<img style="width: 40px;border-radius: 100px;height: 40px;" src="{{ asset('assets/images/youtube.png') }}" alt="youtube">
											</a>
											</td>
											@endif

											@if(config('app.tumblr'))
											<td>
											<a style="display: block;padding: .25rem;" href="{{ config('app.tumblr') }}">
												<img style="width: 40px;border-radius: 100px;height: 40px;" src="{{ asset('assets/images/tumblr.png') }}" alt="tumblr">
											</a>
											</td>
											@endif
										</tr>
									</tbody>
								</table>
							</td>
						</tr>

						<tr>
							<td>
								<table style="margin: 0 auto">
									<tbody>
										<tr>
											<td>
												<a style="display: block;padding: .25rem; color: #000;text-decoration: none;font-size: 1rem;" href="{{ route('home.support') }}">
													{{ __('Support') }}
												</a>
											</td>

											<td>
												<span class="mx-1-hf">-</span>
											</td>

											<td>
												<a style="display: block;padding: .25rem; color: #000;text-decoration: none;font-size: 1rem;" href="{{ page_url('terms-and-conditions') }}">
													{{ __('Terms and conditions') }}
												</a>
											</td>

											<td>
												<span class="mx-1-hf">-</span>
											</td>

											<td>
												<a style="display: block;padding: .25rem; color: #000;text-decoration: none;font-size: 1rem;" href="{{ page_url('privacy-policy') }}">
													{{ __('Privacy policy') }}
												</a>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>

						<tr>
							<td>
								<div style="text-align:right;padding:2rem 1.75rem 1.5rem;border-radius:0 0 .75rem .75rem;background:salmon;margin-top: 1rem;">
									<a style="text-decoration: none;font-size: .9rem;font-weight: 500;color: #ffffff;" href="{{ config('app.url') }}" target="_blank">
										{{ __(':app_name © :year All right reserved', ['app_name' => config('app.name'), 'year' => date('y')]) }}
									</a>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>			
			</td></tr></tbody>
		</table>
	</body>

</html>