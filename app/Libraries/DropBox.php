<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Cache, Session };


	class DropBox 
	{
		
		public static function list_files(Request $request)
		{			
			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

			if(!$request->keyword)
		  {
		  	$payload = [
			  	'path' 			=> mb_strtolower($request->path ?? ''),
			  	'limit' 		=> (int)$request->limit ?? 1000,
			  	'recursive' => $request->path ? false : true
			  ];

			  $url = "https://api.dropboxapi.com/2/files/list_folder";
		  }
		  else
		  {
		  	$payload = [
			  	'query'	=> mb_strtolower($request->keyword),
			  	'options' => [
			  		'max_results' => (int)$request->limit ?? 1000,
			  		'filename_only' => true,
			  		'file_extensions' => ['zip', 'rar', '7z'],
			  		'path' => mb_strtolower($request->path ?? '')
			  	]
			  ];

			  $url = "https://api.dropboxapi.com/2/files/search_v2";
		  }

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

			if($request->cursor)
			{
				$payload = ['cursor' => $request->cursor];

				$url .= '/continue';
			}

			if(isFolderProcess() && $request->keyword)
			{
				unset($payload['options']['file_extensions']);
			}

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					$files = [];

					if(!$request->keyword)
					{
						foreach($obj_response->entries as &$file)
						{
							if($file->{'.tag'} === (config('filehosts.working_with') === 'files' ? 'file' : 'folder'))
							{
								unset($file->client_modified, 
											$file->content_hash, 
											$file->is_downloadable, 
											$file->path_display, 
											$file->path_lower, 
											$file->rev, 
											$file->server_modified);

								$files[] = $file;
							}
						}
					}
					else
					{
						foreach($obj_response->matches as &$file)
						{
							if($file->metadata->metadata->{'.tag'} === (config('filehosts.working_with') === 'files' ? 'file' : 'folder'))
							{
								unset($file->metadata->metadata->{'.tag'},
											$file->metadata->metadata->client_modified, 
											$file->metadata->metadata->content_hash, 
											$file->metadata->metadata->is_downloadable, 
											$file->metadata->metadata->path_display, 
											$file->metadata->metadata->path_lower, 
											$file->metadata->metadata->rev, 
											$file->metadata->metadata->server_modified, 
											$file->metadata->metadata->sharing_info);

								$files[] = $file->metadata->metadata;
							}
						}
					}

					$cursor 	= $obj_response->cursor ?? null;
					$has_more = $obj_response->has_more ?? null;

					return response()->json(['files_list' => compact('cursor', 'files', 'has_more')]);
				}

				return response()->json(['error' => 'Wrong response from "DropBox::list_files" method']);
			}
		}



		public static function list_folder($folder_id = null)
		{
			if(is_null($folder_id)) abort(404);

			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

	  	$payload = [
		  	'path' 			=> $folder_id,
		  	'limit' 		=> 1000,
		  	'recursive' => false
		  ];

		  $url = "https://api.dropboxapi.com/2/files/list_folder";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					$files = [];

					foreach($obj_response->entries as &$file)
					{
						unset($file->client_modified, 
									$file->content_hash, 
									$file->is_downloadable, 
									$file->path_display, 
									$file->path_lower, 
									$file->rev, 
									$file->server_modified);

						$files[] = (object)['id' => $file->id, 'name' => $file->name, 'mimeType' => pathinfo($file->name, PATHINFO_EXTENSION)];
					}

					return response()->json(['files_list' => compact('files')]);
				}

				return response()->json(['error' => 'Wrong response from "DropBox::list_folder" method']);
			}
		}



		public static function download(string $item_id, string $file_name)
		{
			/*
			curl -X POST https://content.dropboxapi.com/2/files/download \
			--header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAIcKzpYGVbjVhV4o1U3j2yvS3kT2afYQYet6vuFi0awqd" \
			--header "Dropbox-API-Arg: {\"path\": \"id:dJadkgMO-9AAAAAAAAAAGQ\"}"
			*/

    	if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

			$headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: text/plain",
									"Dropbox-API-Arg: {\"path\": \"{$item_id}\"}"];

    	$ch = curl_init("https://content.dropboxapi.com/2/files/download");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);


			if(!$res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_error);
			}

			$finfo = new \finfo(FILEINFO_MIME);
			$mimetype = explode('; ', $finfo->buffer($res))[0] ?? '';

			$extension = config("mimetypes.{$mimetype}", slug($mimetype, '_'));

			return 	response()->streamDownload(
							function() use($res)
							{
						    echo $res;
							}, "{$file_name}.{$extension}");
		}



		public static function get_current_user(Request $request)
		{
			/*curl -X POST https://api.dropboxapi.com/2/users/get_current_account \
    	--header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAIz42oagQNsiNiFys9mK6u5hFlJr8yUY4Z0BxoPGiZ3_b"*/

			$headers = ["Authorization: Bearer {$request->access_token}",
									"Content-Type: application/json; charset=utf-8"];

    	$ch = curl_init("https://api.dropboxapi.com/2/users/get_current_account");

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'null');

			if(!$res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $curl_error);
			}

			return response()->json(json_decode($res));
		}



		public static function get_folder_metadata($folder_id = null)
		{
			/*
				curl -s -X POST https://api.dropboxapi.com/2/files/get_metadata \
		    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAASenSfIluQWture1hVeQ-MmMyYfeBhyZBCd7thxrx-WaS" \
		    --header "Content-Type: application/json" \
		    --data "{\"path\": \"id:dJadkgMO-9AAAAAAAAAKvA\",\"include_media_info\": false,\"include_deleted\": false,\"include_has_explicit_shared_members\": false}"
			*/

		  if(is_null($folder_id)) abort(404);

			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

	  	$payload = [
		  	'path'=> $folder_id,
		  	'include_media_info'	=> false,
		  	'include_deleted' => false,
		  	'include_has_explicit_shared_members' => false
		  ];

		  $url = "https://api.dropboxapi.com/2/files/get_metadata";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					return $obj_response;
				}

				return response()->json(['error' => 'Wrong response from "DropBox::get_folder_metadata" method']);
			}
		}



		public static function get_shared_folder_metadata($shared_folder_id = null)
		{
			/*
				curl -X POST https://api.dropboxapi.com/2/sharing/get_folder_metadata \
		    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAVqbyWdI5c6Ny78tvjlZn6W5QSzCyWSloTJoPJqe7O8Vm" \
		    --header "Content-Type: application/json" \
		    --data "{\"shared_folder_id\": \"7099883200\",\"actions\": []}"
			*/

		  if(is_null($shared_folder_id)) abort(404);

			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

	  	$payload = [
		  	'shared_folder_id'=> $shared_folder_id,
		  	'actions'	=> []
		  ];

		  $url = "https://api.dropboxapi.com/2/sharing/get_folder_metadata";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					return $obj_response;
				}

				return response()->json(['error' => 'Wrong response from "DropBox::get_shared_folder_metadata" method']);
			}
		}




		public static function share_folder($folder_path = null)
		{
			/*
			curl -i -X POST https://api.dropboxapi.com/2/sharing/share_folder \
		    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAVqbyWdI5c6Ny78tvjlZn6W5QSzCyWSloTJoPJqe7O8Vm" \
		    --header "Content-Type: application/json" \
		    --data "{\"path\": \"/valexa/materialize - html & laravel material design admin template\",\"acl_update_policy\": \"editors\",\"force_async\": false,\"member_policy\": \"team\",\"shared_link_policy\": \"team\",\"access_inheritance\": \"inherit\"}"
			*/

		  if(is_null($folder_path)) abort(404);

			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

	  	$payload = [
		  	'path'=> $folder_path,
		  	'acl_update_policy'	=> 'editors',
		  	'force_async' => true,
		  	'member_policy' => 'team',
		  	'shared_link_policy' => 'team',
		  	'access_inheritance' => 'inherit'
		  ];

		  $url = "https://api.dropboxapi.com/2/sharing/share_folder";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					if(property_exists($obj_response, 'async_job_id'))
          {
            $async_job_id = $obj_response->async_job_id;

            $obj_response = Self::check_share_job_status($async_job_id);

            while(($obj_response->{'.tag'} ?? null) !== 'complete')
            {
              sleep(5);

              $obj_response = Self::check_share_job_status($async_job_id);
            }
          }

          return $obj_response;
				}

				return response()->json(['error' => 'Wrong response from "DropBox::share_folder" method']);
			}
		}



		public static function unshare_folder($shared_folder_id = null)
		{
			/*
			curl -X POST https://api.dropboxapi.com/2/sharing/unshare_folder \
	    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAVqbyWdI5c6Ny78tvjlZn6W5QSzCyWSloTJoPJqe7O8Vm" \
	    --header "Content-Type: application/json" \
	    --data "{\"shared_folder_id\": \"7099883200\",\"leave_a_copy\": false}"
			*/

	    if(is_null($shared_folder_id)) abort(404);

			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

	  	$payload = [
		  	'shared_folder_id' => $shared_folder_id,
		  	'leave_a_copy'	=> false
		  ];

		  $url = "https://api.dropboxapi.com/2/sharing/unshare_folder";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					return $obj_response;
				}

				return response()->json(['error' => 'Wrong response from "DropBox::unshare_folder" method']);
			}
		}



		public static function check_job_status($async_job_id = null)
		{
			/*
			curl -X POST https://api.dropboxapi.com/2/sharing/check_job_status \
	    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAVqbyWdI5c6Ny78tvjlZn6W5QSzCyWSloTJoPJqe7O8Vm" \
	    --header "Content-Type: application/json" \
	    --data "{\"async_job_id\": \"dbjid:AABE7Alf-gre72rbhKH_3PrZu-LYcWOe3YGmjh-qv-il0rECprRGqMpYLMQBofei-pEjwVtz8GsaJcqbS9jgr_H3\"}"
			*/

	    if(is_null($async_job_id)) abort(404);

			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

	  	$payload = [
		  	'async_job_id' => $async_job_id
		  ];

		  $url = "https://api.dropboxapi.com/2/sharing/check_job_status";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					var_dump($obj_response);
					return $obj_response;
				}

				return response()->json(['error' => 'Wrong response from "DropBox::check_job_status" method']);
			}
		}



		public static function check_share_job_status($async_job_id = null)
		{
			/*
				curl -X POST https://api.dropboxapi.com/2/sharing/check_share_job_status \
		    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAVqbyWdI5c6Ny78tvjlZn6W5QSzCyWSloTJoPJqe7O8Vm" \
		    --header "Content-Type: application/json" \
		    --data "{\"async_job_id\": \"34g93hh34h04y384084\"}"
			*/


			if(is_null($async_job_id)) abort(404);

			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

	  	$payload = [
		  	'async_job_id' => $async_job_id
		  ];

		  $url = "https://api.dropboxapi.com/2/sharing/check_share_job_status";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

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
					return $obj_response;
				}

				return response()->json(['error' => 'Wrong response from "DropBox::check_share_job_status" method']);
			}
		}



		public static function add_folder_member($shared_folder_id = null, $user_email = null)
		{
			/*
				curl -i -X POST https://api.dropboxapi.com/2/sharing/add_folder_member \
		    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAVqbyWdI5c6Ny78tvjlZn6W5QSzCyWSloTJoPJqe7O8Vm" \
		    --header "Content-Type: application/json" \
		    --data "{\"shared_folder_id\": \"7109721728\",\"members\": [{\"member\": {\".tag\": \"email\",\"email\": \"smithcarolina29@gmail.com\"},\"access_level\": \"viewer\"}],\"quiet\": true}"
			*/

	    if(is_null($shared_folder_id)) abort(404);

			if(! filter_var($user_email, FILTER_VALIDATE_EMAIL)) abort(404);


			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

		  $url = "https://api.dropboxapi.com/2/sharing/add_folder_member";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"shared_folder_id\": \"{$shared_folder_id}\",\"members\": [{\"member\": {\".tag\": \"email\",\"email\": \"{$user_email}\"},\"access_level\": \"viewer\"}],\"quiet\": true}");

			if(! $res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return $curl_error;
			}

			return true;
		}


		public static function remove_folder_member($shared_folder_id = null, $user_email = null)
		{
			/*
				curl -X POST https://api.dropboxapi.com/2/sharing/remove_folder_member \
		    --header "Authorization: Bearer tzAAeuPMwmAAAAAAAAAAVqbyWdI5c6Ny78tvjlZn6W5QSzCyWSloTJoPJqe7O8Vm " \
		    --header "Content-Type: application/json" \
		    --data "{\"shared_folder_id\": \"7109721728\",\"member\": {\".tag\": \"email\",\"email\": \"smithcarolina29@gmail.com\"},\"leave_a_copy\": false}"
			*/


	    if(is_null($shared_folder_id)) abort(404);

			if(! filter_var($user_email, FILTER_VALIDATE_EMAIL)) abort(404);


			if(!$dropbox_access_token = config('filehosts.dropbox.access_token'))
			{
				return response()->json(['error' => 'The access token is missing for DropBox']);
			}

		  $url = "https://api.dropboxapi.com/2/sharing/remove_folder_member";

		  $headers = ["Authorization: Bearer {$dropbox_access_token}", 
									"Content-Type: application/json"];

		  $ch = curl_init($url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"shared_folder_id\": \"{$shared_folder_id}\",\"member\": {\".tag\": \"email\",\"email\": \"{$user_email}\"},\"leave_a_copy\": false}");

			if(! $res = curl_exec($ch))
			{
				$curl_error = curl_error($ch);

				curl_close($ch);

				return $curl_error;
			}

			return $res;
		}



	}