<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Cache, Session };
	use GuzzleHttp\Client;

	class OneDrive 
	{

		// Refresh access token if expiration time is up 
		public static function refresh_access_token()
		{
				if(! config('filehosts.one_drive.enabled'))
				{
					exists_or_abort(null, __('Onedrive is not enabled'));
				}

				$client = new Client();

				$headers = [
					'Content-Type' => 'application/x-www-form-urlencoded',
					'Origin' 			 => config('app.url'),
				];

				$payload = [
					'client_id' 		=> config('filehosts.one_drive.client_id'),
					'redirect_uri' 	=> config('app.url'),
					//'client_secret' => config('filehosts.one_drive.client_secret'),
					'refresh_token' => config('filehosts.one_drive.refresh_token'),
					'grant_type' 		=> 'refresh_token',
				];

				try
				{
					$response = $client->post('https://login.live.com/oauth20_token.srf', ['headers' => $headers, 'form_params' => $payload]);

					if($response->getStatusCode() == 200)
					{
						$response = (string)$response->getBody();
						$response = json_decode($response);

						Self::update_refresh_token($response);

						Cache::put('one_drive_access_token', $response->access_token, now()->addMinutes(55));

						return $response->access_token;
					}
				}
				catch(\GuzzleHttp\Exception\ClientException $e)
				{
					$error = json_decode($e->getResponse()->getBody()->getContents());

					abort(403, "{$error->error} : {$error->error_description}");
				}

				exists_or_abort(null, 'Wrong response from "OneDrive::refresh_access_token" method');
		}



		// Get access token
		public static function get_access_token()
		{
			return cache('one_drive_access_token') ?? Self::refresh_access_token();
		}



		public static function list_files(Request $request)
		{
				$folder = $request->folder ?? config('filehosts.one_drive.folder_id') ?? 'root';

				if(!$access_token = Self::get_access_token())
				{
					return response()->json(['error' => 'Missing access token'], 403);
				}

				$client = new Client();

				$headers = [
					'Authorization'	=> "Bearer {$access_token}",
					'Content-Type' 	=> 'application/x-www-form-urlencoded',
					'Origin' 				=> config('app.url'),
				];

				$payload = [
					'select' 	=> 'id,name,file',
					'$filter' => $request->keyword ? "startsWith(name,'{$request->keyword}')" : ($request->get_folders ? 'file eq null' : null),
					'top' 		=> $request->page_size ?? 20
				];

				$query = http_build_query(array_filter($payload));

				$url = $request->nextLink ?? "https://graph.microsoft.com/v1.0/me/drive/items/{$folder}/children?{$query}";

				try
				{
					$response = $client->get($url, ['headers' => $headers]);

					if($response->getStatusCode() == 200)
					{
						$response = (string)$response->getBody();
						
						if($response = json_decode($response))
						{
							$files_list = (object)[];

							$files_list->nextLink = $response->{'@odata.nextLink'} ?? null;

							foreach($response->value ?? [] as &$item)
							{
								$item->mimeType = null;

								if(property_exists($item, 'file'))
								{
									$item->mimeType = $item->file->mimeType;

									unset($item->file);
								}
							}
						
							$files_list->files = $response->value;

							return response()->json(compact('files_list'));
						}
					}
				}
				catch(\GuzzleHttp\Exception\ClientException $e)
				{
					$error = $e->getResponse()->getBody()->getContents();

					abort(403, $error);
				}
		}



		public static function list_folder($folder_id = null)
		{
				if(!$access_token = $access_token ?? Self::get_access_token())
					exists_or_abort(null, 'Missing access token for OneDrive::get_item method');

				$client = new Client();

				$headers = [
					'Authorization'	=> "Bearer {$access_token}",
					'Origin' 				=> config('app.url'),
				];

				$url = "https://graph.microsoft.com/v1.0/me/drive/items/{$folder_id}/children?select=id,name,file";

				try
				{
					$response = $client->get($url, ['headers' => $headers]);

					if($response->getStatusCode() == 200)
					{
						$response = (string)$response->getBody();
						
						if($response = json_decode($response))
						{
							$files_list = (object)[];

							$files_list->nextLink = $response->{'@odata.nextLink'} ?? null;

							foreach($response->value ?? [] as &$item)
							{
								$item->mimeType = null;

								if(property_exists($item, 'file'))
								{
									$item->mimeType = $item->file->mimeType;

									unset($item->file);
								}
							}

							$files_list->files = $response->value;

							return response()->json(compact('files_list'));
						}
					}
				}
				catch(\GuzzleHttp\Exception\ClientException $e)
				{
					$error = $e->getResponse()->getBody()->getContents();

					abort(403, $error);
				}
		}




		private static function update_refresh_token(object $data)
		{
			if(array_has_columns(get_object_vars($data), ['refresh_token', 'access_token', 'id_token']))
			{
				$settings = \App\Models\Setting::first();

				$files_host = json_decode($settings->files_host);
				$one_drive  = $files_host->one_drive;

				$one_drive->refresh_token = $data->refresh_token;
				$one_drive->access_token 	= $data->access_token;
				$one_drive->id_token			= $data->id_token;

				$files_host->one_drive = $one_drive;

				$settings->files_host = json_encode($files_host);

				$settings->save();		
			}
		}




		public static function download(string $item_id, string $file_name)
		{
				$file_info = Self::get_item($item_id);

				$downloadurl = $file_info->{'@microsoft.graph.downloadUrl'};
				$filename    = $file_info->name;
				$mimetype    = $file_info->file->mimeType;
				$extension   = pathinfo($filename, PATHINFO_EXTENSION) ?? \Illuminate\Http\Testing\MimeType::search($mimetype);

				return 	response()->streamDownload(function() use($downloadurl, $file_name, $extension)
									{
								    echo readfile($downloadurl);
									}, "{$file_name}.{$extension}");
		}




		public static function get_item($item_id, $access_token = null)
		{
				if(!$access_token = $access_token ?? Self::get_access_token())
					exists_or_abort(null, 'Missing access token for OneDrive::get_item method');

				$client = new Client();

				$headers = [
					'Authorization'	=> "Bearer {$access_token}",
					'Origin' 				=> config('app.url'),
				];

				$url = "https://graph.microsoft.com/v1.0/me/drive/items/{$item_id}?select=name,file,@microsoft.graph.downloadUrl";

				try
				{
					$response = $client->get($url, ['headers' => $headers]);

					if($response->getStatusCode() == 200)
					{
						return json_decode($response->getBody());
					}
				}
				catch(\GuzzleHttp\Exception\ClientException $e)
				{
					$error = $e->getResponse()->getBody()->getContents();

					abort(403, $error);
				}
		}

	}