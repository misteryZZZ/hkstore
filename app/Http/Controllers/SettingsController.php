<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{ Setting, Category, Product, Product_Price, License };
use Illuminate\Support\{ Arr, Facades\DB, Facades\File, Facades\Config };
use App\Libraries\{ GoogleDrive, DropBox, YandexDisk, AmazonS3, Wasabi };
use League\Csv\Reader;
use League\Csv\Statement;


class SettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      if(strtolower($request->settings_name) === 'translations')
      {
        $langs = File::directories(resource_path('lang/'));
        $langs = array_filter(preg_replace('/.+(\/|\\\)([\w-]+)$/i', '$2', $langs));
        $base = json_decode(File::get(resource_path("lang/en.json")), true);

        foreach($base as $k => $v)
        {
          $base[$k] = preg_replace('/(:[a-z_%]+)/i', '<span class="param">$1</span>', $k);
        }

        $base = array_flip($base);

        view()->share(compact('base', 'langs'));

        $settings = [];
      }
      elseif(strtolower($request->settings_name) == 'cache')
      {
         $settings = [];
      }
      elseif(strtolower($request->settings_name) == 'bulk_upload')
      {
        $settings = (object)['columns' => config('app.bulk_upload_columns', [])];
      }
      else
      {
        $settings = Setting::select($request->settings_name)->first()->{$request->settings_name};

        $settings = json_decode($settings) ?? (object)[];

        if(strtolower($request->settings_name) === 'general')
        {
          $templates = glob(resource_path('views/front/*', GLOB_ONLYDIR));
          $templates = array_filter($templates, 'is_dir');

          $base_path = resource_path('views/front/');
          $templates = str_ireplace($base_path, '', $templates);

          $langs = File::directories(resource_path('lang/'));
          $langs = array_filter(preg_replace('/.+(\/|\\\)([\w-]+)$/i', '$2', $langs));

          view()->share(['langs' => $langs, 'templates' => $templates]);

          $settings->homepage_items = json_decode($settings->homepage_items ?? null);
          $settings->maintenance    = json_decode($settings->maintenance ?? null);
          $settings->auto_approve   = json_decode($settings->auto_approve ?? null); 
          $settings->admin_notifications = json_decode($settings->admin_notifications ?? null);
          $settings->cookie         = json_decode($settings->cookie ?? null);
          $settings->subscriptions  = $settings->subscriptions ?? null;
          $settings->subscriptions  = is_object($settings->subscriptions) ? $settings->subscriptions : json_decode($settings->subscriptions);
        }
        elseif(strtolower($request->settings_name) === 'payments')
        {
          $payments_conf = include(config_path("payments.php"));

          view()->share('currencies', $payments_conf['currencies']);

          $settings->pay_what_you_want = json_decode($settings->pay_what_you_want);        
        }
      }

      return view("back.settings.index", ['view' => $request->settings_name, 'settings' => $settings]);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
      if(file_exists(app()->getCachedConfigPath()))
        unlink(app()->getCachedConfigPath());
      
      call_user_func("Self::update_{$request->settings_name}", $request);

      return redirect()->route('settings', $request->settings_name)->withInput()->with(['settings_message' => __('Changes saved')]);
    }



    private static function update_general(Request $request)
    {
      $templates = implode(',', File::glob(resource_path('views/front/*', GLOB_ONLYDIR)));

      $base_path = resource_path('views/front/');

      $templates = str_ireplace($base_path, '', $templates);

      $request->validate([
        'name'            => 'nullable|string',
        'title'           => 'nullable|string',
        'description'     => 'nullable|string',
        'email'           => 'nullable|email',
        'keywords'        => 'nullable|string',
        'items_per_page'  => 'nullable|min:0|integer',
        'favicon'         => 'nullable|image',
        'logo'            => 'nullable|image',
        'cover'           => 'nullable|image',
        'watermark'       => 'nullable',
        'top_cover'       => 'nullable',
        'search_header'   => 'nullable|string',
        'masonry_layout'  => 'nullable|string|in:0,1',
        'search_subheader'  => 'nullable|string',
        'blog.title'        => 'nullable|string',
        'blog.description'  => 'nullable|string',
        'blog.enabled'   => 'nullable|in:0,1',
        'products_by_country_city' => 'nullable|in:0,1',
        'subscriptions.enabled' => 'nullable|in:0,1',
        'subscriptions.accumulative' => 'nullable|in:0,1',
        'env'             => ['regex:/^(production|development)$/i', 'required'],
        'debug'           => 'boolean|required',
        'facebook'        => 'nullable|string',
        'twitter'         => 'nullable|string',
        'pinterest'       => 'nullable|string',
        'youtube'         => 'nullable|string',
        'tumblr'          => 'nullable|string',
        'fb_app_id'       => 'nullable|string',
        'cookie'          => 'nullable|array',
        'template'        => "required|string|in:{$templates}",
        'timezone'        => ['required', \Illuminate\Validation\Rule::in(array_keys(config('app.timezones')))],
        'fonts'           => 'nullable|array',
        'top_cover_color' => 'nullable|string',
        'top_cover_mask'  => 'nullable',
        'users_notif'     => 'nullable|string',
        'recently_viewed_items' => 'nullable|string:in:0,1',
        'admin_notifications' => 'nullable|array',
        'admin_notifications.comments' => 'nullable|string|in:0,1',
        'admin_notifications.reviews' => 'nullable|string|in:0,1',
        'admin_notifications.sales' => 'nullable|string|in:0,1',
        'purchase_code'   => 'required|string',
        'email_verification' => 'nullable|in:0,1',
        'maintenance.enabled' => 'nullable|in:0,1',
        'maintenance.expires_at' => 'nullable',
        'maintenance.title' => 'nullable|string',
        'maintenance.header' => 'nullable|string',
        'maintenance.subheader' => 'nullable|string',
        'maintenance.text' => 'nullable|string',
        'maintenance.bg_color' => 'nullable|string',
        'maintenance.auto_disable' => 'nullable|string|in:0,1',
        'auto_approve.*' => 'nullable|array',
        'auto_approve.reviews' => 'nullable|string|in:0,1',
        'auto_approve.support' => 'nullable|string|in:0,1',
        'default_product_type' => 'nullable|string',
        'homepage_items' => 'array|nullable',
        'randomize_homepage_items' => 'nullable|string|in:0,1',
        'langs'           => ['nullable', function($attribute, $value, $fail)
                                          {
                                            if($langs = array_filter(explode(',', $value)))
                                            {
                                              foreach($langs as $lang)
                                              {
                                                if($lang !== 'en' && !is_file(resource_path("lang/{$lang}.json")))
                                                {
                                                  File::put(resource_path("lang/{$lang}.json"), '{}');
                                                }
                                              }
                                            }
                                          }],
      ]);


      $settings = Setting::first();

      $general_settings = json_decode($settings->general) ?? new \stdClass;

      $general_settings->langs          = $request->post('langs', config('app.locale'));
      $general_settings->name           = $request->name;
      $general_settings->title          = $request->title;
      $general_settings->description    = $request->description;
      $general_settings->email          = $request->email;
      $general_settings->keywords       = $request->keywords;
      $general_settings->items_per_page = $request->items_per_page;
      $general_settings->env            = $request->env;
      $general_settings->template       = $request->template;
      $general_settings->debug          = $request->debug;
      $general_settings->masonry_layout = $request->masonry_layout;
      $general_settings->timezone       = $request->timezone;
      $general_settings->facebook       = $request->facebook;
      $general_settings->twitter        = $request->twitter;
      $general_settings->pinterest      = $request->pinterest;
      $general_settings->youtube        = $request->youtube;
      $general_settings->tumblr         = $request->tumblr;
      $general_settings->fb_app_id      = $request->fb_app_id;
      $general_settings->search_header    = $request->search_header;
      $general_settings->search_subheader = $request->search_subheader;
      $general_settings->blog             = $request->blog;
      $general_settings->products_by_country_city = $request->products_by_country_city;
      $general_settings->subscriptions    = json_encode($request->subscriptions);
      $general_settings->users_notif      = $request->users_notif ?? '';
      $general_settings->admin_notifications   = json_encode($request->admin_notifications);
      $general_settings->purchase_code         = $request->purchase_code;
      $general_settings->email_verification    = $request->email_verification;
      $general_settings->fonts                 = $request->fonts;
      $general_settings->maintenance           = json_encode($request->maintenance);
      $general_settings->auto_approve          = json_encode($request->auto_approve);
      $general_settings->homepage_items        = json_encode($request->homepage_items);
      $general_settings->default_product_type  = $request->default_product_type ?? '-';
      $general_settings->recently_viewed_items = $request->recently_viewed_items;
      $general_settings->randomize_homepage_items = $request->randomize_homepage_items;
      $general_settings->direct_download_links = $request->direct_download_links;
      
      $cookie = [
        'text'       => $request->input('cookie.text'),
        'background' => $request->input('cookie.background.raw') ?? $request->input('cookie.background.picker'),
        'color'      => $request->input('cookie.color.raw') ?? $request->input('cookie.color.picker'),
        'button_bg'  => $request->input('cookie.button_bg.raw') ?? $request->input('cookie.button_bg.picker')
      ];

      $general_settings->cookie = mb_strlen(strip_tags($request->input('cookie.text'))) ? json_encode($cookie) : null;



      if(env('PURCHASE_CODE') !== $request->purchase_code)
      {
        update_env_var('PURCHASE_CODE', wrap_str($request->purchase_code));
      }

      if($favicon = $request->file('favicon'))
      {
        $ext = $favicon->getClientOriginalExtension();
        
        if($favicon = $request->favicon->storeAs('images', "favicon.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/favicon.*')) as $_favicon)
          {
            if(pathinfo($_favicon, PATHINFO_BASENAME) != "favicon.{$ext}")
            {
              @unlink($_favicon);
              break;
            }
          }

          $general_settings->favicon = pathinfo($favicon, PATHINFO_BASENAME);
        }
      }

      if($logo = $request->file('logo'))
      {
        $ext = $logo->getClientOriginalExtension();
        
        if($logo = $logo->storeAs('images', "{$request->template}_logo.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path("storage/images/{$request->template}_logo.*")) as $_logo)
          {
            if(pathinfo($_logo, PATHINFO_BASENAME) != "{$request->template}_logo.{$ext}")
            {
              @unlink($_logo);
              break;
            }
          }

          $general_settings->logo = pathinfo($logo, PATHINFO_BASENAME);
        }
      }



      if($cover = $request->file('cover'))
      {
        $ext = $cover->getClientOriginalExtension();

        if($cover = $request->cover->storeAs('images', "cover.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/cover.*')) as $_cover)
          {
            if(pathinfo($_cover, PATHINFO_BASENAME) != "cover.{$ext}")
            {
              @unlink($_cover);
              break;
            }
          }

          $general_settings->cover = pathinfo($cover, PATHINFO_BASENAME);
        }
      }


      if($watermark = $request->file('watermark'))
      {
        $ext = $watermark->getClientOriginalExtension();

        if($watermark = $watermark->storeAs('images', "watermark.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/watermark.*')) as $_watermark)
          {
            if(pathinfo($_watermark, PATHINFO_BASENAME) != "watermark.{$ext}")
            {
              @unlink($_watermark);
              break;
            }
          }

          $general_settings->watermark = pathinfo($watermark, PATHINFO_BASENAME);
        }
      }
      elseif(!$request->post('watermark'))
      {
        $watermark_path = public_path("storage/images/{$general_settings->watermark}");

        if(is_file($watermark_path))
        {
          File::delete($watermark_path);

          $general_settings->watermark = null;
        }
      }


      if($top_covers = $request->file('top_cover'))
      {
        foreach($top_covers as $k => $top_cover)
        {
          $ext = $top_cover->getClientOriginalExtension();

          if($top_cover = $top_cover->storeAs('images', "{$k}_top_cover.{$ext}", ['disk' => 'public']))
          {
            foreach(glob(public_path("storage/images/{$k}_top_cover.*")) as $_top_cover)
            {
              if(pathinfo($_top_cover, PATHINFO_BASENAME) != "{$k}_top_cover.{$ext}")
              {
                @unlink($_top_cover);
                break;
              }

              $general_settings->{"{$k}_top_cover"} = pathinfo($top_cover, PATHINFO_BASENAME);
            }
          }
        }
      }
      else
      {
        foreach(['valexa', 'tendra', 'default'] as $name)
        {
          if(!$top_cover = $request->input("top_cover.{$name}"))
          {
            $attr = "{$name}_top_cover";
            $top_cover_path = public_path("storage/images/{$general_settings->$attr}");

            if(is_file($top_cover_path))
            {
              File::delete($top_cover_path);

              $general_settings->$attr = null;
            }
          }
        }
      }


      if($top_cover_masks = $request->file('top_cover_mask'))
      {
        foreach($top_cover_masks as $k => $top_cover_mask)
        {
          $ext = $top_cover_mask->getClientOriginalExtension();

          if($top_cover_mask = $top_cover_mask->storeAs('images', "{$k}_top_cover_mask.{$ext}", ['disk' => 'public']))
          {
            foreach(glob(public_path("storage/images/{$k}_top_cover_mask.*")) as $_top_cover_mask)
            {
              if(pathinfo($_top_cover_mask, PATHINFO_BASENAME) != "{$k}_top_cover_mask.{$ext}")
              {
                @unlink($_top_cover_mask);
                break;
              }

              $general_settings->{"{$k}_top_cover_mask"} = pathinfo($top_cover_mask, PATHINFO_BASENAME);
            }
          }
        }
      }
      else
      {
        foreach(['valexa', 'tendra', 'default'] as $name)
        {
          if(!$top_cover_mask = $request->input("top_cover_mask.{$name}"))
          {
            $attr = "{$name}_top_cover_mask";
            $top_cover_path = public_path("storage/images/".($general_settings->$attr ?? null));

            if(is_file($top_cover_path))
            {
              File::delete($top_cover_path);

              $general_settings->$attr = null;
            }
          }
        }
      }


      if($blog_cover = $request->file('blog_cover'))
      {
        $ext = $blog_cover->getClientOriginalExtension();

        if($blog_cover = $blog_cover->storeAs('images', "blog_cover.{$ext}", ['disk' => 'public']))
        {
          foreach(glob(public_path('storage/images/blog_cover.*')) as $_blog_cover)
          {
            if(pathinfo($_blog_cover, PATHINFO_BASENAME) != "blog_cover.{$ext}")
            {
              @unlink($_blog_cover);
              break;
            }
          }

          $general_settings->blog_cover = pathinfo($blog_cover, PATHINFO_BASENAME);
        }
      }

      $settings->general = json_encode($general_settings);

      $settings->save();
    }




    private static function update_mailer(Request $request)
    {
      $request->validate([
        'mailer.mail.username'        => 'email|required|bail',
        'mailer.mail.host'            => 'required',
        'mailer.mail.password'        => 'required',
        'mailer.mail.port'            => 'required',
        'mailer.mail.encryption'      => 'required',
        'mailer.mail.reply_to'        => 'nullable|email',
        'mailer.mail.forward_to'      => 'nullable|string',
        'mailer.mail.use_queue'       => 'nullable|in:0,1'
      ]);
      
      $mailer = $request->mailer;

      $mailer['mail']['forward_to'] = preg_replace('/\s+/i', '', $mailer['mail']['forward_to']);
      
      $mailer['mail']['from'] = ['name' => config('app.name'), 'address' => $request->mailer['mail']['username'] ?? 'example@gmail.com'];

      Setting::first()->update(['mailer' => json_encode($mailer)]);
    }



    private static function update_affiliate(Request $request)
    {
      $request->validate([
        'affiliate.enabled' => 'string|nullable|in:0,1',
        'affiliate.commission' => 'numeric|gt:0|nullable|required_if:affiliate.enabled,1',
        'affiliate.expire' => 'numeric|gt:0|nullable|required_if:affiliate.enabled,1',
        'affiliate.cashout_description' => 'string|nullable|required_if:affiliate.enabled,1',
        'affiliate.cashout_methods' => 'string|nullable|required_if:affiliate.enabled,1',
        'affiliate.minimum_cashout.bank_transfer' => 'nullable|numeric',
        'affiliate.minimum_cashout.paypal' => 'nullable|numeric'
      ],
      ['required_if' => __(':attribute is required if affiliate is enabled.')]);

      Setting::first()->update(['affiliate' => json_encode($request->affiliate)]);
    }




    public function check_mailer_connection(Request $request)
    {
      $request->validate([
        'mailer.mail.username'        => 'email|required|bail',
        'mailer.mail.host'            => 'required',
        'mailer.mail.password'        => 'required',
        'mailer.mail.port'            => 'required',
        'mailer.mail.encryption'      => 'required'
      ]);

      try
      {
          $transport = new \Swift_SmtpTransport($request->input('mailer.mail.host'), 
                                                $request->input('mailer.mail.port'), 
                                                $request->input('mailer.mail.encryption'));
          
          $transport->setUsername($request->input('mailer.mail.username'));
          $transport->setPassword($request->input('mailer.mail.password'));

          $mailer = new \Swift_Mailer($transport);

          $mailer->getTransport()->start();

          return response()->json(['status' => true, 'message' => __('Success.')]);
      } 
      catch(\Swift_TransportException $e) 
      {
          return response()->json(['status' => false, 'message' => $e->getMessage()]);
      }
      catch(\Exception $e) 
      {
          return response()->json(['status' => false, 'message' => $e->getMessage()]);
      }
    }



    private static function update_payments(Request $request)
    {      
      $request->validate([
        'paypal.enabled'    => 'nullable|in:on',
        'paypal.mode'       => ['in:live,sandbox', 'nullable', 'required_with:paypal.enabled'],
        'paypal.client_id'  => 'string|nullable|required_with:paypal.enabled',
        'paypal.secret_id'  => 'string|nullable|required_with:paypal.enabled',
        'paypal.fee'        => 'numeric|nullable',
        'paypal.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'stripe.enabled'    => 'nullable|in:on',
        'stripe.mode'       => ['in:live,sandbox', 'nullable', 'required_with:stripe.enabled'],
        'stripe.client_id'  => 'string|nullable|required_with:stripe.enabled',
        'stripe.secret_id'  => 'string|nullable|required_with:stripe.enabled',
        'stripe.fee'        => 'numeric|nullable',
        'stripe.method_types' => 'nullable',
        'stripe.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'skrill.enabled'    => 'nullable|in:on',
        'skrill.merchant_account'  => 'string|nullable|required_with:skrill.enabled',
        'skrill.mqiapi_secret_word' => 'string|nullable|required_with:skrill.enabled',
        'skrill.mqiapi_password'    => 'string|nullable|required_with:skrill.enabled',
        'skrill.methods'    => 'nullable|string',
        'skrill.fee'        => 'numeric|nullable',
        'skrill.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'razorpay.enabled'    => 'nullable|in:on',
        'razorpay.client_id'  => 'string|nullable|required_with:razorpay.enabled',
        'razorpay.secret_id'  => 'string|nullable|required_with:razorpay.enabled',
        'razorpay.webhook_secret' => 'string|nullable|required_with:razorpay.enabled',
        'razorpay.fee'        => 'numeric|nullable',
        'razorpay.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'iyzico.enabled'      => 'nullable|in:on',
        'iyzico.mode'         => ['in:live,sandbox', 'nullable', 'required_with:iyzico.enabled'],
        'iyzico.client_id'    => 'string|nullable|required_with:iyzico.enabled',
        'iyzico.secret_id'    => 'string|nullable|required_with:iyzico.enabled',
        'iyzico.fee'          => 'numeric|nullable',
        'iyzico.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'coingate.enabled'    => 'nullable|in:on',
        'coingate.mode'       => ['in:live,sandbox', 'nullable', 'required_with:coingate.enabled'],
        'coingate.auth_token' => 'string|nullable|required_with:coingate.enabled',
        'coingate.receive_currency' => 'nullable|string|between:3,3',
        'coingate.fee'          => 'numeric|nullable',
        'coingate.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'spankpay.enabled'    => 'nullable|in:on',
        'spankpay.public_key' => 'string|nullable|required_with:spankpay.enabled',
        'spankpay.secret_key' => 'string|nullable|required_with:spankpay.enabled',
        'spankpay.fee'          => 'numeric|nullable',
        'spankpay.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'midtrans.enabled'      => 'nullable|in:on',
        'midtrans.mode'         => ['in:live,sandbox', 'nullable', 'required_with:midtrans.enabled'],
        'midtrans.client_key'    => 'string|nullable|required_with:midtrans.enabled',
        'midtrans.server_key'    => 'string|nullable|required_with:midtrans.enabled',
        'midtrans.merchant_id'  => 'string|nullable|required_with:midtrans.enabled',
        'midtrans.methods' => 'nullable|string|required_with:midtrans.enabled',
        'midtrans.fee'          => 'numeric|nullable',
        'midtrans.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'paystack.enabled'    => 'nullable|in:on',
        'paystack.secret_key' => 'string|nullable|required_with:paystack.enabled',
        'paystack.public_key' => 'string|nullable|required_with:paystack.enabled',
        'paystack.fee'        => 'numeric|nullable',
        'paystack.channels' => 'string|required_with:paystack.enabled',
        'paystack.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'adyen.enabled'    => 'nullable|in:on',
        'adyen.mode'       => 'in:live,sandbox|nullable|required_with:adyen.enabled',
        'adyen.api_key'    => 'string|nullable|required_with:adyen.enabled',
        'adyen.client_key' => 'string|nullable|required_with:adyen.enabled',
        'adyen.merchant_account' => 'string|nullable|required_with:adyen.enabled',
        'adyen.hmac_key' => 'string|nullable|required_with:adyen.enabled',
        'adyen.fee'        => 'numeric|nullable',
        'adyen.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'instamojo.enabled'    => 'nullable|in:on',
        'instamojo.mode'       => 'in:live,sandbox|nullable|required_with:instamojo.enabled',
        'instamojo.private_api_key' => 'string|nullable|required_with:instamojo.enabled',
        'instamojo.private_auth_token' => 'string|nullable|required_with:instamojo.enabled',
        'instamojo.private_salt' => 'string|nullable|required_with:instamojo.enabled',
        'instamojo.fee'        => 'numeric|nullable',
        'instamojo.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'offline.enabled' => 'nullable|in:on',
        'offline.instructions' => 'string|nullable',
        'offline.fee'        => 'numeric|nullable',
                    #---------------
        'payhere.enabled'    => 'nullable|in:on',
        'payhere.mode'       => 'in:live,sandbox|string|nullable|required_with:payhere.enabled',
        'payhere.merchant_secret' => 'string|nullable|required_with:payhere.enabled',
        'payhere.merchant_id' => 'string|nullable|required_with:payhere.enabled',
        'payhere.fee'          => 'numeric|nullable',
        'payhere.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'coinpayments.enabled'    => 'nullable|in:on',
        'coinpayments.mode'       => 'in:live,sandbox|string|nullable|required_with:coinpayments.enabled',
        'coinpayments.private_key' => 'string|nullable|required_with:coinpayments.enabled',
        'coinpayments.public_key' => 'string|nullable|required_with:coinpayments.enabled',
        'coinpayments.merchant_id' => 'string|nullable|required_with:coinpayments.enabled',
        'coinpayments.fee'          => 'numeric|nullable',
        'coinpayments.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #----------------
        'omise.enabled'    => 'nullable|in:on',
        'omise.secret_key' => 'string|nullable|required_with:omise.enabled',
        'omise.public_key' => 'string|nullable|required_with:omise.enabled',
        'omise.fee'        => 'numeric|nullable',
        'omise.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #----------------
        'paymentwall.enabled'    => 'nullable|in:on',
        'paymentwall.mode'       => 'in:live,sandbox|string|nullable|required_with:paymentwall.enabled',
        'paymentwall.secret_key' => 'string|nullable|required_with:paymentwall.enabled',
        'paymentwall.project_key' => 'string|nullable|required_with:paymentwall.enabled',
        'paymentwall.fee'        => 'numeric|nullable',
        'paymentwall.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #----------------
        'authorize_net.enabled'         => 'nullable|in:on',
        'authorize_net.mode'            => 'in:live,sandbox|string|nullable|required_with:authorize_net.enabled',
        'authorize_net.api_login_id'    => 'string|nullable|required_with:authorize_net.enabled',
        'authorize_net.client_key'      => 'string|nullable|required_with:authorize_net.enabled',
        'authorize_net.transaction_key' => 'string|nullable|required_with:authorize_net.enabled',
        'authorize_net.signature_key'   => 'string|nullable|required_with:authorize_net.enabled',
        'authorize_net.fee'             => 'numeric|nullable',
        'authorize_net.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #----------------
        'yookassa.enabled'    => 'nullable|in:on',
        'yookassa.secret_key' => 'string|nullable|required_with:yookassa.enabled',
        'yookassa.shop_id'    => 'string|nullable|required_with:yookassa.enabled',
        'yookassa.fee'        => 'numeric|nullable',
        'yookassa.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #----------------
        'sslcommerz.enabled'      => 'nullable|in:on',
        'sslcommerz.store_passwd' => 'string|nullable|required_with:sslcommerz.enabled',
        'sslcommerz.store_id'     => 'string|nullable|required_with:sslcommerz.enabled',
        'sslcommerz.fee'          => 'numeric|nullable',
        'sslcommerz.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'flutterwave.enabled'          => 'nullable|in:on',
        'flutterwave.public_key'       => 'string|nullable|required_with:flutterwave.enabled',
        'flutterwave.secret_key'       => 'string|nullable|required_with:flutterwave.enabled',
        'flutterwave.methods'          => 'string|nullable|required_with:flutterwave.enabled',
        'flutterwave.verif_hash'       => 'string|nullable|required_with:flutterwave.enabled',
        'flutterwave.fee'              => 'numeric|nullable',
        'flutterwave.auto_exchange_to' => 'nullable|string|regex:/^[a-z]{3}$/i',
                    #---------------
        'vat'               => 'numeric|nullable',
        'currency_code'     => 'required|string',
        'currency_symbol'   => 'nullable|string',
        'currency_position' => 'nullable|string|in:left,right',
        'currencies'        => 'nullable|string',
        'allow_foreign_currencies' => 'nullable|numeric|in:0,1',
        'currency_exchange_api' => 'nullable|in:api.exchangeratesapi.io,api.currencyscoop.com,api.exchangerate.host,api.coingate.com',
        'currencyscoop_api_key' => 'required_if:currency_exchange_api,api.currencyscoop.com',
        'exchangeratesapi_io_key' => 'required_if:currency_exchange_api,api.exchangeratesapi.io',
        'guest_checkout'  => 'nullable|in:0,1',
        'pay_what_you_want.enabled' => 'nullable|string|in:0,1',
        'pay_what_you_want.for' => 'nullable|required_if:pay_what_you_want.enabled,1',
        'currency_by_country' => 'nullable|string|in:0,1'
      ]);
  
      $request->currencies = explode(',', $request->currencies);
      $request->currencies[] = $request->currency_code;
      $request->currencies = array_unique($request->currencies);
      $request->currencies = implode(',', $request->currencies);

      Setting::first()->update(['payments' => json_encode([
                "paypal"          => $request->paypal,
                "stripe"          => $request->stripe,
                "skrill"          => $request->skrill,
                "razorpay"        => $request->razorpay,
                "iyzico"          => $request->iyzico,
                "coingate"        => $request->coingate,
                "midtrans"        => $request->midtrans,
                "paystack"        => $request->paystack,
                "adyen"           => $request->adyen,
                "instamojo"       => $request->instamojo,
                "offline"         => $request->offline,
                "payhere"         => $request->payhere,
                "coinpayments"    => $request->coinpayments,
                "spankpay"        => $request->spankpay,
                "omise"           => $request->omise,
                "paymentwall"     => $request->paymentwall,
                "authorize_net"   => $request->authorize_net,
                "sslcommerz"      => $request->sslcommerz,
                "flutterwave"     => $request->flutterwave,
                "vat"             => $request->vat,
                "currency_code"   => $request->currency_code,
                "currency_symbol" => $request->currency_symbol,
                "currency_position" => $request->currency_position,
                "exchange_rate"   => 1,
                "currencies"      => $request->currencies,
                "allow_foreign_currencies" => $request->allow_foreign_currencies,
                "currency_exchange_api" => $request->currency_exchange_api,
                "currencyscoop_api_key" => $request->currencyscoop_api_key,
                "exchangeratesapi_io_key" => $request->exchangeratesapi_io_key,
                "guest_checkout" => $request->guest_checkout,
                "pay_what_you_want" => json_encode($request->pay_what_you_want),
                "currency_by_country" => $request->currency_by_country,
              ])]);
        
      \Cache::forget('paypal_access_token');
    }



    private static function update_search_engines(Request $request)
    {      
      $request->validate([
        'google'  => 'string|nullable',
        'bing'    => 'string|nullable',
        'yandex'  => 'string|nullable',
        'google_analytics' => 'string|nullable',
        'robots'  => ['regex:/^(follow, index|follow, noindex|nofollow, index|nofollow, noindex)$/i']
      ]);

      $settings = Setting::first();

      $search_engines_settings = json_decode($settings->search_engines) ?? new \stdClass;

      $search_engines_settings->google  = $request->google;
      $search_engines_settings->bing    = $request->bing;
      $search_engines_settings->yandex  = $request->yandex;
      $search_engines_settings->google_analytics = $request->google_analytics;
      $search_engines_settings->robots  = $request->robots;

      $settings->search_engines = json_encode($search_engines_settings);

      $settings->save();
    }



    private static function update_adverts(Request $request)
    {
      $request->validate([
        'responsive_ad' => 'string|nullable',
        'auto_ad'       => 'string|nullable',
        'in_feed_ad'    => 'string|nullable',
        'link_ad'       => 'string|nullable',
        'ad_728x90'     => 'string|nullable',
        'ad_468x60'     => 'string|nullable',
        'ad_250x250'    => 'string|nullable',
        'ad_320x100'    => 'string|nullable'
      ]);


      $settings = Setting::first();

      $advers_settings = json_decode($settings->adverts) ?? new \stdClass;

      $advers_settings->responsive_ad = $request->responsive_ad;
      $advers_settings->auto_ad       = $request->auto_ad;
      $advers_settings->ad_728x90     = $request->ad_728x90;
      $advers_settings->ad_468x60     = $request->ad_468x60;
      $advers_settings->ad_300x250    = $request->ad_300x250;
      $advers_settings->ad_320x100    = $request->ad_320x100;
      $advers_settings->popup_ad      = $request->popup_ad;

      $settings->adverts = json_encode($advers_settings);

      $settings->save();
    }



    private static function update_files_host(Request $request)
    {
      $request->validate([
        'google_drive.enabled'        => 'nullable|in:on',
        'google_drive.api_key'        => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.client_id'      => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.secret_id'      => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.refresh_token'  => 'string|nullable|required_with:google_drive.enabled',
        'google_drive.chunk_size'     => 'numeric|nullable|gte:1',
        'google_drive.folder_id'      => 'nullable|string',
                  #---------------------------
        'dropbox.enabled'             => 'nullable|in:on',
        'dropbox.app_key'             => 'string|nullable|required_with:dropbox.enabled',
        'dropbox.app_secret'          => 'string|nullable|required_with:dropbox.enabled',
        'dropbox.access_token'        => 'string|nullable|required_with:dropbox.enabled',
        'dropbox.folder_path'         => 'nullable|regex:/^\/(.+)$/i',
                 #----------------------------
        'yandex.enabled'              => 'nullable|in:on',
        'yandex.client_id'            => 'string|nullable|required_with:yandex.enabled',
        'yandex.secret_id'            => 'string|nullable|required_with:yandex.enabled',
        'yandex.refresh_token'        => 'string|nullable|required_with:yandex.enabled',
        'yandex.folder_path'          => 'nullable|string',
                #----------------------------
        'amazon_s3.enabled'           => 'nullable|in:on',
        'amazon_s3.access_key_id'     => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.secret_key'        => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.bucket'            => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.region'            => 'string|nullable|required_with:amazon_s3.enabled',
        'amazon_s3.version'           => 'string|nullable|required_with:amazon_s3.enabled',
                #----------------------------
        'wasabi.enabled'              => 'nullable|in:on',
        'wasabi.access_key'           => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.secret_key'           => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.bucket'               => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.region'               => 'string|nullable|required_with:wasabi.enabled',
        'wasabi.version'              => 'string|nullable|required_with:wasabi.enabled',
                #----------------------------
        'working_with'                => ['string', 'regex:/^(files|folders)$/i'],
      ]);

      $data = [
                'google_drive'  => $request->google_drive,
                'dropbox'       => $request->dropbox,
                'yandex'        => $request->yandex,
                'amazon_s3'     => $request->amazon_s3,
                'wasabi'        => $request->wasabi,
                'working_with'  => $request->working_with
              ];

      if(strtolower($request->working_with) === 'folders')
      {
        unset($data['yandex']['enabled'], $data['amazon_s3']['enabled'], $data['wasabi']['enabled']);
      }

      Setting::first()->update(['files_host' => json_encode($data)]);
    }



    private static function update_social_login(Request $request)
    {
      $request->validate([
        'google.enabled'        => 'nullable|in:on',
        'google.client_id'      => 'string|nullable|required_with:google.enabled',
        'google.secret_id'  => 'string|nullable|required_with:google.enabled',
                #---------------------------
        'github.enabled'        => 'nullable|in:on',
        'github.client_id'      => 'string|nullable|required_with:github.enabled',
        'github.secret_id'  => 'string|nullable|required_with:github.enabled',
                #---------------------------
        'linkedin.enabled'        => 'nullable|in:on',
        'linkedin.client_id'      => 'string|nullable|required_with:linkedin.enabled',
        'linkedin.secret_id'  => 'string|nullable|required_with:linkedin.enabled',
                #---------------------------
        'facebook.enabled'        => 'nullable|in:on',
        'facebook.client_id'      => 'string|nullable|required_with:facebook.enabled',
        'facebook.secret_id'  => 'string|nullable|required_with:facebook.enabled',
                #---------------------------
        'vkontakte.enabled'       => 'nullable|in:on',
        'vkontakte.client_id'     => 'string|nullable|required_with:vkontakte.enabled',
        'vkontakte.secret_id' => 'string|nullable|required_with:vkontakte.enabled',
                #---------------------------
        'twitter.enabled'         => 'nullable|in:on',
        'twitter.client_id'       => 'string|nullable|required_with:twitter.enabled',
        'twitter.secret_id'   => 'string|nullable|required_with:twitter.enabled'
      ]);

      Setting::first()->update(['social_login' => json_encode([
                "google"    => array_merge($request->google, ['redirect' => env('GOOGLE_CALLBACK')]),
                "github"    => array_merge($request->github, ['redirect' => env('GITHUB_CALLBACK')]),
                "linkedin"  => array_merge($request->linkedin, ['redirect' => env('LINKEDIN_CALLBACK')]),
                "facebook"  => array_merge($request->facebook, ['redirect' => env('FACEBOOK_CALLBACK')]),
                "vkontakte" => array_merge($request->vkontakte, ['redirect' => env('VK_CALLBACK')]),
                "twitter"   => array_merge($request->twitter, ['redirect' => env('TWITTER_CALLBACK')]),
              ])]);
    }



    private static function update_chat(Request $request)
    {
      $request->validate([
        'twak.enabled'     => 'nullable|in:on',
        'twak.property_id' => 'string|nullable|required_with:twak.enabled',
        
        #---------------------
        
        'gist.enabled'     => 'nullable|in:on',
        'gist.workspace_id' => 'string|nullable|required_with:gist.enabled',

        #---------------------

        'other.enabled'  => 'nullable|in:on',
        'other.code'     => 'string|nullable|required_with:other.enabled' 
      ]);

      Setting::first()->update(['chat' => json_encode([
                "twak" => $request->twak,
                "gist" => $request->gist,
                'other' => $request->other
              ])]);
    }


    private static function update_translations(Request $request)
    {
      $langs = File::directories(resource_path('lang/'));
      $langs = array_filter(preg_replace('/.+(\/|\\\)([\w-]+)$/i', '$2', $langs));

      $request->validate([
        'translation' => 'required|array',
        'new' => 'nullable|array',
        '__lang__' => ['required', 'string', 'in:'.implode(',', $langs), function($attribute, $value, $fail) use($request)
        {
            if (!File::exists(resource_path("lang/{$request->__lang__}.json"))) 
            {
              $fail('Missing language file.');
            }
        }]
      ]);

      $new_translation = [];

      if(array_filter($request->new ?? []))
      {
        if(count($request->new['key'] ?? []) === count($request->new['value'] ?? []))
        {
          $new_translation = array_combine($request->new['key'] ?? [], $request->new['value'] ?? []);
        }
      }

      $lang = json_decode(File::get(resource_path("lang/{$request->__lang__}.json")), true);

      $lang = array_merge($lang, $request->translation, $new_translation);

      ksort($lang);

      File::put(resource_path("lang/{$request->__lang__}.json"), json_encode($lang, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    }


    private static function update_captcha(Request $request)
    {      
      $request->validate([
        'captcha.enable_on' => 'required_with:captcha.google.enabled|required_with:captcha.mewebstudio.enabled|nullable',
        'captcha.google.enabled'      => 'nullable|in:on',
        'captcha.google.secret' => 'nullable|string|required_with:captcha.google.enabled',
        'captcha.google.sitekey'  => 'nullable|string|required_with:captcha.google.enabled',
        'captcha.google.attributes.data-theme'  => 'nullable|string|required_with:captcha.google.enabled',
        'captcha.google.attributes.data-size'  => 'nullable|string|required_with:captcha.google.enabled|in:compact,normal',
        'captcha.mewebstudio.enabled' => 'nullable|in:on',
        'captcha.mewebstudio.length' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled',
        'captcha.mewebstudio.width' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled',
        'captcha.mewebstudio.height' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled',
        'captcha.mewebstudio.quality' => 'numeric|nullable|required_with:captcha.mewebstudio.enabled'
      ]);

      $captcha = $request->captcha;

      $captcha['mewebstudio']['math'] = $captcha['mewebstudio']['math'] === "true";

      Setting::first()->update(['captcha' => json_encode($captcha)]);
    }



    private static function update_database(Request $request)
    {      
      $request->validate([
        'database.host'             => 'required|string',
        'database.database'         => 'required|string',
        'database.username'         => 'required|string',
        'database.password'         => 'required|string',
        'database.charset'          => 'required|string',
        'database.collation'        => 'required|string',
        'database.port'             => 'required|numeric',
        'database.sort_buffer_size' => 'required|numeric|gte:0',
        'database.timezone'         => 'required|string',
        'database.sql_mode'         => 'required|string'
      ]);

      $new_config = [];

      foreach($request->post('database') as $k => $v)
      {
        $k = "DB_".strtoupper($k);
        $new_config[$k] = wrap_str($v);
      }

      update_env_var($new_config);

      Setting::first()->update(['database' => json_encode($request->post('database'))]);
    }



    public function update_bulk_upload(Request $request)
    {
      $request->validate([
        'data_file'    => 'required|file',
        'main_files.*' => 'nullable|file|mimes:zip,rar,7z',
        'covers.*'     => 'nullable|image'
      ]);


      $csv        = $request->file('data_file');
      $main_files = $request->file('main_files');
      $covers     = $request->file('covers');

      $cols = $request->post('columns');

      if(!ini_get("auto_detect_line_endings"))
      {
        ini_set("auto_detect_line_endings", '1');
      }

      $columns = [];
      $reader  = Reader::createFromPath($csv->getRealPath())->setHeaderOffset(0);
      $result  = Statement::create()->process($reader);
      $records = iterator_to_array($result->getRecords());
      $data    = [];

      if($request->async)
      {
        foreach($records as $record)
        {

          header('Content-Type: application/json');

          $columns =  array_reduce(array_keys($record), function($carry, $column)
                      {
                        $carry[] = ['name' => str_replace('_', ' ', mb_ucfirst($column)), 'value' => $column];
                        return $carry;
                      }, []);

          exit(json_encode($columns));
        }
      }

      $_cols = array_combine($cols['original'], $cols['imported']);

      foreach($records as $record)
      { 
        $entry = [];

        foreach($_cols as $original => $imported)
        {
          $entry[$original] = $record[$imported] ?? null; 
        }

        $data[] = $entry;
      }

      foreach($data as $item)
      {
        $product_id = get_auto_increment('products');

        unset($item['id']);

        $regular_price = $item['regular_price'] ?? 0;

        unset($item['regular_price']);

        $category = $item['category'] ?? null;
        $new_category = null;

        if(isset($category))
        {
          if(!filter_var($category, FILTER_VALIDATE_INT))
          {
            if($existing_category = Category::where('name', $category)->first())
            {
              $new_category = $existing_category;
            }
            else
            {
              $new_category = new Category;

              $new_category->name = $category;
              $new_category->slug = slug($category);

              $new_category->save();
            }
          }
          else
          {
            $new_category = (object)['id' => $category]; 
          }
        }

        $product = new Product;

        foreach($item as $key => $val)
        {
          $product->$key = $val;          
        }


        $product->type   = in_array($item['type'] ?? null, ['-', 'audio', 'video', 'graphic', 'ebook']) ? $item['type'] : '-';
        
        $product->is_dir = in_array((string)($item['is_dir'] ?? null), ['0', '1']) ? $item['is_dir'] : 0;

        foreach($main_files as $main_file)
        {
          if($product->file_name == $main_file->getClientOriginalName())
          {
            $extension = $main_file->getClientOriginalExtension();

            $main_file->storeAs("downloads", "{$product_id}.{$extension}", []);

            $product->file_name = "{$product_id}.{$extension}";
          }
        }

        foreach($covers as $cover)
        {
          if($product->cover == $cover->getClientOriginalName())
          {
            $extension = $cover->getClientOriginalExtension();

            $cover->storeAs("covers", "{$product_id}.{$extension}", ['disk' => 'public']);

            $product->cover = "{$product_id}.{$extension}";
          }
        }

        if($new_category)
        {
          $product->category = $new_category->id;
        }

        $product->slug = slug($product->name);
        $product->file_host = $item['file_host'] ?? 'local';

        $product->save();

        if(!$license = License::where('item_type', $product->type)->where('regular', 1)->first())
        {
          $license = new License;
          
          $license->name      = 'Regular License';
          $license->item_type = $product->type;
          $license->regular   = 1;

          $license->save();
        }

        Product_Price::where('product_id', $product_id)->delete();

        Product_Price::insert(['license_id' => $license->id, 'product_id' => $product_id, 'price' => $regular_price, 'promo_price' => null]);
      }
    } 



    public static function remove_top_cover()
    {
      foreach(glob(public_path('storage/images/top_cover.*')) ?? [] as $top_cover)
      {
        @unlink($top_cover);
      }

      $settings = Setting::first();

      $general_settings = json_decode($settings->general) ?? (object)[];
      
      $general_settings->top_cover =  '';

      $settings->general = json_encode($general_settings);
      
      $settings->save();

      return response()->json(['success' => true]);
    }
    

    
    public function google_drive_get_refresh_token(Request $request)
    {
        return GoogleDrive::code_to_access_token_async($request);
    }
    
    
    public function google_drive_get_current_user(Request $request)
    {
        return GoogleDrive::get_current_user($request);
    }
    
    
    public function dropbox_get_current_user(Request $request)
    {
        return DropBox::get_current_user($request);
    }



    public function yandex_disk_get_refresh_token(Request $request)
    {
      return YandexDisk::code_to_refresh_token($request);
    }


    public function test_amazon_s3_connection(Request $request)
    {
      return response()->json(['status' => AmazonS3::test_connexion($request) ? __('Success') : __('Failed')]);
    }


    public function test_wasabi_connection(Request $request)
    {
      return response()->json(['status' => Wasabi::test_connexion($request) ? __('Success') : __('Failed')]);
    }


    public function test_database_connection(Request $request)
    {
      $error_message = null;

      if(!$request->installation)
      {
        $mysql_config  = config('database.connections.mysql');
        $mysql_config  = array_merge($request->database, $mysql_config);

        Config::set("database.connections.mysql", $mysql_config);
        
        try 
        {
          DB::connection()->getPdo();
        }
        catch (\Exception $e)
        {
          $error_message = $e->getMessage();
        }
      }
      else
      {
        $db_config = array_values($request->input('database'));

        try 
        {
          $mysqli = new \mysqli(...$db_config);

          if($mysqli->connect_error) 
          {
            $error_message = $mysqli->connect_error;
          }
        }
        catch(\Exception $e)
        {
          $error_message = $e->getMessage();
        }
      }

      return response()->json(['status' => $error_message ?? __('Success')]);
    }


    public function get_translation(Request $request)
    {
      $lang = $request->lang ?? abort(404);

      $lang = json_decode(File::get(resource_path("lang/{$lang}.json")), true);
      $base = json_decode(File::get(resource_path("lang/en.json")), true);
      
      return response()->json(compact('lang', 'base'));
    }


    public function clear_cache(Request $request)
    {
        $name = strtolower($request->post('name'));

        if(preg_match('/^sessions|views|cache$/', $name))
        {
            if($name !== 'sessions')
            {
              $name = rtrim($name, 's');

              \Artisan::call("{$name}:clear");  
            }
            else
            {
              \File::cleanDirectory(storage_path("framework/{$name}"));
            }

            $exists = cache_exists($name);

            return response()->json(['exists' => $exists ? '1' : '0']);
        }

        return response()->json([]);
    }
}
