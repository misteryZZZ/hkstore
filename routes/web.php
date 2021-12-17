<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Http\Request;


Route::get('laravel_log', function(Request $request)
{
  if(!config('app.debug'))
  {
    abort(403, __('Debug mode is not enabled. Please enable it from the administration.'));
  }

  if($request->query('delete'))
  {
    \File::delete(storage_path('logs/laravel.log'));
    
    return redirect('laravel_log');  
  }

  $log = [];

  if(File::exists(storage_path('logs/laravel.log')))
  {
    $log_file = file_get_contents(storage_path('logs/laravel.log'));

    $log = preg_split('/(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/', $log_file, 0, PREG_SPLIT_DELIM_CAPTURE);
    $log = array_filter($log);
    $log = array_chunk($log, 2);
  }

  return view('laravel_log', compact('log'));
});


Route::post('set_locale', function(Request $request)
{
	$url 		= $request->post('redirect', '');
	$locale = $request->post('locale', config('app.locale'));

  if(in_array($locale, \LaravelLocalization::getSupportedLanguagesKeys()))
  {
  		session(["locale" => $locale]);
  }

  return redirect($url);
})->name('set_locale');


Route::get('set_currency', function(Request $request)
{
	$url	= urldecode($request->query('redirect', ''));
	$code = $request->query('code');

	if(in_array(mb_strtoupper($code), array_keys(config('payments.currencies', []))))
	{
			session(["currency" => $code]);
	}

	return redirect($url);
})->name('set_currency');


Route::get('set_template', function(Request $request)
{
	$url	= urldecode($request->query('redirect', ''));

	if(auth_is_admin() || env_is('development'))
	{
		$template = $request->query('template');

		$templates = \File::glob(resource_path('views/front/*', GLOB_ONLYDIR));
	  $base_path = resource_path('views/front/');
	  $templates = str_ireplace($base_path, '', $templates);
    $templates = array_filter($templates, 'is_dir');
    
		if(in_array($template, $templates))
		{
				session(["template" => $template]);
		}
	}

	return redirect($url);
})->name('set_template');


Route::post('admin/settings/check_database_connection', "SettingsController@test_database_connection")
      ->name('settings.check_database_connection');


