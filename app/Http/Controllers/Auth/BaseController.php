<?php
	
	namespace App\Http\Controllers\Auth;

  use App\Http\Controllers\Controller;


	class BaseController extends Controller 
  {
		public $meta_data = [];

    public function __construct()
    {
      $this->meta_data = (object)['name'        => config('app.name'),
                                  'title'       => config('app.title'),
                                  'description' => config('app.description'), 
                                  'url'         => url()->full(),
                                  'fb_app_id'   => config('app.fb_app_id'),
                                  'image'       => asset('storage/images/'.(config('app.cover') ?? 'cover.jpg'))];
    }
	}