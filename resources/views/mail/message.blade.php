<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<style type="text/css"> 
			@import url('//fonts.googleapis.com/css2?family=Spartan:wght@400;600&display=swap');  
			p, h1, h2, h3, h4, ol, li, ul, th, td, span {  
				font-family: 'Spartan', sans-serif;
				line-height: 1.5;
			} 
		</style>
	</head>
    
	<body>
		<table style="height: 100%; width: 100%; min-height: 500px; background: ghostwhite; padding: 1rem;">
			<tbody><tr><td>
				<table style="max-width: 600px;width: 100%;margin: auto;background: #fff;border-radius: 1rem;padding: 1.5rem;">
					<thead>
						<tr>
							<th><div style="padding: 1rem; margin-bottom: 3rem; font-size: 1.1rem; color: steelblue; background: ghostwhite; border-radius: 100px;">{{ $subject }}</div></th>
						</tr>
					</thead>
					<tbody>
						<tr>
						<td><div style="color: #2c2c2c; font-size: 1rem;">{!! nl2br($text) !!}</div></td>
					</tr>
					</tbody>
					<tfoot>
						<tr>
							<td>
								<div style="margin-top: 4rem; text-align: right; padding: 0 1rem; margin-left: auto; display: table;">
									<a style="text-decoration: none; font-size: .9rem; font-weight: 600; color: #c1c1c1;" href="{{ config('app.url') }}" target="_blank">
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