<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\{ DB, Route, Blade };
use App\Models\{ Setting, Page, Category, License, Product };
use App\Http\Controllers\AdminNotifsController;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    { 
        if(!app()->runningInConsole() && config('app.installed') === true)
        {
          DB::statement("SET sort_buffer_size = ?", [(int)env('DB_SORT_BUFFER_SIZE', 2) * 1048576]);
          DB::statement("SET sql_mode = ?", [(string)env('DB_SQL_MODE', 'STRICT_TRANS_TABLES')]);

          config(['mmdb_reader' => new \GeoIp2\Database\Reader(base_path('maxmind/GeoLite2-Country.mmdb'))]);

          Blade::directive('cards', function($args) 
          {
            return "<?php cards({$args}); ?>";
          });

          Paginator::defaultView('vendor.pagination.semantic-ui');

          Paginator::defaultSimpleView('vendor.pagination.simple-semantic-ui');

          $settings = Setting::first()->toArray();

          foreach($settings as $k => &$v)
          {
            $v = json_decode($v, true) ? json_decode($v, true) : $v;

            if(is_array($v))
            {
              foreach($v as &$sub_v)
              {
                $sub_v = is_array($sub_v) ? $sub_v : (json_decode($sub_v, true) ? json_decode($sub_v, true) : $sub_v);
              }
            }
          }

          $mail  = collect($settings['mailer']['mail'] ?? []);
          $pages =  Page::useIndex('active')->select('name', 'slug', 'deletable')->where('active', 1)->get()->toArray();
          $pages =  array_combine(array_column($pages, 'slug'), $pages);

          $settings['social_login'] = json_decode(str_ireplace('secret_id', 'client_secret', json_encode($settings['social_login'] ?? [])), TRUE);

          if(isset($settings['captcha']))
          {
            $captcha = array_merge(config('captcha'), $settings['captcha']['google']);
            
            $captcha['default'] = $settings['captcha']['mewebstudio'];
            $captcha['enable_on'] = array_filter(explode(',', $settings['captcha']['enable_on']));

            config(['captcha' => $captcha]);
          }

          if($currencies = array_filter(explode(',', ($settings['payments']['currencies'] ?? null))))
          {
            $_currencies = array_filter(config('payments.currencies', []), function($v, $k) use ($currencies)
                                                {
                                                  return in_array(mb_strtolower($k), $currencies);
                                                }, ARRAY_FILTER_USE_BOTH);

            ksort($_currencies);

            $settings['payments']['currencies'] = $_currencies;
          }

          parse_str(request()->getQueryString(), $url_params);

          $settings['general']['url_params'] = $url_params;


          config([
            'services'   => array_merge(config('services', []), $settings['social_login']  ?? []),
            'payments'   => array_merge(config('payments', []), $settings['payments']  ?? []),
            'affiliate'  => $settings['affiliate'] ?? [],
            'adverts'    => $settings['adverts'] ?? [],
            'chat'       => $settings['chat'] ?? [],
            'mail.mailers.smtp'   => array_merge(config('mail.mailers.smtp'), $mail->except('from')->toArray()),
            'mail.from'           => $mail->only('from')->values()->first() ?? [],
            'mail.reply_to'       => $mail->only('reply_to')->values()->first(),
            'mail.forward_to'     => $mail->only('forward_to')->values()->first(),
            'app'        => array_merge(config('app', []), $settings['general'] ?? [], $settings['search_engines'] ?? [], ['version' => env('APP_VERSION', '3.0.0')]),
            'filehosts'  => array_merge(config('filehosts', []), $settings['files_host'] ?? []),
            'categories' => Category::products(),
            'pages'      => $pages,
            'popular_categories' => Category::popular(),
            'licenses'   => License::select('id', 'name', 'item_type')->get()->groupBy('item_type'),
          ]);

          $cashout_methods = explode(',', config('affiliate.cashout_methods'));
          $cashout_methods = array_combine(array_values($cashout_methods), array_values($cashout_methods));

          config(['affiliate.cashout_methods' => $cashout_methods]);
          
          $template = config('app.template');

          config(['app.top_cover' => config("app.{$template}_top_cover"),
                  'app.top_cover_mask' => config("app.{$template}_top_cover_mask")]);
          
          $langs = explode(',', $settings['general']['langs'] ?? config('app.locale'));

          $supportedLocales = config('laravellocalization.supportedLocales');

          foreach($supportedLocales as $locale => $props)
          {
            if(!in_array($locale, $langs))
              unset($supportedLocales[$locale]);
          }

          config(['laravellocalization.supportedLocales' => $supportedLocales, 'langs' => $langs]);

          config(['app.locale' => $langs[0]]);
          
          $payment_procs = collect(config('payments', []))->where('enabled', '===', 'on')->toArray();

          $pay_what_you_want = config('payments.pay_what_you_want');
          $pay_what_you_want['for'] = explode(',', $pay_what_you_want['for']);
          $pay_what_you_want['for'] = array_combine(array_values($pay_what_you_want['for']), array_values($pay_what_you_want['for']));
          
          $minimum_payments = array_column($payment_procs, 'minimum');
          $minimum_payments = (empty($minimum_payments) || count($minimum_payments) < count($payment_procs)) ? array_fill(0, count($payment_procs), 0) : $minimum_payments;

          config([
            'fees' => array_combine(array_keys($payment_procs), array_column($payment_procs, 'fee')),
            'mimimum_payments' => array_combine(array_keys($payment_procs), $minimum_payments),
            'pay_what_you_want' => $pay_what_you_want,
            'products_types' => DB::table('products')->selectRaw('DISTINCT(products.`type`)')->where('active', 1)->get()->pluck('type')->toArray()
          ]);

          if(!preg_match('/^home\..+/i', Route::currentRouteName()))
          {
            $admin_notifications = AdminNotifsController::latest();

            view()->share(compact('admin_notifications'));
          }

          if($timezone = config('app.timezone'))
          {            
            date_default_timezone_set($timezone);
            ini_set('date.timezone', $timezone);
          }

          preg_match('/^\(GMT(?P<offset>.+\d+:\d+)\) \w+$/i', config("app.timezones.{$timezone}"), $matches);
          
          $timezone = $matches['offset'] ?? '+00:00';

          DB::statement('SET time_zone = ?', [(string)env('DB_TIMEZONE', $timezone)]);
        }
    }
}
