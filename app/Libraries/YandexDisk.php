<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Cache;


	class YandexDisk 
	{

		/** 
		* Get Refresh & Access token and cache them
		* @param Illuminate\Http\Request
		* @return String - refresh token
		**/
		public static function code_to_refresh_token(Request $request)
		{
			if(!$request->clientId || !$request->secretId || !$request->code)
			{
				$error = 'Either "clientId", "SecretId" or "code" parameter is missing.';

				return response()->json(['error' => $error]);
			}

			$payload = http_build_query([
									'code' 					=> $request->code,
									'grant_type' 		=> 'authorization_code'
								]);

			$headers = ['Host: oauth.yandex.com', 
									'Content-Type: application/x-www-form-urlencoded',
									"Authorization: Basic ".base64_encode("{$request->clientId}:{$request->secretId}")];

			$ch = curl_init("https://oauth.yandex.com/token");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode($payload));


			if(!$res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error]);
			}
			else
			{
				if($obj_response = json_decode($res))
				{
					if(! isset($obj_response->refresh_token))
					{
						return response()->json(['error' => json_encode($res)]);
					}

					Cache::forever('yandex_disk_access_token', $obj_response->access_token);
					Cache::forever('yandex_disk_refresh_token', $obj_response->refresh_token);

					return response()->json(['refresh_token' => $obj_response->refresh_token]);
				}

				return response()->json(['error' => 'Wrong response from "YandexDisk::code_to_access_token" method']);
			}
		}



		public static function list_files(Request $request)
		{
			$access_token = cache('yandex_disk_access_token');

			exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

			$headers = ['Host: cloud-api.yandex.net', 
									'Content-Type: application/json',
									"Authorization: OAuth {$access_token}"];

 			$payload = 	[
										'limit' => $request->limit ?? 20,
										'media_type' => 'compressed',
										'fields' => 'name, path, mime_type'
									];

 			if($request->offset)
 			{
 				$payload['offset'] = (int)$request->offset;
 			}

 			if($request->keyword)
 			{
 				$payload['limit'] = 10**10;
 			}

 			$payload = http_build_query($payload);

			$ch = curl_init(urldecode("https://cloud-api.yandex.net/v1/disk/resources/files?{$payload}"));

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);


			if(!$res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error]);
			}
			else
			{
				if($obj_response = json_decode($res))
				{
					if($request->keyword)
					{
						$items = [];

						foreach($obj_response->items as $k => &$item)
						{
							if(preg_match("/{$request->keyword}/i", $item->name))
							{
								$item->id = $item->path;

								$items[] = $item;
							}
						}

						$obj_response->items  = $items;
						$obj_response->offset = null;
					}
					else
					{
						if(count($obj_response->items) < (int)$request->limit)
						{
							$obj_response->offset = null;
						}
						else
						{
							$obj_response->offset = (int)$request->offset + (int)$request->limit;
						}

						$items = [];

						foreach($obj_response->items as $k => &$item)
						{
							$item->id = $item->path;
							
							$items[] = $item;
						}

						$obj_response->items = $items;
					}

					return response()->json(['files_list' => $obj_response]);
				}

				return response()->json(['error' => 'Wrong response from "YandexDisk::list_files" method']);
			}
		}



		public static function download(string $item_path, string $file_name)
		{
			$access_token = cache('yandex_disk_access_token');

			exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

			$headers = ['Host: cloud-api.yandex.net', 
									'Content-Type: application/json',
									"Authorization: OAuth {$access_token}"];

			$ch = curl_init(urldecode("https://cloud-api.yandex.net/v1/disk/resources/download?path={$item_path}"));

			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_msg);
			}

			

			if($obj_response = json_decode($res))
			{
				if(! isset($obj_response->href))
				{
					exists_or_abort(null, "{$res->error} - {$res->error_description}");
				}

				$url = \Spatie\Url\Url::fromString(urldecode($obj_response->href));

				$content_type = $url->getQueryParameter('content_type');
				$filename 	  = $url->getQueryParameter('filename');
	
				if(!$extension = pathinfo($filename, PATHINFO_EXTENSION))
				{
					$extension = config("mimetypes.{$content_type}", slug($content_type, '_'));
				}

				return 	response()->streamDownload(function() use($obj_response)
								{
									$context = [
									    "ssl" => [
									        "verify_peer" => false,
									        "verify_peer_name" => false,
									    ],
									];  

							    readfile($obj_response->href, false, stream_context_create($context));
								}, "{$file_name}.{$extension}");
			}
		}


		/**
		* Test Only
		* Upload files from url to yandex disk
		* @param String - $file_url : external file url
		* @param String - path to where the file will be copied
		* @return String - Url to track upload progression and status
		**/
		public static function upload($file_url, $to_path)
		{
			$access_token = cache('yandex_disk_access_token');

			exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

			$payload = http_build_query([
									'url'		=> $file_url,
									'path'	=> "disk:/{$to_path}"
								]);

			$headers = ['Host: cloud-api.yandex.net',
									'Content-Type: application/x-www-form-urlencoded',
									"Authorization: OAuth {$access_token}"];

			$ch = curl_init("https://cloud-api.yandex.net/v1/disk/resources/upload?{$payload}");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);

			if(!$res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error]);
			}
			else
			{
				if($obj_response = json_decode($res))
				{
					return $obj_response->href;
				}

				abort(403, 'Wrong response from "YandexDisk::upload" method');
			}
		}


		// For Testing Only
		public static function track_upload_operation($operation_url)
		{
			$access_token = cache('yandex_disk_access_token');

			exists_or_abort($access_token, __('Missing access token for Yandex Disk API'));

			$headers = ['Host: cloud-api.yandex.net', 
									'Content-Type: application/json',
									"Authorization: OAuth {$access_token}"];

			$ch = curl_init(urldecode($operation_url));

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_msg);
			}

			if($obj_response = json_decode($res))
			{
				return $obj_response->status;
			}
		}
	}