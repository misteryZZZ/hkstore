<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Cache, Session };

	class GoogleDrive 
	{

		// Refresh access token if expiration time is up 
		public static function refresh_access_token()
		{
			if(! config('filehosts.google_drive'))
			{
				exists_or_abort(null, __('Google drive is not enabled'));
			}

			$headers = ['Host: www.googleapis.com', 
									'Content-Type: application/x-www-form-urlencoded'];

			$payload = http_build_query([
									'client_id' 		=> config("filehosts.google_drive.client_id"),
									'client_secret'	=> config("filehosts.google_drive.secret_id"),
									'refresh_token' => config("filehosts.google_drive.refresh_token"),
									'grant_type' 		=> 'refresh_token'
								]);

			$ch = curl_init("https://www.googleapis.com/oauth2/v4/token");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, urldecode($payload));

			$res = curl_exec($ch);

			if(curl_errno($ch))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $error_msg);
			}
			else
			{
				if($obj_response = json_decode($res))
				{					
					Cache::put('google_drive_access_token', $obj_response->access_token, now()->addMinutes(55));

					return $obj_response->access_token;
				}

				exists_or_abort(null, 'Wrong response from "GoogleDive::refresh_access_token" method');
			}
		}




		// Get access token
		public static function get_access_token()
		{
			return cache('google_drive_access_token') ?? Self::refresh_access_token();
		}





		public static function list_files(Request $request)
		{
			if(!$access_token = Self::get_access_token())
				return response()->json(['error' => 'Missing access token'], 403);

			$headers = ['Content-Type: application/x-www-form-urlencoded',
									"Authorization: Bearer {$access_token}"];

			$q = "(mimeType contains 'zip' or mimeType contains 'rar' or mimeType contains '7z') and trashed=false";

			if(isFolderProcess())
			{
				$q = "(mimeType = 'application/vnd.google-apps.folder') and trashed=false";
			}

			if($request->parent)
			{
				$q .= " and '{$request->parent}' in parents";
			}

			if($request->keyword)
			{
				$q .= " and name contains '{$request->keyword}'";
			}

			$q = str_ireplace(' ', '%20', $q);

			$page_size 	= $request->page_size ?? 1000;

			$http_query = "supportsAllDrives=true&includeItemsFromAllDrives=true&pageSize={$page_size}&q={$q}&fields=files(id,name,mimeType),nextPageToken";

			if($request->nextPageToken)
				$http_query .= "&pageToken={$request->nextPageToken}";

			$ch = curl_init("https://www.googleapis.com/drive/v3/files?$http_query");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error], 403);
			}

			return response()->json(['files_list' => json_decode($res)]);
		}





		private static function get_file(string $file_id)
		{
			if(!$access_token = Self::get_access_token())
				return response()->json(['error' => 'Missing access token'], 403);

			$headers = ['Content-Type: application/x-www-form-urlencoded',
									"Authorization: Bearer {$access_token}"];

			$q = "'{$file_id}' in fileId and trashed=false";

			$q = str_ireplace(' ', '%20', $q);

			$http_query = "supportsAllDrives=true&includeItemsFromAllDrives=true&pageSize=10&q={$q}&fields=files(id,name,mimeType)";

			$ch = curl_init("https://www.googleapis.com/drive/v3/files?$http_query");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error], 403);
			}

			return json_decode($res);
		}





		public static function list_folder($folder_id = null)
		{
			if(is_null($folder_id)) abort(404);

			if(!$access_token = Self::get_access_token())
				return response()->json(['error' => 'Missing access token'], 403);

			$headers = ['Content-Type: application/x-www-form-urlencoded',
									"Authorization: Bearer {$access_token}"];

			$q = "trashed=false and '{$folder_id}' in parents";

			$q = str_ireplace(' ', '%20', $q);

			$http_query = "supportsAllDrives=true&includeItemsFromAllDrives=true&&q={$q}&fields=files(id,name,mimeType)";

			$ch = curl_init("https://www.googleapis.com/drive/v3/files?$http_query");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_error = curl_error($ch);

				curl_close($ch);

				return response()->json(['error' => $curl_error], 403);
			}

			return response()->json(['files_list' => json_decode($res)]);
		}



		public static function code_to_access_token_async(Request $request)
		{
			if(!$request->clientId || !$request->clientSecret || !$request->code)
			{
				$error = 'Either "clientId", "clientSecret" or "code" parameter is missing.';

				return response()->json(['error' => $error]);
			}

			$headers = ['Host: www.googleapis.com', 
									'Content-Type: application/x-www-form-urlencoded'];

			$payload = http_build_query([
									'code' 					=> $request->code,
									'client_id' 		=> $request->clientId,
									'redirect_uri' 	=> env('APP_URL', "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}"),
									'client_secret'	=> $request->clientSecret,
									'grant_type' 		=> 'authorization_code'
								]);

			$ch = curl_init("https://www.googleapis.com/oauth2/v4/token");

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
					if(!isset($obj_response->refresh_token))
					{
						return response()->json(['error' => $res]);
					}

					Cache::put('google_drive_access_token', $obj_response->access_token, now()->addMinutes(55));

					Cache::put('google_drive_refresh_token', $obj_response->refresh_token);

					return response()->json(['refresh_token' => $obj_response->refresh_token]);
				}

				return response()->json(['error' => 'Wrong response from "GoogleDive::code_to_access_token" method']);
			}
		}



		public static function download(string $item_id, string $file_name)
		{
			if(!$access_token = Self::get_access_token())
				exists_or_abort(null, 'Missing access token for GoogleDive::download_alt method');

			$headers = ['Content-Type: application/x-www-form-urlencoded',
									"Authorization: Bearer {$access_token}"];

			$ch = curl_init("https://www.googleapis.com/drive/v3/files/{$item_id}?supportsAllDrives=true&includeItemsFromAllDrives=true&fields=name,id,mimeType,size");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_msg);
			}

			$res = json_decode($res);
			
			$filename = $res->name;
			$filesize = $res->size;
			$mimetype = $res->mimeType;

			$extension = pathinfo($filename, PATHINFO_EXTENSION);

			$chunkSizeBytes = config('filehosts.google_drive.chunk_size', 1) * 1024 * 1024;
    	$chunkStart 		= 0;

			$callback = function() use($filename, $filesize, $mimetype, $item_id, $chunkStart, $chunkSizeBytes, $access_token)
			{
			    $handle = fopen('php://output', 'w');

			    while($chunkStart < $filesize) 
			    {
			        $chunkEnd = $chunkStart + $chunkSizeBytes;

			        $headers = ['Content-Type: application/x-www-form-urlencoded',
			                "Authorization: Bearer {$access_token}",
			                "Range: bytes=$chunkStart-$chunkEnd"];

			        $ch = curl_init("https://www.googleapis.com/drive/v3/files/{$item_id}?alt=media&supportsAllDrives=true&includeItemsFromAllDrives=true");

			        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			        curl_setopt($ch, CURLOPT_HTTPGET, 1);

			        if(!$res = curl_exec($ch))
			        {               
			            $curl_msg = curl_error($ch);

			            curl_close($ch);

			            exists_or_abort(null, $curl_msg);
			        }

			        curl_close($ch);

			        $chunkStart = $chunkEnd + 1;

			        fwrite($handle, $res);
			    }
			    
			    fclose($handle);
			};

			return response()->streamDownload($callback, "{$file_name}.{$extension}", 
								['Content-Type' => $mimetype])->send();
		}


		protected static function share_file(string $file_id, $type = 'anyone')
		{
			if(!$access_token = Self::get_access_token())
				exists_or_abort(null, 'Missing access token for GoogleDive::download_alt method');

			$headers = ['Content-Type: application/json',
									"Authorization: Bearer {$access_token}"];

			$ch = curl_init("https://www.googleapis.com/drive/v3/files/{$file_id}/permissions");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['role' => 'reader', 'type' => 'anyone']));

			if(!$res = curl_exec($ch))
			{				
				$curl_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_msg);
			}

			return json_decode($res);
		}



		public static function get_current_user(Request $request)
		{
			/* GET https://oauth2.googleapis.com/tokeninfo?id_token={id_token} */

			if(!$request->id_token)
				exists_or_abort(null, 'Missing id_token for GoogleDive::get_current_user() method');

			$ch = curl_init("https://oauth2.googleapis.com/tokeninfo?id_token={$request->id_token}");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			
			if(!$res = curl_exec($ch))
			{				
				$curl_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_msg);
			}

			return response()->json(json_decode($res));
		}



		public static function share_folder($item_id = null, $email_address = null)
		{
			if(!$item_id) abort(404);

			if(!is_null($email_address) && !filter_var($email_address, FILTER_VALIDATE_EMAIL)) abort(404);

			if(!$access_token = Self::get_access_token())
				exists_or_abort(null, 'Missing access token for GoogleDive::share_folder method');

			$headers = ['Content-Type: application/json',
									"Authorization: Bearer {$access_token}"];

			$payload = [
				'role' => 'reader',
				'type' => 'anyone'
			];

			if($email_address)
			{
				$payload['emailAddress'] = $email_address;
				$payload['type'] 				 = 'user';
			}

			$ch = curl_init("https://www.googleapis.com/drive/v3/files/{$item_id}/permissions?sendNotificationEmail=false");
			
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

			if(! $res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return $curl_error;
			}

			return json_decode($res);
		}



		public static function unshare_folder($item_id = null, $permission_id = null)
		{
			if(!$item_id || !$permission_id) abort(404);

			if(!$access_token = Self::get_access_token())
				exists_or_abort(null, 'Missing access token for GoogleDive::unshare_folder method');

			$headers = ['Content-Type: application/json',
									"Authorization: Bearer {$access_token}"];

			$ch = curl_init("https://www.googleapis.com/drive/v3/files/{$item_id}/permissions/{$permission_id}");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_POST, 1);

			if(! $res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return $curl_error;
			}

			return true;
		}



	}