Route::middleware('app_installed', 'is_blocked', 'set_locale', 'set_exchange_rate', 'set_template', 'affiliate')->group(function()
{
  Route::get('download_file', function(Request $request)
  { 
      $request->get('file') ?? abort(404);
      
      try
      {
        parse_str(decrypt($request->get('file')), $params);
      }
      catch(\Exception $e)
      {
        exists_or_abort(null, $e->getMessage());
      }

      $user_id = $params['user_id'] ?? null;
      $item_id = $params['item_id'] ?? abort(404);
      $user_ip = $params['user_ip'] ?? null;
      $exp     = $params['exp'] ?? null;

      if(!config("app.direct_download_links.enabled"))
      {
        exists_or_abort(null, __('Serving direct download links is not enabled.'));
      }

      if(!cache("direct_download_links.{$item_id}"))
      {
        exists_or_abort(null, __('No direct download link found for this item.'));
      }

      $item = \DB::select("SELECT products.id, products.slug, products.file_host, products.file_name, products.direct_download_link, 
                          products.updated_at, 
                          (IF(product_price.price = 0 OR CURRENT_DATE between substr(products.free, 10, 10) and substr(products.free, 28, 10), 1, 0)) AS is_free
                          FROM products USE INDEX(primary, active)
                          JOIN licenses ON licenses.item_type = products.type AND licenses.regular = 1
                          JOIN product_price ON product_price.license_id = licenses.id AND product_price.product_id = products.id
                          WHERE products.id = ? AND products.active = 1 
                          GROUP BY products.id, products.slug, products.file_host, products.file_name, products.direct_download_link, 
                          products.updated_at, is_free", [$item_id])[0] ?? abort(404);

      if(!$item->file_name)
      {
        return back()->with(['user_message' => __('The file is missing, please contact the support about this issue.')]);
      }

      if(config("app.direct_download_links.authenticated"))
      {
        if(!\Auth::check())
        {
          return redirect()->route('login', ['redirect' => url()->current()]);  
        }

        if(!cache("direct_download_links.{$item_id}.users.{$user_id}") || ($user_id != \Auth::id()))
        {
          exists_or_abort(null, __('You are not allowed to download this file.'));
        }

        if(config("app.direct_download_links.expire_in") > 0 && ($exp < now()->timestamp))
        {
          abort(403, __('This link has expired.'));
        }

        if(!auth_is_admin())
        {
          $item_purchased = \DB::table('transactions')
                                ->whereRaw('products_ids REGEXP ?', [wrap_str($item_id, "'")])
                                ->where(['is_subscription' => 0, 'refunded' => 0, 'status' => 'paid', 'confirmed' => 1])
                                ->where('user_id', $user_id)
                                ->exists();

          if(!$item_purchased)
          {
            $subscription = \DB::select("SELECT user_subscription.id, user_subscription.downloads, subscriptions.limit_downloads,
                              user_subscription.daily_downloads, subscriptions.limit_downloads_per_day, daily_downloads_date, 
                              subscriptions.limit_downloads_same_item, subscription_same_item_downloads.downloads as same_item_downloads,
                              (user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP > user_subscription.ends_at) as expired,
                              (subscriptions.limit_downloads > 0 AND user_subscription.downloads >= subscriptions.limit_downloads) as download_limit_reached,
                              (subscriptions.limit_downloads_per_day > 0 AND user_subscription.daily_downloads >= subscriptions.limit_downloads_per_day AND user_subscription.daily_downloads_date = CURDATE()) as daily_download_limit_reached,
                              (subscriptions.limit_downloads_same_item > 0 AND subscription_same_item_downloads.downloads >= subscriptions.limit_downloads_same_item) as same_items_downloads_reached
                              FROM user_subscription
                              JOIN subscriptions ON user_subscription.subscription_id = subscriptions.id
                              LEFT JOIN transactions ON transactions.id = user_subscription.transaction_id
                              LEFT JOIN subscription_same_item_downloads USE INDEX(product_id, subscription_id) ON (subscription_same_item_downloads.subscription_id = user_subscription.id AND subscription_same_item_downloads.product_id = ?)
                              WHERE (transactions.refunded = 0 OR transactions.refunded IS NULL) AND transactions.confirmed = 1 AND transactions.status = 'paid' AND user_subscription.user_id = ? AND 
                              CASE 
                                WHEN subscriptions.products IS NOT NULL 
                                  THEN FIND_IN_SET(?, subscriptions.products)
                                ELSE 1=1
                              END",
                              [$user_id, $user_id, $item_id])[0] ?? [];

            if(!$subscription)
            {
              return back();
            }
            
            if($subscription->expired)
            {
              return back()->with(['user_message' => __('Your subscription has expired.')]);
            }
            elseif($subscription->download_limit_reached)
            {
              return back()->with(['user_message' => __('Your max download limit is reached for this subscription.')]);
            }
            elseif($subscription->daily_download_limit_reached)
            {
              return back()->with(['user_message' => __('Your max download limit for this day is reached.')]);
            }
            elseif($subscription->same_items_downloads_reached)
            {
              return back()->with(['user_message' => __('Your max download limit for this item is reached.')]);
            }

            if($subscription->limit_downloads_per_day > 0)
            {
              if($subscription->daily_downloads_date && $subscription->daily_downloads_date < date('Y-m-d'))
              {
                \App\Models\User_Subscription::find($subscription->id)->update(['daily_downloads_date' => date('Y-m-d'),
                                                                    'daily_downloads' => 1]);
              }
              else
              {
                \App\Models\User_Subscription::find($subscription->id)->increment('daily_downloads', 1);
              }
            }

            if($subscription->limit_downloads_same_item > 0)
            {
              \DB::insert('INSERT INTO subscription_same_item_downloads (subscription_id, product_id, downloads) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE downloads = downloads + 1', [$subscription->id, $item->id, 1]);
            }

            if($subscription->limit_downloads > 0)
            {
              \App\Models\User_Subscription::find($subscription->id)->increment('downloads', 1);
            }
          }
        }
      }

      if(config("app.direct_download_links.by_ip"))
      {
        if($user_ip != $request->ip())
        {
          exists_or_abort(null, __('Wrong user ip address.'));  
        }

        if(!cache("direct_download_links.{$item_id}.users.{$user_ip}"))
        {
          exists_or_abort(null, __('You are not allowed to download this file.'));
        }

        if(config("app.direct_download_links.expire_in") > 0 && $exp < now()->timestamp)
        {
          abort(403, __('This link has expired.'));
        }
      }    

      if($item->direct_download_link)
      {
          try
          {
            return  response()->streamDownload(function() use($item) 
                    {
                        readfile($item->direct_download_link);
                    }, "{$item->slug}.zip");
          }
          catch(\Exception $e)
          {
            return back_with_errors(['user_message' => __('Invalid direct download url.')]);
          }
      }

      if($item->file_host === 'local')
      {
        $extension = pathinfo($item->file_name, PATHINFO_EXTENSION);

        if(file_exists(storage_path("app/downloads/{$item->file_name}")))
        {
            return response()->download(storage_path("app/downloads/{$item->file_name}"), "{$item->slug}.{$extension}");
        }
      }
      else
      {
        $host_class = [
          'dropbox'   => 'DropBox',
          'google'    => 'GoogleDrive',
          'yandex'    => 'YandexDisk',
          'amazon_s3' => 'AmazonS3',
          'wasabi'    => 'Wasabi'
        ];

        $class_name = $host_class[$item->file_host];

        try
        {
          if(!preg_match('/^amazon_s3|wasabi$/i', $item->file_host))
          {
            return call_user_func_array(["\App\Libraries\\{$class_name}", 'download'], [$item->file_name, $item->slug]);
          }
          else
          {
            return call_user_func_array(["\App\Libraries\\{$class_name}", 'download'], [$item->file_name, null, [], $item->slug, $item->updated_at]);
          }
        }
        catch(\Exception $e)
        {
          
        }
      }
  })
  ->middleware([config("app.direct_download_links.authenticated") ? 'auth' : 'set_locale'])
  ->name('direct_download_link');


  Route::match(['post', 'get'], 'unsubscribe', 'HomeController@unsubscribe_from_newsletter')
  ->name('home.unsubscribe_from_newsletter');

  Route::match(['get', 'post'], 'checkout/webhook', 'CheckoutController@webhook')
  ->name('home.checkout.webhook');

	Auth::routes(['verify' => config('app.email_verification') ? true : false]);

	Route::get('login/{provider}', 'Auth\LoginController@redirectToProvider')
	->where('provider', '^(github|facebook|google|twitter|linkedin|vkontakte)$');


	Route::get('login/{provider}/callback', 'Auth\LoginController@handleProviderCallback')
	->where('provider', '^(github|facebook|google|twitter|linkedin|vkontakte)$');

	Route::post('admin/get_temp_url', "ProductsController@get_temp_url")
	->name('products.get_temp_url');

	Route::post('admin/save_wave', "ProductsController@save_wave")
	->name('products.save_wave');


	Route::get('', 'HomeController@index')
	->name('home');

	Route::get('pay/{token}', 'HomeController@proceed_payment_link')
	->name('home.proceed_payment_link');

	Route::match(['get', 'post'], 'install', 'HomeController@install')
	->name('home.install_app');

	Route::post('user_notifs', 'HomeController@init_notifications')
	->name('home.user_notifs');


	if(config('app.blog.enabled'))
	{
		Route::get('blog', 'HomeController@blog')
		->name('home.blog');

		Route::get('blog/category/{category}', 'HomeController@blog')
		->name('home.blog.category');

		Route::get('blog/tag/{tag}', 'HomeController@blog')
		->name('home.blog.tag');

		Route::get('blog/search', 'HomeController@blog')
		->name('home.blog.q');

		Route::get('blog/{slug}', 'HomeController@post')
		->name('home.post');
	}

  if(config('affiliate.enabled'))
  {
    Route::get('affiliate-program', 'HomeController@affiliate')
    ->name('home.affiliate');
  }

	Route::get('page/{slug}', 'HomeController@page')
	->name('home.page');


  Route::post('items/live_search', 'HomeController@live_search');

	Route::get('items/filter/{filter}', 'HomeController@products')
	->name('home.products.filter')
	->where('filter', '^(free|newest|flash|featured|trending)$');

	Route::get('items/category/{category_slug}/{subcategory_slug?}', 'HomeController@products')
	->name('home.products.category');

	Route::get('items/search', 'HomeController@products')
	->name('home.products.q');
	
	Route::get('item/{id}/{slug}', 'HomeController@product')
	->name('home.product');

	Route::post('save_reaction', 'HomeController@save_reaction')
	->name('home.save_reaction');
	
	Route::post('get_reactions', 'HomeController@get_reactions')
	->name('home.get_reactions');

	
	Route::get('item/{slug}', 'HomeController@old_product_redirect')
	->name('home.old_product');

	Route::match(['post', 'get'], 'support', 'HomeController@support')
	->name('home.support');


	if(config('app.subscriptions.enabled'))
	{
		Route::get('pricing', 'HomeController@subscriptions')
		->name('home.subscriptions');		
	}
	


	Route::post('newsletter', 'HomeController@subscribe_to_newsletter')
	->name('home.newsletter');

	Route::get('unsubscribe/{md5_email}', 'HomeController@unsubscribe_from_newsletter')
	->name('home.unsubscribe');

	Route::post('add_to_cart_async', 'HomeController@add_to_cart_async')
	->name('home.add_to_cart_async');

	Route::post('update_price', 'HomeController@update_price');

	


	Route::get('checkout/failed', 'HomeController@checkout_error')
	->name('home.checkout.error');

	Route::post('item/product_folder', 'HomeController@product_folder_async')
	->name('home.product_folder_async')
	->middleware('valid_folders_config');



	Route::middleware('guest_checkout_allowed')->group(function()
	{	
			if(config('payments.guest_checkout'))
			{
				Route::get('guest', 'HomeController@guest')
				->name('home.guest');

				Route::post('guest/downloads', 'HomeController@guest_downloads')
				->name('home.guest_downloads');

				Route::post('guest/download', 'HomeController@guest_download')
				->name('home.guest_download');
			}


			Route::get('downloads/{id}/{slug}', 'HomeController@product_folder_sync')
			->name('home.product_folder_sync');


			// CHECKOUT
			Route::get('checkout', 'HomeController@checkout')
			->name('home.checkout')
			->middleware('auth_if_subscription');

			Route::get('checkout/offline/reference/{reference}', 'CheckoutController@offline')
			->name('home.checkout.offline');

			Route::post('checkout/offline/reference/{reference}/confirm', 'CheckoutController@offline_confirm')
			->name('home.checkout.offline_confirm');

			Route::match(['post', 'get'], 'checkout/payment/order_completed', 'CheckoutController@order_completed')
			->name('home.checkout.order_completed');

			Route::match(['get', 'post'], 'checkout/save', 'CheckoutController@save')
			->name('home.checkout.save');

			Route::get('checkout/success', 'CheckoutController@success')
			->name('home.checkout.success');

			Route::post('downloads/{id}{slug}/download', 'HomeController@product_folder_sync_download')
			->name('home.product_folder_sync_download');
			
			Route::post('checkout/validate_coupon', 'CheckoutController@validate_coupon')
			->name('home.checkout.validate_coupon');

			Route::post('checkout/payment', 'CheckoutController@payment')
			->name('home.checkout.payment')
			->middleware('valid_checkout_request', 'valid_payment_method');
	});


	Route::match(['get', 'post'], 'checkout/subscription/save', function(Request $request)
	{
		($request->session()->has(['products_ids', 'payment_processor', 'cart']) && $request->isMethod('get') && \Auth::check())
		|| (\Cache::has($request->token) && $request->isMethod('post'))
		|| abort(404);
		
		return call_user_func([new \App\Http\Controllers\CheckoutController(), 'subscription_save'], $request);
	})
	->name('home.subscription.save');


	Route::post('download_license', 'HomeController@download_license')
	->name('home.download_license');


	Route::get('favorites', 'HomeController@favorites')
	->name('home.favorites');


	Route::middleware('auth')->group(function()
	{
		// SUBSCRIPTIONS
		if(config('app.subscriptions.enabled'))
		{
			Route::post('checkout/subscription/payment', 'CheckoutController@payment')
			->name('home.subscription.payment');
		}

		// USER 
		Route::match(['get', 'post'], 'profile', 'HomeController@profile')
		->name('home.profile')
		->middleware('is_not_admin');

		Route::post('send_email_verification_link', 'HomeController@send_email_verification_link')
		->name('home.send_email_verification_link');

		Route::get('subscriptions', 'HomeController@user_subscriptions')
		->name('home.user_subscriptions');

		Route::get('purchases', 'HomeController@purchases')
		->name('home.purchases')
		->middleware('is_not_admin');

		Route::get('invoices', 'HomeController@invoices')
		->name('home.invoices')
		->middleware('is_not_admin');

		Route::post('invoices', 'HomeController@export_invoice')
		->name('home.export_invoice')
		->middleware('is_not_admin');
		
		Route::post('download', 'HomeController@download')
		->name('home.download');

		Route::post('directory/{slug}', 'HomeController@list_folder')
		->name('home.list_folder')
		->middleware('valid_folders_config');

		Route::post('downloads/dropbox_preview_url', 'HomeController@get_dropbox_preview_url')
		->name('home.downloads.dropbox_preview_url');

		Route::get('notifications', 'HomeController@notifications')
		->name('home.notifications');

		Route::post('notifications/read', 'HomeController@notifications_read')
		->name('home.notifications.read');
		
		Route::post('item/{id}/{slug}', 'HomeController@product')
		->name('home.product');
		
		

		Route::middleware('auth', 'is_admin')->group(function()
		{
			// Admin Dashboard
			Route::get('admin', 'DashboardController@index')
			->name('admin');

			Route::post('admin/dashboard', 'DashboardController@update_sales_chart')
			->name('admin.update_sales_chart');

      Route::post('admin/report_errors', 'DashboardController@report_errors')
      ->name('admin.report_errors');


			// Validate Licenses
			Route::post('admin/validate_license', 'LicenseValidatorController@validate_license')
			->name('validate_license');


			// Products
			Route::get('admin/products', 'ProductsController@index')
			->name('products');

			Route::get('admin/products/create', 'ProductsController@create')
			->name('products.create');

			Route::post('admin/products/store', 'ProductsController@store')
			->name('products.store');

			Route::get('admin/products/edit/{id}', 'ProductsController@edit')
			->name('products.edit');

			Route::match(['post', 'get'], 'admin/products/update/{id}', 'ProductsController@update')
			->name('products.update');

			Route::get('admin/products/destroy/{ids}', 'ProductsController@destroy')
			->name('products.destroy');

			Route::match(['get', 'post'], 'admin/products/export/{ids?}', 'ProductsController@export')
			->name('products.export');

			Route::post('admin/products/active', 'ProductsController@active')
			->name('products.active');

			Route::post('admin/products/status', 'ProductsController@status')
			->name('products.status');

			Route::post('admin/products/list_files', 'ProductsController@list_files')
			->name('products.list_files')
			->middleware('valid_files_host');

			Route::post('admin/products/list_folders', 'ProductsController@list_folders')
			->name('products.list_folders')
			->middleware('valid_files_host');

			Route::post('admin/products/get_stock_files/{id}', 'ProductsController@get_stock_files')
			->name('products.get_stock_files');

			Route::post('admin/products/upload_file_async', 'ProductsController@upload_file_async')
			->name('products.upload_file_async');

			Route::post('admin/products/delete_file_async', 'ProductsController@delete_file_async')
			->name('products.delete_file_async');

			Route::post('admin/products/api', "ProductsController@api")
			->name('products.api');


			// Licenses
			Route::get('admin/licenses', 'LicensesController@index')
			->name('licenses');

			Route::get('admin/licenses/create', 'LicensesController@create')
			->name('licenses.create');

			Route::post('admin/licenses/store', 'LicensesController@store')
			->name('licenses.store');

			Route::get('admin/licenses/edit/{id}', 'LicensesController@edit')
			->name('licenses.edit');

			Route::post('admin/licenses/update/{id}', 'LicensesController@update')
			->name('licenses.update');

			Route::get('admin/licenses/destroy/{ids}', 'LicensesController@destroy')
			->name('licenses.destroy');

			Route::match(['get', 'post'], 'admin/licenses/export/{ids?}', 'LicensesController@export')
			->name('licenses.export');

			Route::post('admin/licenses/active', 'LicensesController@active')
			->name('licenses.active');


			// Keys, Accounts ...
			Route::get('admin/keys', 'KeysController@index')
			->name('keys');

			Route::get('admin/keys/create', 'KeysController@create')
			->name('keys.create');

			Route::post('admin/keys/store', 'KeysController@store')
			->name('keys.store');

			Route::get('admin/keys/edit/{id}', 'KeysController@edit')
			->name('keys.edit');

			Route::post('admin/keys/update/{id}', 'KeysController@update')
			->name('keys.update');

			Route::post('admin/keys/update_async', 'KeysController@update_async')
			->name('keys.update_async');
			
			Route::post('admin/keys/void_purchase', 'KeysController@void_purchase')
			->name('keys.void_purchase');

			Route::get('admin/keys/destroy/{ids}', 'KeysController@destroy')
			->name('keys.destroy');

			Route::match(['get', 'post'], 'admin/keys/export/{ids?}', 'KeysController@export')
			->name('keys.export');



			// Pricing table
			Route::get('admin/subscriptions', 'SubscriptionsController@index')
			->name('subscriptions');

			Route::get('admin/subscriptions/create', 'SubscriptionsController@create')
			->name('subscriptions.create');

			Route::post('admin/subscriptions/store', 'SubscriptionsController@store')
			->name('subscriptions.store');

			Route::get('admin/subscriptions/edit/{id}', 'SubscriptionsController@edit')
			->name('subscriptions.edit');

			Route::post('admin/subscriptions/update/{id}', 'SubscriptionsController@update')
			->name('subscriptions.update');

			Route::get('admin/subscriptions/destroy/{ids}', 'SubscriptionsController@destroy')
			->name('subscriptions.destroy');

			Route::match(['get', 'post'], 'admin/subscriptions/export/{ids?}', 'SubscriptionsController@export')
			->name('subscriptions.export');



			// Users Subscriptions
			Route::get('admin/users_subscriptions', 'UserSubscriptionsController@index')
			->name('users_subscriptions');

			Route::get('admin/users_subscriptions/destroy/{ids}', 'UserSubscriptionsController@destroy')
			->name('users_subscriptions.destroy');

			Route::post('admin/users_subscriptions/create_send_renewal_payment_link', 'UserSubscriptionsController@create_send_renewal_payment_link')
			->name('users_subscriptions.sendRenewalPaymentLink');
			



			// Pages
			Route::get('admin/pages', 'PagesController@index')
			->name('pages');

			Route::get('admin/pages/create', 'PagesController@create')
			->name('pages.create');

			Route::post('admin/pages/store', 'PagesController@store')
			->name('pages.store');

			Route::get('admin/pages/edit/{id}', 'PagesController@edit')
			->name('pages.edit');

			Route::post('admin/pages/update/{id}', 'PagesController@update')
			->name('pages.update');

			Route::get('admin/pages/destroy/{ids}', 'PagesController@destroy')
			->name('pages.destroy');

			Route::match(['get', 'post'], 'admin/pages/export/{ids?}', 'PagesController@export')
			->name('pages.export');

			Route::post('admin/pages/active', 'PagesController@status')
			->name('pages.status');



			// Support
			Route::get('admin/support', 'SupportController@index')
			->name('support');

			Route::post('admin/support/reply', 'SupportController@create')
			->name('support.create');

			Route::get('admin/support/destroy/{ids}', 'SupportController@destroy')
			->name('support.destroy');

			Route::match(['get', 'post'], 'admin/support/export/{ids?}', 'SupportController@export')
			->name('support.export');

			Route::post('admin/support/read', 'SupportController@status')
			->name('support.status');



	    // Newsletter
			Route::get('admin/subscribers', 'SubscribersController@index')
			->name('subscribers');

			Route::get('admin/subscribers/create_newsletter', 'SubscribersController@create')
			->name('subscribers.newsletter.create');

			Route::post('admin/subscribers/send_newsletter', 'SubscribersController@send')
			->name('subscribers.newsletter.send');

			Route::get('admin/subscribers/destroy/{ids}', 'SubscribersController@destroy')
			->name('subscribers.destroy');

			Route::post('admin/subscribers/export', 'SubscribersController@export')
			->name('subscribers.export');

			

			// Posts
			Route::get('admin/posts', 'PostsController@index')
			->name('posts');

			Route::get('admin/posts/create', 'PostsController@create')
			->name('posts.create');

			Route::post('admin/posts/store', 'PostsController@store')
			->name('posts.store');

			Route::get('admin/posts/edit/{id}', 'PostsController@edit')
			->name('posts.edit');

			Route::post('admin/posts/update/{id}', 'PostsController@update')
			->name('posts.update');

			Route::get('admin/posts/destroy/{ids}', 'PostsController@destroy')
			->name('posts.destroy');

			Route::match(['get', 'post'], 'admin/posts/export/{ids?}', 'PostsController@export')
			->name('posts.export');

			Route::post('admin/posts/active', 'PostsController@status')
			->name('posts.status');





			// Categories
			Route::get('admin/categories/{for?}', 'CategoriesController@index')
			->name('categories')
			->where('for', '^(posts|products)$');

			Route::get('admin/categories/create', 'CategoriesController@create')
			->name('categories.create');

			Route::post('admin/categories/store', 'CategoriesController@store')
			->name('categories.store');

			Route::get('admin/categories/edit/{id}/{for?}', 'CategoriesController@edit')
			->name('categories.edit')
			->where('for', '^(posts|products)$');

			Route::post('admin/categories/update/{id}/{for?}', 'CategoriesController@update')
			->name('categories.update')
			->where('for', '^(posts|products)$');

			Route::get('admin/categories/destroy/{ids}/{for?}', 'CategoriesController@destroy')
			->name('categories.destroy')
			->where('for', '^(posts|products)$');

			Route::match(['get', 'post'], 'admin/categories/export/{ids?}', 'CategoriesController@export')
			->name('categories.export');



			// Coupons
			Route::get('admin/coupons', 'CouponsController@index')
			->name('coupons');

			Route::get('admin/coupons/create', 'CouponsController@create')
			->name('coupons.create');

			Route::post('admin/coupons/store', 'CouponsController@store')
			->name('coupons.store');

			Route::get('admin/coupons/edit/{id}', 'CouponsController@edit')
			->name('coupons.edit');

			Route::post('admin/coupons/update/{id}', 'CouponsController@update')
			->name('coupons.update');

			Route::get('admin/coupons/destroy/{ids}', 'CouponsController@destroy')
			->name('coupons.destroy');

			Route::match(['get', 'post'], 'admin/coupons/export/{ids?}', 'CouponsController@export')
			->name('coupons.export');

			Route::post('admin/coupons/generate', 'CouponsController@generate')
			->name('coupons.generate');



			// Users
			Route::get('admin/users', 'UsersController@index')
			->name('users');

			Route::get('admin/users/destroy/{ids}', 'UsersController@destroy')
			->name('users.destroy');

			Route::post('admin/users/status', 'UsersController@status')
			->name('users.status');

			Route::post('admin/users/notify', 'UsersController@notify')
			->name('users.notify');

			Route::match(['get', 'post'], 'admin/users/export/{ids?}', 'UsersController@export')
			->name('users.export');
			


			// Payment Links
			Route::get('admin/payment_links', 'PaymentLinksController@index')
			->name('payment_links');

			Route::get('admin/payment_links/create', 'PaymentLinksController@create')
			->name('payment_links.create');

			Route::post('admin/payment_links/store', 'PaymentLinksController@store')
			->name('payment_links.store');

			Route::post('admin/payment_links/send', 'PaymentLinksController@send')
			->name('payment_links.send');

			Route::post('admin/payment_links/item_licenses', 'PaymentLinksController@item_licenses')
			->name('payment_links.item_licenses');

			Route::get('admin/payment_links/destroy/{ids}', 'PaymentLinksController@destroy')
			->name('payment_links.destroy');

			Route::match(['get', 'post'], 'admin/payment_links/export/{ids?}', 'PaymentLinksController@export')
			->name('payment_links.export');




			// Faq
			Route::get('admin/faq', 'FaqController@index')
			->name('faq');

			Route::get('admin/faq/create', 'FaqController@create')
			->name('faq.create');

			Route::post('admin/faq/store', 'FaqController@store')
			->name('faq.store');

			Route::get('admin/faq/edit/{id}', 'FaqController@edit')
			->name('faq.edit');

			Route::post('admin/faq/update/{id}', 'FaqController@update')
			->name('faq.update');

			Route::get('admin/faq/destroy/{ids}', 'FaqController@destroy')
			->name('faq.destroy');

			Route::match(['get', 'post'], 'admin/faq/export/{ids?}', 'FaqController@export')
			->name('faq.export');

			Route::post('admin/faq/active', 'FaqController@status')
			->name('faq.status');



			// Admin Profile
			Route::get('admin/profile', 'AdminProfileController@edit')
			->name('profile.edit');

			Route::post('admin/profile/update', 'AdminProfileController@update')
			->name('profile.update');


			// Licenses Validation
			Route::get('admin/validate-license', 'LicenseValidatorController@index')
			->name('licenses_validation_form');



			// Transactions
			Route::get('admin/transactions', 'TransactionsController@index')
			->name('transactions');

			Route::match(['get', 'post'], 'admin/transactions/export/{ids?}', 'TransactionsController@export')
			->name('transactions.export');

			# Create transaction for offline payment
			Route::get('admin/transactions/create/{for?}', 'TransactionsController@create')
			->name('transactions.create')
			->where('for', '^(|subscriptions)$');

			# Store offline transaction
			Route::post('admin/transactions/store/{for?}', 'TransactionsController@store')
			->name('transactions.store')
			->where('for', '^(|subscriptions)$');

			# Store offline transaction
			Route::post('admin/transactions/store', 'TransactionsController@store')
			->name('transactions.store');

			# Edit offline transaction
			Route::get('admin/transactions/edit/{id}', 'TransactionsController@edit')
			->name('transactions.edit');

			# Update offline transaction
			Route::post('admin/transactions/edit/{id}', 'TransactionsController@update')
			->name('transactions.update');

			# Mark offline transaction as refunded
			Route::get('admin/transactions/{id}/mark_as_refunded', 'TransactionsController@mark_as_refunded')
			->name('transactions.mark_as_refunded');

			# Update transaction Status and Refunded props
			Route::post('admin/transactions/update_prop', 'TransactionsController@update_prop')
			->name('transactions.update_prop');

			# Show transaction details
			Route::get('admin/transactions/show/{id}', 'TransactionsController@show')
			->name('transactions.show');

			# Refund transaction
			Route::post('admin/transactions/refund', 'TransactionsController@refund')
			->name('transactions.refund');

			# Refund Iyzico Transaction
			Route::any('admin/transactions/refund/iyzico/{payment_id}', 'TransactionsController@refund_iyzico')
			->name('transactions.refund_iyzico');

			
			# Remove transaction
			Route::get('admin/transactions/destroy/{ids}', 'TransactionsController@destroy')
			->name('transactions.destroy');





			// Comments
			Route::get('admin/comments', 'CommentsController@index')
			->name('comments');

			Route::post('admin/comments/approve', 'CommentsController@status')
			->name('comments.status');

			Route::get('admin/comments//destroy/{ids}', 'CommentsController@destroy')
			->name('comments.destroy');

			Route::match(['get', 'post'], 'admin/comments/export/{ids?}', 'CommentsController@export')
			->name('comments.export');



      // Affiliate
      Route::get('admin/affiliate/balances', 'CashoutsController@balances')
      ->name('affiliate.balances');

      Route::get('admin/affiliate/balances/destroy/{ids}', 'CashoutsController@destroy_balances')
      ->name('affiliate.destroy_balances');

      Route::get('admin/affiliate/cashouts', 'CashoutsController@cashouts')
      ->name('affiliate.cashouts');

      Route::get('admin/affiliate/cashouts/destroy/{ids}', 'CashoutsController@destroy_cashouts')
      ->name('affiliate.destroy_cashouts');

      Route::post('admin/affiliate/mark_as_paid', 'CashoutsController@mark_as_paid')
      ->name('affiliate.mark_as_paid');

      Route::post('admin/affiliate/transfer_to_paypal', 'CashoutsController@transfer_to_paypal')
      ->name('affiliate.transfer_to_paypal');



			// Reviews
			Route::get('admin/reviews', 'ReviewsController@index')
			->name('reviews');

			Route::post('admin/reviews/approve', 'ReviewsController@status')
			->name('reviews.status');

			Route::get('admin/reviews/destroy/{ids}', 'ReviewsController@destroy')
			->name('reviews.destroy');

						Route::match(['get', 'post'], 'admin/reviews/export/{ids?}', 'ReviewsController@export')
			->name('reviews.export');


			// Reviews
			Route::get('admin/searches', 'SearchesController@index')
			->name('searches');

			Route::get('admin/searches/destroy/{ids}', 'SearchesController@destroy')
			->name('searches.destroy');
			
			Route::match(['get', 'post'], 'admin/searches/export/{ids?}', 'SearchesController@export')
			->name('searches.export');


			// Admin notification
			Route::get('admin/admin-notifs', 'AdminNotifsController@index')
			->name('admin_notifs');
			
			Route::post('admin/admin-notifs/mark_as_read', 'AdminNotifsController@mark_as_read')
			->name('admin_notifs.mark_as_read');

			


			// Settings
      Route::post('admin/settings/clear_cache', "SettingsController@clear_cache");

			Route::get('admin/settings/{settings_name}', "SettingsController@index")
			->where('settings_name', 
              '^(bulk_upload|affiliate|general|cache|payments|adverts|search_engines|mailer|files_host|social_login|adverts|chat|translations|captcha|database)$')
			->name('settings');

			Route::post('admin/settings/{settings_name}/update', "SettingsController@update")
			->where('settings_name', '^(bulk_upload|affiliate|general|payments|adverts|search_engines|mailer|files_host|social_login|adverts|chat|translations|captcha|database)$')
			->name('settings.update');

			Route::post('admin/settings/check_mailer_connection', "SettingsController@check_mailer_connection")
			->name('settings.check_mailer_connection');

			Route::post('admin/settings/remove_search_cover', "SettingsController@remove_search_cover")
			->name('settings.remove_search_cover');

			Route::post('admin/settings/files_host/google_drive_get_refresh_token', "SettingsController@google_drive_get_refresh_token")
			->name('google_drive_get_refresh_token');

			Route::post('admin/settings/files_host/google_drive_get_current_user', "SettingsController@google_drive_get_current_user")
			->name('google_drive_get_current_user');

			Route::post('admin/settings/files_host/one_drive_get_refresh_token', "SettingsController@one_drive_get_refresh_token")
			->name('one_drive_get_refresh_token');

			Route::post('admin/settings/files_host/one_drive_get_current_user', "SettingsController@one_drive_get_current_user")
			->name('one_drive_get_current_user');

			Route::post('admin/settings/files_host/dropbox_get_current_user', "SettingsController@dropbox_get_current_user")
			->name('dropbox_get_current_user');

			Route::post('admin/settings/files_host/yandex_disk_get_refresh_token', "SettingsController@yandex_disk_get_refresh_token")
			->name('yandex_disk_get_refresh_token');

			Route::post('admin/settings/files_host/test_amazon_s3_connection', "SettingsController@test_amazon_s3_connection")
			->name('test_amazon_s3_connection');

			Route::post('admin/settings/files_host/test_wasabi_connection', "SettingsController@test_wasabi_connection")
			->name('test_wasabi_connection');

			Route::post('admin/settings/translations/get_translation', "SettingsController@get_translation")
			->name('get_translation');			
		});

	});
});


Route::get('admin_login/{token}', "DashboardController@admin_login")
->name('admin_login');