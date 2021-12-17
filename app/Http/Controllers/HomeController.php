<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ Product, User_Subscription, Subscription, Page, Post, Setting, Newsletter_Subscriber, Reaction, Search, Subscription_Same_Item_Downloads,
                 Category, Support_Email, Support, Review, Comment, Faq, Notification, Transaction, User, Product_Price, License,
                 Payment_Link };
use Illuminate\Support\Facades\{ DB, File, Hash, Validator, Config, Auth, Mail, Cache, Session };
use App\Libraries\{ DropBox, GoogleDrive, IyzicoLib, YandexDisk, OneDrive, AmazonS3, Wasabi, PayPalCheckout };
use App\Events\NewMail;
use ZipArchive;
use GeoIp2\Database\Reader;


class HomeController extends Controller
{
    public $meta_data = [];

    public function __construct()
    {
      $this->meta_data = (object)['name'        => config('app.name'),
                                  'title'       => config('app.title'),
                                  'description' => config('app.description'), 
                                  'url'         => url()->current(),
                                  'fb_app_id'   => config('app.fb_app_id'),
                                  'image'       => asset('storage/images/'.(config('app.cover') ?? 'cover.jpg'))];

      $this->middleware('maintenance_mode');
    }


    private static $product_columns = ['products.id', 'products.name', 'products.views', 'products.preview', 'products.preview_type', 'products.for_subscriptions', 'products.type', 'licenses.id as license_id', 'licenses.name as license_name', 'products.pages', 'products.authors', 'products.language', 'products.country_city', 
      'products.words', 'products.formats', 
                                       'products.slug', 'products.updated_at', 'products.active', 'products.bpm', 'products.label',
                                       'products.cover', 'products.last_update', 'products.hidden_content', 'categories.id as category_id',
                                       'is_dir', 'products.trending', 'products.stock', 'IFNULL(CHAR_LENGTH(GROUP_CONCAT(transactions.products_ids)) - CHAR_LENGTH(REPLACE(GROUP_CONCAT(transactions.products_ids), QUOTE(products.id), SPACE(LENGTH(QUOTE(products.id))-1))), 0) AS sales', 
                                      'IFNULL((SELECT ROUND(AVG(rating)) FROM reviews WHERE product_id = products.id), 0) AS rating',
                                      '(SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id AND key_s.user_id IS NULL) as `remaining_keys`',
                                      '(SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id) as has_keys',
                                      'products.tags', 'products.short_description',
                                       "CASE
                                          WHEN product_price.`promo_price` IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN STR_TO_DATE(SUBSTR(products.promotional_price_time, 10, 10), '%Y-%m-%d') and STR_TO_DATE(SUBSTR(products.promotional_price_time, 28, 10), '%Y-%m-%d')))
                                            THEN product_price.promo_price
                                          ELSE
                                            NULL
                                        END AS `promotional_price`",
                                        "IF(DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN STR_TO_DATE(SUBSTR(products.promotional_price_time, 10, 10), '%Y-%m-%d') and STR_TO_DATE(SUBSTR(products.promotional_price_time, 28, 10), '%Y-%m-%d'), products.promotional_price_time, null) AS promotional_time",
                                        'product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) AS free_item',
                                        'IF(product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) = 1, 0, product_price.price) AS price',
                                       'categories.name as category_name', 'categories.slug as category_slug',
                                       'GROUP_CONCAT(subcategories.name) AS subcategories'];


    public function index()
    {
        Self::init_notifications();

        $template = config('app.template', 'valexa');

        return call_user_func([$this, "{$template}_home"]);
    }


    private function tendra_home()
    {
        $posts = Post::useIndex('primary')
                  ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                  ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                  ->where('active', 1)->orderBy('id', 'DESC')->limit(config('app.homepage_items.tendra.posts.limit', 4))->get();

        Self::init_notifications();

        $featured_products = [];

        foreach(config('categories.category_parents') as $parent_category)
        {
          $featured_products[$parent_category->slug] = $this->featured_products(false, config('app.homepage_items.tendra.featured.limit', 6), $parent_category->id, config('app.randomize_homepage_items', 0));
        }

        $featured_products =  array_filter($featured_products, function($items, $k)
                              {
                                return $items->count();
                              }, ARRAY_FILTER_USE_BOTH);

        $newest_products = $this->newest_products(false, config('app.homepage_items.tendra.newest.limit', 14), config('app.randomize_homepage_items', 0));
        
        $free_products = $this->free_products(false, config('app.homepage_items.tendra.free.limit', 10), config('app.randomize_homepage_items', 0));

        $products = [];

        foreach($featured_products as $list)
        {
          foreach($list as $featured_product)
          {
            $products[$featured_product->id] = $featured_product;
          }
        }

        foreach($newest_products as $newest_product)
        {
          $products[$newest_product->id] = $newest_product;
        }

        $subscriptions      = Subscription::useIndex('position')->orderBy('position', 'asc')->get();

        $meta_data          = $this->meta_data;

        return view_('home', compact('products', 'posts', 'newest_products', 'subscriptions', 
                                     'featured_products', 'free_products', 'meta_data'));
    }


    private function valexa_home()
    {
        $posts = Post::useIndex('primary')->where('active', 1)->orderBy('id', 'DESC')->limit(config('app.homepage_items.valexa.posts.limit', 4))->get();

        $meta_data = $this->meta_data;

        $selections = [
          'trending_products' => $this->trending_products(false, config('app.homepage_items.valexa.trending.limit', 4), config('app.randomize_homepage_items', 0)),
          'featured_products' => $this->featured_products(false, config('app.homepage_items.valexa.featured.limit', 8), null, config('app.randomize_homepage_items', 0)),
          'free_products'     => $this->free_products(false, config('app.homepage_items.valexa.free.limit', 4), config('app.randomize_homepage_items', 0)),
          'flash_products'    => $this->flash_products(false, config('app.homepage_items.valexa.flash.limit', 4), config('app.randomize_homepage_items', 0)),
          'newest_products'   => $this->newest_products(false, config('app.homepage_items.valexa.newest.limit', 20), config('app.randomize_homepage_items', 0))
        ];

        $products = [];

        foreach($selections as $selection)
        {
          $products = array_merge($products, $selection->toArray());
        }

        $products = array_combine(array_column($products, 'id'), $products);
        $products = array_unique($products, SORT_REGULAR);

        extract($selections);

        return view_('home', compact('posts', 'products', 'trending_products', 'newest_products','featured_products', 
                                     'free_products', 'flash_products', 'meta_data'));
    }


    private function default_home()
    {
        $posts = Post::useIndex('primary')->where('active', 1)->orderBy('id', 'DESC')->limit(config('app.homepage_items.default.posts.limit', 5))->get();

        $meta_data = $this->meta_data;

        $selections = [
          'trending_products' => $this->trending_products(false, config('app.homepage_items.default.trending.limit', 4), config('app.randomize_homepage_items', 0)),
          'featured_products' => $this->featured_products(false, config('app.homepage_items.default.featured.limit', 8), null, config('app.randomize_homepage_items', 0)),
          'free_products'     => $this->free_products(false, config('app.homepage_items.default.free.limit', 4), config('app.randomize_homepage_items', 0)),
          'flash_products'    => $this->flash_products(false, config('app.homepage_items.default.flash.limit', 4), config('app.randomize_homepage_items', 0)),
          'newest_products'   => $this->newest_products(false, config('app.homepage_items.default.newest.limit', 8), config('app.randomize_homepage_items', 0))
        ];

        $products = [];

        foreach($selections as $selection)
        {
          $products = array_merge($products, $selection->toArray());
        }

        $products = array_combine(array_column($products, 'id'), $products);
        $products = array_unique($products, SORT_REGULAR);

        extract($selections);

        $this->set_home_categories(15);

        return view_('home', compact('posts', 'products', 'trending_products', 'newest_products','featured_products', 
                                     'free_products', 'flash_products', 'meta_data'));
    }


    // Single page
    public function affiliate()
    {
        Self::init_notifications();

        $meta_data = $this->meta_data;

        return view('front.affiliate', compact('meta_data'));
    }

    

    // Single page
    public function page($slug)
    {
        if(!$page = Page::useIndex('slug', 'active')->where(['slug' => $slug, 'active' => 1])->first())
          abort(404);

        Self::init_notifications();

        $page->setTable('pages')->increment('views', 1);

        $meta_data = $this->meta_data;

        $meta_data->title = $page->name;
        $meta_data->description = $page->short_description;
        $meta_data->url = route('home.page', $page->slug);

        return view_('page',compact('page', 'meta_data'));
    }


    // Products per category
    public function products(Request $request)
    {      
      $categories         = config('categories.category_parents', []);
      $subcategories      = config('categories.category_children', []);
      $category           = $active_category = $active_subcategory = (object)[];
      $meta_data          = $this->meta_data;
      
      if($sort = strtolower($request->query('sort')))
      {
        preg_match('/^(?P<sort>relevance|price|rating|featured|trending|date)_(?P<order>asc|desc)$/i', $sort, $matches) || abort(404);

        extract($matches);
      }
      else
      {
        list($sort, $order) = ['id', 'desc'];
      }

      if($sort === 'date')
        $sort = 'updated_at';
      else
        $sort = 'id';

      Self::init_notifications();

      $indexes = ['active'];

      if($request->category_slug)
      {
        array_push($indexes, 'category');

        $category_slug = $request->category_slug;

        $active_category =  array_filter($categories, function($category) use ($category_slug)
                            {
                              return $category->slug === strtolower($category_slug);
                            }) ?? abort(404);

        $active_category = array_shift($active_category);
        
        if(!isset($active_category->name)) return back();

        if($subcategory_slug = $request->subcategory_slug)
        {
          if(!isset($subcategories[$active_category->id ?? null])) return back();

          $active_subcategory =   array_filter($subcategories[$active_category->id], 
                                  function($subcategory) use ($subcategory_slug)
                                  {
                                    return $subcategory->slug === strtolower($subcategory_slug);
                                  });

          if(!$active_subcategory = array_shift($active_subcategory)) return back();
        }

        if(!$subcategory_slug)
        {
          $category->name        = $active_category->name;
          $category->description = Category::useIndex('primary')->select('description')
                                                        ->where('id', $active_category->id)->first()->description;

          $products = Product::where(['category' => $active_category->id]);

          $meta_data->url = route('home.products.category', ['category_slug' => $category_slug]);
        }
        else
        {
          array_push($indexes, 'subcategories');

          $category->name        = $active_subcategory->name;
          $category->description = Category::useIndex('primary')->select('description')
                                                        ->where('id', $active_subcategory->id)->first()->description;

          $products = Product::where(['category' => $active_category->id])
                              ->whereRaw("subcategories LIKE '%{$active_subcategory->id}%'");

          $meta_data->url = route('home.products.category', ['category_slug'    => $category_slug, 
                                                             'subcategory_slug' => $subcategory_slug]);
        }

        $meta_data->title       = config('app.name').' - '.$category->name;
        $meta_data->description = $category->description;
      }

      if($filter = strtolower($request->filter))
      {
        if($filter === 'free')
        {
          $products = Product::where(function ($query)
                            {
                              $query->where('product_price.price', 0)
                                    ->orWhereRaw("CURRENT_DATE between substr(free, 10, 10) and substr(free, 28, 10)");
                            });
        }
        elseif($filter === 'trending')
        {
          $products = Product::havingRaw("active = 1 AND (trending = 1 OR count(transactions.id) > 0)")
                      ->orderByRaw('trending, sales DESC');
        }
        elseif($filter === 'featured')
        {
          $products = Product::where("featured", 1);
        }
        elseif($filter === 'flash')
        {
          $products = Product::havingRaw("product_price.promo_price IS NOT NULL");
        }
        elseif($filter === 'newest')
        {
          $products = Product::useIndex("created_at");
        }
      }

      
      if($q = $request->query('q'))
      {
        $search = new Search;
        
        $search->keywords = $q;
        $search->user_id = Auth::id();

        $search->save();

        array_push($indexes, 'description');

        $products = call_user_func_array([$request->category_slug ? $products : '\App\Models\Product', 'whereRaw'], ["active = 1 AND (
                        products.name LIKE ? OR products.slug LIKE ? OR 
                        products.short_description LIKE ? OR products.overview LIKE ?
                      )", ["%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%"]]);

        $meta_data->title       = config('app.name').' - '.__('Searching for').' '.ucfirst($request->q);
        $meta_data->description = config('app.description');
        $meta_data->url         = route('home.products.q').'?q='.$request->q;
      }

      if($tags = $request->query('tags'))
      {
        $tags = implode('|', array_filter(explode(',', $tags)));

        $products = call_user_func_array([($request->category_slug || $q) ? $products : '\App\Models\Product', 'where'], 
                                         ['products.tags', 'REGEXP', $tags]);
      }

      $cities = $country = null;

      if(config('app.products_by_country_city'))
      {
        if($country = $request->query('country'))
        {
          if($cities = urldecode($request->query('cities')))
          {
            if($cities = array_filter(explode(',', $cities)))
            {
              $cities = implode('|', $cities);
              
              $products = call_user_func_array([($request->category_slug || $q || $tags) ? $products : '\App\Models\Product', 'whereRaw'], ["products.country_city REGEXP ?", ['^\{"country":"'. $country .'","city":"'. $cities .'"\}$']]);
            
              $cities = str_ireplace('|', ',', $cities);
            }
            else
            {
              $cities = null;
            }
          }
          else
          {
            $products = call_user_func_array([($request->category_slug || $q || $tags) ? $products : '\App\Models\Product', 'whereRaw'], ["products.country_city REGEXP ?", ['^\{"country":"'. $country .'","city":.*\}$']]);
          } 
        }
      }

      if($price_range = $request->query('price_range'))
      {
        preg_match('/^\d+,\d+$/', $price_range) || abort(404);

        $price_range =  array_filter(explode(',', $price_range), function($price)
                        {
                          return $price >= 0;
                        });

        if($price_range[0] > $price_range[1])
          return back();

        $products = call_user_func_array([($request->category_slug || $q || $tags) ? $products : '\App\Models\Product', 'whereBetween'], ['product_price.price', $price_range]);
      }

      isset($products) || abort(404);

      $products = $products->setModel(Product::useIndex($indexes))
                      ->selectRaw(implode(',', Self::$product_columns))
                      ->leftJoin('categories', 'categories.id', '=', 'products.category')
                      ->leftJoin('categories as subcategories', function($join) use ($active_category)
                      {
                          $join->on('products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                             ->where('subcategories.parent', '=', property_exists($active_category, 'id') ? $active_category->id : null);
                      })
                      ->leftJoin('transactions', 'transactions.products_ids', 'LIKE', \DB::raw("CONCAT(\"%'\", products.id,\"'%\")"))
                      ->leftJoin('reviews', 'reviews.product_id', '=', 'products.id')
                      ->leftJoin('licenses', function($join)
                      {
                        $join->on('licenses.item_type', '=', 'products.type')->where('licenses.regular', 1);
                      })
                      ->leftJoin('product_price', function($join)
                      {
                        $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                      })
                      ->whereRaw("products.active = 1 AND products.is_dir = ?", [(isFolderProcess() ? 1 : 0)])
                      ->groupBy('products.id', 'products.name', 'products.views', 'products.preview', 'products.preview_type', 'categories.id',
                                 'products.slug', 'products.updated_at', 'products.active', 'products.bpm', 'products.label',
                                 'products.cover', 'product_price.price', 'products.hidden_content', 'products.last_update', 'promotional_price_time', 'products.stock', 'products.pages', 'products.authors', 'products.language',
                                 'products.words', 'products.formats', 
                                 'categories.name', 'categories.slug', 'rating', 'products.short_description', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.is_dir', 'products.for_subscriptions', 'products.type', 'licenses.id', 'licenses.name', 'products.country_city')
                      ->orderBy($sort, $order)->paginate(config('app.items_per_page', 12));

      $tags  = [];

      foreach($products->items() as &$item)
      {
        $tags = array_merge($tags, array_filter(array_map('trim', explode(',', $item->tags))));
      }

      $tags = array_unique($tags);

      return view_('products', compact('products', 'tags', 'meta_data', 'country', 'cities'));
    }



    public function live_search(Request $request)
    {
      $products = [];

      if($q = $request->post('q'))
      {
        $products = DB::select("SELECT id, `name`, slug, cover FROM products USE INDEX(name, slug)
                                WHERE active = 1 AND (`name` LIKE ? OR slug LIKE ?) LIMIT 5", ["%{$q}%", "%{$q}%"]);
      }

      return response()->json(compact('products'));
    }



    // Single product
    public function product(Request $request)
    {
      $user_id = request()->user()->id ?? 'null';

      $product = Product::by_id($request->id, $user_id);

      $product->getAttributes() || abort(404);

      if(mb_strtolower(urldecode($request->slug)) !== mb_strtolower(urldecode($product->slug)))
      {
        return redirect(item_url($product));
      }

      $product->remaining_downloads = null;

      if($promotional_price_time = json_decode($product->promotional_price_time))
      {
        $promotional_price_time->from = format_date($promotional_price_time->from, 'Y-m-d');
        $promotional_price_time->to   = format_date($promotional_price_time->to, 'Y-m-d');

        $product->promotional_price_time = json_encode($promotional_price_time);
      }

      $product_prices = Product_Price::selectRaw("product_price.*, licenses.name as license_name, licenses.regular,
                        products.promotional_price_time IS NOT NULL as has_promo_time,
                        IF(product_price.promo_price IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN STR_TO_DATE(SUBSTR(products.promotional_price_time, 10, 10), '%Y-%m-%d') and STR_TO_DATE(SUBSTR(products.promotional_price_time, 28, 10), '%Y-%m-%d'), products.promotional_price_time, null) AS promotional_time")
                        ->leftJoin('licenses', 'licenses.id', '=', 'product_price.license_id')
                        ->join('products USE INDEX(primary)', 'products.id', '=', 'product_price.product_id')
                        ->where('product_id', $product->id)
                        ->get()->toArray();

      $product_prices = array_combine(array_column($product_prices, 'license_id'), $product_prices);

      if(exchange_rate_required())
      {
        foreach($product_prices as &$product_price)
        {
          $product_price['price'] = convert_amount($product_price['price']);

          if($product_price['promo_price'])
          {
            $product_price['promo_price'] = convert_amount($product_price['promo_price']);
          }
        }
      }

      $valid_subscription = null;



      if(Auth::check())
      {
        $subscription = User_Subscription::useIndex('user_id', 'subscription_id')
                        ->selectRaw("
                          (user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP > user_subscription.ends_at) OR
                          (subscriptions.limit_downloads > 0 AND user_subscription.downloads >= subscriptions.limit_downloads) OR 
                          (subscriptions.limit_downloads_per_day > 0 AND user_subscription.daily_downloads >= subscriptions.limit_downloads_per_day AND user_subscription.daily_downloads_date = CURDATE())
                           AS expired, subscriptions.products, subscriptions.limit_downloads_same_item,
                          IF(subscriptions.limit_downloads_same_item > 0, subscriptions.limit_downloads_same_item - IFNULL(subscription_same_item_downloads.downloads, 0), null) as remaining_downloads,
                          (subscriptions.limit_downloads_same_item > 0 AND subscription_same_item_downloads.downloads >= subscriptions.limit_downloads_same_item) as same_items_downloads_reached")
                          ->join('subscriptions', 'user_subscription.subscription_id', '=', 'subscriptions.id')
                          ->join('products', function($join) use($product)
                          {
                            $join->where('products.id', '=', $product->id)
                                 ->where('products.for_subscriptions', $product->for_subscriptions);
                          })
                          ->leftJoin('subscription_same_item_downloads USE INDEX(product_id, subscription_id)', function($join) use($product)
                          {
                            $join->on('subscription_same_item_downloads.subscription_id', '=', 'user_subscription.id')
                                 ->where('subscription_same_item_downloads.product_id', $product->id);
                          })
                          ->join('transactions USE INDEX(primary)', 'user_subscription.transaction_id', '=', 'transactions.id')
                          ->where(function($query)
                          {
                              $query->where('transactions.refunded', 0)
                                    ->orWhere('transactions.refunded', null);
                          })
                          ->where('transactions.status', 'paid')
                          ->where('user_subscription.user_id', Auth::id())
                          ->whereRaw('CASE 
                                        WHEN subscriptions.products IS NOT NULL
                                          THEN FIND_IN_SET(?, subscriptions.products)
                                        ELSE 
                                          1=1
                                      END
                                        ', [$product->id])
                          ->first();

        if($subscription)
        {
          $valid_subscription = !$subscription->expired && !$subscription->same_items_downloads_reached;
          $product->remaining_downloads = $subscription->remaining_downloads;
        }
      }

      $product->table_of_contents = json_decode($product->table_of_contents);

      if(!$product->purchased && !$valid_subscription)
      {
        if($guest_token = $request->query('guest_token'))
        {
          $product->purchased = Transaction::useIndex('guest_token')
                                ->where(['guest_token' => $guest_token, 'status' => 'paid', 'refunded' => 0, 'confirmed' => 1])
                                ->where('transactions.products_ids', 'LIKE', "'%{$product->id}%'")
                                ->first();
        }
      }

      $meta_data  = $this->meta_data;
      
      if($request->isMethod('POST'))
      {        
        $type = $request->input('type');
        
        $redirect_url = url()->current().'#'.$request->input('type');

        if($type === 'reviews')
        {
          if(! $product->purchased) abort(404);

          $rating  = $request->input('rating');
          $review  = $request->input('review');
          $approved = auth_is_admin() ? 1 : (config('app.auto_approve.reviews') ? 1 : 0);

          if(!ctype_digit($rating)) return redirect($redirect_url);

          DB::insert("INSERT INTO reviews (product_id, user_id, rating, content, approved) VALUES (?, ?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE rating = ?, content = ?", 
                      [$product->id, $user_id, $rating, $review, $approved, $rating, $review]);

          if(!$approved)
            $request->session()->put(['review_response' => 'Your review is waiting for approval. Thank you!']);

          if(Auth::check() && !auth_is_admin() && config('app.admin_notifications.reviews'))
          {
              $mail_props = [
                'data'   => ['text' => __('A new review has been posted by :user for :item.', ['user' => $request->user()->name ?? null, 'item' => $product->name]),
                             'subject' => __('You have a new review.')],
                'action' => 'send',
                'view'   => 'mail.message',
                'to'     => User::where('role', 'admin')->first()->email,
                'subject' => __('You have a new review.')
              ];

              NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));
          }
        }
        elseif($type === 'support')
        {
          if(! $comment = $request->input('comment'))
          {
            return redirect($redirect_url);
          }

          $approved = auth_is_admin() ? 1 : (config('app.auto_approve.support') ? 1 : 0);
          $comment = strip_tags($comment);

          if($request->comment_id) // parent
          {
            if($parent_comment = Comment::where('id', $request->comment_id)->where('parent', null)->where('product_id', $product->id)->first())
            {
              DB::insert("INSERT INTO comments (product_id, user_id, body, approved, parent) VALUES (?, ?, ?, ?, ?)", 
                          [$product->id, $user_id, $comment, $approved, $parent_comment->id]);    
            }
          }
          else
          {
            DB::insert("INSERT INTO comments (product_id, user_id, body, approved) VALUES (?, ?, ?, ?)", 
                        [$product->id, $user_id, $comment, $approved]); 
          }

          if(!$approved)
            $request->session()->put(['comment_response' => __('Your comment is waiting for approval. Thank you!')]);

          if(Auth::check() && !auth_is_admin() && config('app.admin_notifications.comments'))
          {
              $mail_props = [
                'data'   => ['text' => __('A new comment has been posted by :user for :item.', ['user' => $request->user()->name ?? null, 'item' => $product->name]),
                             'subject' => __('You have a new comment.')],
                'action' => 'send',
                'view'   => 'mail.message',
                'to'     => User::where('role', 'admin')->first()->email,
                'subject' => __('You have a new comment.')
              ];

              NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));
          }
        }

        return redirect($redirect_url);
      }

      Self::init_notifications();

      DB::update('UPDATE products USE INDEX(primary) SET views = views+1 WHERE id = ?', [$product->id]);

      $reviews = Review::useIndex('product_id', 'approved')
                          ->selectRaw("reviews.*, users.name, SUBSTR(users.email, 1, LOCATE('@', users.email)-1) as alias_name, CONCAT(users.firstname, ' ', users.lastname) AS fullname, IFNULL(users.avatar, 'default.jpg') AS avatar")
                          ->leftJoin('users', 'users.id', '=', 'reviews.user_id')
                          ->where(['reviews.product_id' => $product->id, 'reviews.approved' => 1])
                          ->orderBy('created_at', 'DESC')->get();

      $comments = Comment::useIndex('product_id', 'approved')
                          ->selectRaw("comments.*, users.name, SUBSTR(users.email, 1, LOCATE('@', users.email)-1) as alias_name, CONCAT(users.firstname, ' ', users.lastname) AS fullname, IFNULL(users.avatar, 'default.jpg') AS avatar, IF(users.role = 'admin', 1, 0) as is_admin, 
                            IF((SELECT COUNT(transactions.id) FROM transactions WHERE transactions.user_id = comments.user_id AND transactions.status = 'paid' AND transactions.refunded = 0 AND transactions.confirmed = 1 AND transactions.products_ids REGEXP CONCAT('\'', comments.product_id, '\'')) > 0, 1, 0) as item_purchased")
                          ->leftJoin('users', 'users.id', '=', 'comments.user_id')
                          ->where(['comments.product_id' => $product->id, 'comments.approved' => 1])
                          ->orderBy('id', 'ASC')->get();


      $similar_products = Product::useIndex('primary', 'category', 'active')
                          ->selectRaw(implode(',', Self::$product_columns).', categories.name as category_name, categories.slug as category_slug')
                          ->leftJoin('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('CONCAT("\'", subcategories.id, "\'")'))
                          ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                          ->leftJoin('licenses', function($join)
                          {
                            $join->on('licenses.item_type', '=', 'products.type')
                                 ->where('licenses.regular', 1);
                          })
                          ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')
                                 ->on('product_price.product_id', '=', 'products.id');
                          })
                          ->where(['products.category' => $product->category_id, 
                                   'products.active' => 1,
                                   'products.for_subscriptions' => 0,
                                   'products.is_dir' => (isFolderProcess() ? 1 : 0)])
                          ->where('products.id', '!=', $product->id)
                          ->groupBy('products.id', 'products.name', 'products.views', 'products.preview', 'products.preview_type',
                                   'products.slug', 'products.updated_at', 'products.hidden_content', 'products.active', 'products.stock', 'products.bpm', 'products.label', 'products.pages', 
                                   'products.authors', 'products.language', 'products.words', 'products.formats',
                                   'products.cover', 'product_price.price', 'products.last_update', 
                                   'category_name', 'category_slug', 'categories.id', 'promotional_price_time', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.is_dir', 'products.for_subscriptions', 'products.type', 'licenses.id', 'licenses.name', 'products.country_city')
                          ->orderByRaw('rand()')
                          ->limit(5)->get();

      if($parents = $comments->where('parent', null)->sortByDesc('id')) // parents comments only
      {
        $children = $comments->where('parent', '!=', null); // children comments only

        // Append children comments to their parents
        $parents->map(function (&$item, $key) use ($children, $request, $product)
        {
          $request->merge(['item_type' => 'comment', 'item_id' => $item->id, 'product_id' => $product->id]);

          $item->reactions = $this->get_reactions($request); 

          $item->children = $children->where('parent', $item->id)->sortBy('created_at');

          foreach($item->children as $children)
          {
            $request->merge(['item_type' => 'comment', 'item_id' => $children->id, 'product_id' => $product->id]);

            $children->reactions = $this->get_reactions($request); 
          }
        });
      }
      
      if($product->country_city)
      {
        $country_city = json_decode($product->country_city);

        $product->country = $country_city->country ?? null;
        $product->city = $country_city->city ?? null;
      }

      if($product->screenshots)
      {
        $product->screenshots = array_reduce(explode(',', $product->screenshots), function($ac, $img)
                                {
                                  $ac[] = asset_("storage/screenshots/{$img}");
                                  return $ac;
                                }, []);
      }

      $product->tags = array_filter(explode(',', $product->tags));

      $product->additional_fields = json_decode($product->additional_fields);

      $product->faq = json_decode($product->faq, true) ?? []; 

      if(count(array_column($product->faq, 'Q')))
      {
        $faqs = [];

        foreach($product->faq as $faq)
        {
          $faqs[] = [
            'question' => $faq['Q'] ?? '',
            'answer' => $faq['A'] ?? ''
          ];
        }

        $product->faq = $faqs;
      }

      $product->faq = arr2obj($product->faq);

      $meta_data->title         = $product->name;
      $meta_data->description   = $product->short_description;
      $meta_data->image         = asset("storage/covers/{$product->cover}");

      return view_('product', [
                  'title'     => mb_ucfirst($product->name),
                  'product'   => $product,
                  'reviews'   => $reviews,
                  'comments'  => $parents, // Parents comments with their children in
                  'similar_products' => $similar_products,
                  'meta_data' => $meta_data,
                  'valid_subscription' => $valid_subscription,
                  'product_prices' => $product_prices
                ]);
    }



    // Redirect old product URLs to new URLs
    public function old_product_redirect(Request $request)
    {
      $product = Product::where(['slug' => $request->slug, 'active' => 1])->first() ?? abort(404);

      return redirect(item_url($product));
    }


    // Trending products
    public static function trending_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('trending', 'active')
                          ->selectRaw(implode(',', Self::$product_columns))
                          ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                          ->leftJoin('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('licenses', function($join)
                            {
                              $join->on('licenses.item_type', '=', 'products.type')
                                   ->where('licenses.regular', 1);
                            })
                            ->leftJoin('product_price', function($join)
                            {
                              $join->on('product_price.license_id', '=', 'licenses.id')
                                   ->on('product_price.product_id', '=', 'products.id');
                            })
                          ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'promotional_price_time', 'products.stock', 'products.bpm', 'products.label',
                                    'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'products.trending','is_dir', 'products.hidden_content', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'products.type', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.country_city')
                          ->havingRaw("active = 1 AND (trending = 1 OR count(transactions.id) > 0) AND is_dir = ?", [isFolderProcess() ? 1 : 0])
                          ->orderByRaw('trending, sales DESC');
                          
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;

      return $returnQueryBuilder ? $products : $products->limit($limit)->get();                                
    }


    // Featured products
    private static function featured_products(bool $returnQueryBuilder, $limit = 15, $category_id = null, $randomize = false)
    {
      $products = Product::useIndex('featured', 'active')
                          ->selectRaw(implode(',', Self::$product_columns))
                          ->leftJoin('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('transactions', 'transactions.products_ids', 'LIKE', \DB::raw("CONCAT(\"%'\", products.id,\"'%\")"))
                          ->leftJoin('licenses', function($join)
                          {
                            $join->on('licenses.item_type', '=', 'products.type')->where('licenses.regular', 1);
                          })
                          ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                          })
                          ->where(['featured' => 1, 'active' => 1, 'is_dir' => isFolderProcess() ? 1 : 0])
                          ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'promotional_price_time', 'products.stock', 'products.type', 'products.bpm', 'products.label', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'products.featured', 'is_dir', 'products.hidden_content', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.country_city');
      
      $products = $category_id ? $products->where('category', $category_id) : $products;
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;
      
      return $returnQueryBuilder ? $products : $products->limit($limit)->get();   
    }



    // Newest products
    private static function newest_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('newest', 'active')
                          ->selectRaw(implode(',', Self::$product_columns).', products.newest')
                          ->leftJoin('transactions', 'transactions.products_ids', 'LIKE', \DB::raw("CONCAT(\"%'\", products.id,\"'%\")"))
                          ->join('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('licenses', function($join)
                            {
                              $join->on('licenses.item_type', '=', 'products.type')->where('licenses.regular', 1);
                            })
                            ->leftJoin('product_price', function($join)
                            {
                              $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                            })
                          ->where(['active' => 1, 'is_dir' => (isFolderProcess() ? 1 : 0)])
                          ->groupBy('products.id', 'products.name','products.views','products.newest','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'is_dir', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'promotional_price_time', 'products.hidden_content', 'products.stock', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'products.type', 'licenses.id', 'licenses.name', 'products.pages', 'products.bpm', 'products.label', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.country_city')
                          ->orderByRaw('products.created_at, products.newest DESC');
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;
      
      if($returnQueryBuilder)
      {
        return $returnQueryBuilder;
      }

      $products = $products->limit($limit)->get();

      foreach($products as &$product)
      {
        $price = is_null($product->promotional_price) ? $product->price : $product->promotional_price;

        $product->price = price($price, false, false, 0, null, null);
      }

      return $products;
    }


    // Free products
    private static function free_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('free', 'active')
                          ->selectRaw(implode(',', Self::$product_columns))
                          ->leftJoin('categories', 'categories.id', '=', 'products.category')
                          ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                          ->leftJoin('licenses', function($join)
                            {
                              $join->on('licenses.item_type', '=', 'products.type')->where('licenses.regular', 1);
                            })
                            ->leftJoin('product_price', function($join)
                            {
                              $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                            })
                          ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                          ->where(['active' => 1, 'is_dir' => (isFolderProcess() ? 1 : 0)])
                          ->where(function ($query)
                          {
                            $query->where('product_price.price', 0)
                                  ->orWhereRaw("CURRENT_DATE between substr(free, 10, 10) and substr(free, 28, 10)");
                          })
                          ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'is_dir', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'promotional_price_time', 'products.hidden_content', 'products.stock', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'products.type', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.bpm', 'products.label', 'products.country_city');
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;
      
      return $returnQueryBuilder ? $products : $products->limit($limit)->get();   
    }


    // Flash products
    private static function flash_products(bool $returnQueryBuilder, $limit = 15, $randomize = false)
    {
      $products = Product::useIndex('free', 'active')
                    ->selectRaw(implode(',', Self::$product_columns))
                    ->leftJoin('categories', 'categories.id', '=', 'products.category')
                    ->leftJoin('categories as subcategories', 'products.subcategories', 'REGEXP', DB::raw('concat("\'", subcategories.id, "\'")'))
                    ->leftJoin('transactions', 'products_ids', 'REGEXP', DB::raw('concat("\'", products.id, "\'")'))
                    ->where(['active' => 1, 'is_dir' => (isFolderProcess() ? 1 : 0)])
                    ->leftJoin('licenses', function($join)
                    {
                      $join->on('licenses.item_type', '=', 'products.type')->where('licenses.regular', 1);
                    })
                    ->leftJoin('product_price', function($join)
                    {
                      $join->on('product_price.license_id', '=', 'licenses.id')->on('product_price.product_id', '=', 'products.id');
                    })
                    ->where('product_price.promo_price', '!=', null)
                    ->whereRaw('promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10))')
                    ->groupBy('products.id', 'products.name','products.views','products.preview', 'products.preview_type','products.slug','products.updated_at','products.active', 'is_dir', 'products.cover','product_price.price','products.last_update', 'categories.name', 'categories.slug', 'promotional_price_time', 'products.hidden_content', 'products.stock', 'categories.id', 'products.tags', 'products.short_description', 'product_price.promo_price', 'products.free', 'products.trending', 'products.for_subscriptions', 'products.type', 'licenses.id', 'licenses.name', 'products.pages', 'products.authors', 'products.language', 'products.words', 'products.formats', 'products.bpm', 'products.label', 'products.country_city');
      
      $products = $randomize ? $products->orderByRaw('RAND()') : $products;
      
      return $returnQueryBuilder ? $products : $products->limit($limit)->get();   
    }



    // Blog 
    public function blog(Request $request)
    {
      $filter     = [];
      $meta_data  = $this->meta_data;

      $meta_data->description = config('app.blog.description');
      $meta_data->title       = config('app.blog.title');
      $meta_data->image       = asset('storage/images/'.(config('app.blog_cover') ?? 'blog_cover.jpg'));

      if($request->category)
      {
        if(!$category = Category::useIndex('slug')->where('slug', $request->category)->first())
          abort(404);

        $posts = Post::useIndex('category')->where(['category' => $category->id, 'active' => 1]);

        $filter = ['name' => 'Category', 'value' => $category->name];

        $meta_data->title       = config('app.name').' Blog - '.$category->name;
        $meta_data->description = $category->description;
        $meta_data->url         = route('home.blog.category', $category->slug);
      }
      elseif($request->tag)
      {
        $posts = Post::useIndex('tags')->where(function ($query) use ($request) {
                                                        $tag = str_replace('-', ' ', $request->tag);

                                                        $query->where('tags', 'LIKE', "%{$request->tag}%")
                                                              ->orWhere('tags', 'like', "%{$tag}%");
                                                   })
                                                   ->where('active', 1);

        $filter = ['name' => 'Tag', 'value' => $request->tag];

        $meta_data->title = config('app.name').' Blog - '.$request->tag;
        $meta_data->url   = route('home.blog.tag', $request->tag);
      }
      elseif($request->q)
      {
        $request->tag = str_replace('-', ' ', $request->tag);
        $posts = Post::useIndex('search', 'active')->where(function ($query) use ($request) {
                                                         $query->where('name', 'like', "%{$request->q}%")
                                                               ->orWhere('tags', 'like', "%{$request->q}%")
                                                               ->orWhere('short_description', 'like', "%{$request->q}%")
                                                               ->orWhere('content', 'like', "%{$request->q}%")
                                                               ->orWhere('slug', 'like', "%{$request->q}%");
                                                     })
                                                     ->where('active', 1);

        $filter = ['name' => 'Search', 'value' => $request->q];

        $meta_data->title = config('app.name').' '.__('Blog').' - '.__('Searching for').' '.$request->q;
        $meta_data->url   = route('home.blog.q', $request->q);
      }
      else
      {
        $posts = Post::useIndex('primary')->where('active', 1);

        $meta_data->url = route('home.blog');
      }

      $posts = $posts->orderBy('id', 'desc')->paginate(9);

      if($filter) settype($filter, 'object');

      $posts_categories = Category::useIndex('`for`')->select('name', 'slug')->where('categories.for', 0)->get();

      $latest_posts = Post::useIndex('primary', 'active')
                      ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                      ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                      ->where('posts.active', 1)->orderBy('updated_at')->limit(5)->get();

      $posts_tags = Post::useIndex('active')->select('tags')->where('active', 1)->orderByRaw('rand()')
                                  ->limit(10)->get()->pluck('tags')->toArray();

      $tags  = [];

      foreach($posts_tags as $tag)
        $tags = array_merge($tags, array_map('trim', explode(',', $tag)));

      $tags = array_unique($tags);

      Self::init_notifications();

      return view_('blog', compact('posts_categories', 'latest_posts', 'tags', 'posts', 'filter', 'meta_data'));
    }


    // BLOG POST
    public function post(string $slug)
    {
      $post = Post::useIndex('slug', 'active')->select('posts.*', 'categories.name AS category', 'posts.category as category_id')
                  ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                  ->where(['posts.slug' => $slug, 'posts.active' => 1])->first() ?? abort(404);

      $meta_data  = $this->meta_data;

      $meta_data->description = $post->short_description;
      $meta_data->title       = $post->name;
      $meta_data->image       = asset('storage/posts/'.$post->cover);

      $post->setTable('posts')->increment('views', 1);

      $latest_posts = Post::useIndex('primary', 'active')
                      ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                      ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                      ->where('posts.id', '!=', $post->id)->where('posts.active', 1)->orderBy('updated_at')->limit(5)->get();

      $related_posts =  Post::useIndex('primary', 'active')
                        ->select('posts.*', 'categories.name as category_name', 'categories.slug as category_slug')
                        ->leftJoin('categories', 'categories.id', '=', 'posts.category')
                        ->where('posts.id', '!=', $post->id)->where('posts.active', 1)
                        ->where('posts.category', $post->category_id)->orderBy('updated_at')->limit(6)->get();


      $posts_categories = Category::useIndex('`for`')->select('name', 'slug')->where('categories.for', 0)->get();
      $posts_tags       = Post::useIndex('active')->select('tags')->where('active', 1)->orderByRaw('rand()')
                                  ->limit(10)->get()->pluck('tags')->toArray();

      $tags  = [];

      foreach($posts_tags as $tag)
        $tags = array_merge($tags, array_map('trim', explode(',', $tag)));

      $tags = array_unique($tags);

      Self::init_notifications();

      return view_('post', compact('post', 'posts_categories', 'latest_posts', 'related_posts', 'tags', 'meta_data'));
    }


    // Profile
    public function profile(Request $request)
    {
      $user = \App\User::find($request->user()->id);

      if($request->method() === 'POST')
      {
        $cashout_methods = implode(',', array_values(config('affiliate.cashout_methods', [])));

        $request->validate([
          'name' => 'string|nullable|max:255|bail',
          'firstname' => 'string|nullable|max:255|bail',
          'lastname' => 'string|nullable|max:255|bail',
          'country' => 'string|nullable|max:255|bail',
          'city' => 'string|nullable|max:255|bail',
          'address' => 'string|nullable|max:255|bail',
          'zip_code' => 'string|nullable|max:255|bail',
          'id_number' => 'string|nullable|max:255|bail',
          'state' => 'string|nullable|max:255|bail',
          'affiliate_name' => 'string|nullable|max:255|bail',
          'cashout_method' => "string|nullable|in:{$cashout_methods}|max:255|bail",
          'paypal_account' => 'string|nullable|email|max:255|bail',
          'bank_account' => 'array|nullable|bail',
          'bank_account.*' => 'nullable|string|bail',
          'phone' => 'string|nullable|max:255|bail',
          'receive_notifs' => 'string|nullable|in:0,1|bail',
          'old_password' => 'string|nullable|max:255|bail',
          'new_password' => 'string|nullable|max:255|bail',
          'avatar' => 'nullable|image'
        ]);

        $user->name       = $request->input('name', $user->name ?? null);
        $user->firstname  = $request->input('firstname', $user->firstname ?? null);
        $user->lastname   = $request->input('lastname', $user->lastname ?? null);
        $user->country    = $request->input('country', $user->country ?? null);
        $user->city       = $request->input('city', $user->city ?? null);
        $user->address    = $request->input('address', $user->address ?? null);
        $user->zip_code   = $request->input('zip_code', $user->zip_code ?? null);
        $user->id_number  = $request->input('id_number', $user->id_number ?? null);
        $user->state      = $request->input('state', $user->state ?? null);
        $user->affiliate_name = $request->input('affiliate_name');
        $user->paypal_account = $request->input('paypal_account');
        $user->bank_account   = json_encode($request->input('bank_account'));
        $user->phone      = $request->input('phone', $user->phone ?? null);
        $user->receive_notifs = $request->input('receive_notifs', $user->receive_notifs ?? '1');
        $user->cashout_method = $request->input('cashout_method');


        if($request->old_password && $request->new_password)
        {
          Validator::make($request->all(), [
            'old_password' => [
              function ($attribute, $value, $fail) 
              {
                  if(! Hash::check($value, auth()->user()->password)) {
                      $fail($attribute.' is incorrect.');
                  }
              }
            ],
          ])->validate();
        
          $user->password = Hash::make($request->new_password);
        }

        if($avatar = $request->file('avatar'))
        {          
          $request->validate(['avatar' => 'image']);

          if(File::exists(public_path("storage/avatars/{$user->avatar}")))
          {
            File::delete(public_path("storage/avatars/{$user->avatar}"));
          }

          $ext  = $avatar->extension();
          $file = $avatar->storeAs('avatars', "{$user->id}.{$ext}", ['disk' => 'public']);

          $user->avatar = pathinfo($file, PATHINFO_BASENAME);
        }

        $user->save();

        $request->session()->flash('profile_updated', __('Done').'!');

        return redirect()->route('home.profile');
      }

      $user = (object)$user->getAttributes();

      $user->fullname = null;

      if($user->firstname && $user->lastname)
      {
        $user->fullname = $user->firstname . ' ' . $user->lastname;
      }

      $user->bank_account = json_decode($user->bank_account);

      $meta_data = $this->meta_data;

      Self::init_notifications();
      
      return view_('user', compact('user', 'meta_data'));
    }


    // Send email verification link
    public function send_email_verification_link(Request $request)
    {
      $notifiable = $request->email ? \App\User::where('email', $request->email)->first() : $request->user();
      $notifiable->sendEmailVerificationNotification();

      return response()->json([
        'status' => true, 
        'message' => __('Please check your :email inbox for a verification link.', ['email' => $request->email])
      ]);
    }


    // User Purchases
    public function purchases(Request $request)
    {
      $products = Product::useIndex('active', 'primary')
                  ->selectRaw('products.id, products.name, products.slug, products.direct_download_link, products.cover, products.is_dir, products.file_host, products.file_name, products.last_update, products.hidden_content, key_s.purchased_at, products.enable_license, key_s.code as key_code, IFNULL(key_s.code, UUID()) as key_code_alt, key_s.id as key_id,
                    ROUND(AVG(reviews.rating)) as rating, transactions.refunded, categories.name as category_name, 
                    categories.slug as category_slug, transactions.status as payment_status')
                  ->join('transactions USE INDEX(user_id)', function($join) 
                  {
                    $join->on('transactions.user_id', '=', DB::raw(Auth::id()))
                         ->where('transactions.is_subscription', 0)
                         ->where('transactions.products_ids', 'LIKE', DB::raw('CONCAT("%\'", products.id, "\'%")'))
                         ->where('transactions.status', 'paid')
                         ->where('transactions.refunded', 0)
                         ->where('transactions.confirmed', 1);
                  })
                  ->leftJoin('reviews', function($join)
                  {
                    $join->on('reviews.product_id', '=', 'products.id')
                         ->where('reviews.user_id', '=', DB::raw(Auth::id()));
                  })
                  ->leftJoin('key_s USE INDEX(product_id, user_id)', function($join)
                  {
                    $join->on('key_s.product_id', '=', 'products.id')
                         ->where('key_s.user_id', '=', DB::raw(Auth::id()));
                  })
                  ->leftJoin('categories', 'categories.id', '=', 'products.category')
                  ->where('products.is_dir', '=', isFolderProcess() ? 1 : 0)
                  ->groupByRaw('key_code_alt')
                  ->orderBy('purchased_at', 'DESC')
                  ->paginate(5);

      $meta_data = $this->meta_data;

      $keycodes = [];

      foreach($products as $product)
      { 
        if($product->key_code)
        {
          $keycodes[$product->key_id] = $product->key_code;
        }

        if($product->file_name)
        {
          create_direct_download_link($product->id);
        }
      }

      Self::init_notifications();

      return view_("user", compact('products', 'keycodes', 'meta_data'));
    }


    // User invoices
    public function invoices(Request $request)
    {
      $invoices = Transaction::useIndex('user_id', 'confirmed')
                  ->select('id', 'reference_id', 'amount', 'created_at', 'details')
                  ->where(['user_id' => Auth::id(), 'confirmed' => 1])
                  ->where('details', '!=', null)
                  ->orderBy('id', 'desc')
                  ->paginate(10);

      foreach($invoices as &$invoice)
      {
        $details = json_decode($invoice->details);
        $currency = $details->currency ?? currency('code');
        $invoice->setAttribute('currency', $currency);
        $invoice->amount = $details->total_amount ?? $invoice->amount;
      }

      $meta_data = $this->meta_data;

      Self::init_notifications();

      return view_("user", compact('invoices', 'meta_data'));
    }


    // Guest page
    public function guest(Request $request)
    {
      $meta_data = $this->meta_data;

      Self::init_notifications();

      return view_("guest", compact('meta_data'));
    }


    // Guest downloads
    public function guest_downloads(Request $request)
    {
      $request->validate(['access_token' => 'required|uuid']);

      $access_token = $request->post('access_token');

      $products = Product::useIndex('active', 'primary')
                  ->selectRaw('products.id, products.name, products.slug, products.direct_download_link, products.cover, products.is_dir, products.file_host, products.file_name, products.last_update, products.hidden_content, MAX(transactions.created_at) as purchased_at, products.enable_license, key_s.code as key_code, IFNULL(key_s.code, UUID()) as key_code_alt, key_s.id as key_id,
                    ROUND(AVG(reviews.rating)) as rating, transactions.refunded, categories.name as category_name, 
                    categories.slug as category_slug, transactions.status as payment_status')
                  ->join('transactions USE INDEX(user_id)', function($join) use($access_token)
                  {
                    $join->where('transactions.is_subscription', 0)
                         ->where('transactions.products_ids', 'LIKE', DB::raw('CONCAT("%\'", products.id, "\'%")'))
                         ->where('transactions.guest_token', $access_token)
                         ->where('transactions.status', 'paid')
                         ->where('transactions.refunded', 0)
                         ->where('transactions.confirmed', 1);
                  })
                  ->leftJoin('reviews', function($join) use($access_token)
                  {
                    $join->on('reviews.product_id', '=', 'products.id')
                         ->where('reviews.user_id', '=', $access_token);
                  })
                  ->leftJoin('key_s USE INDEX(product_id, user_id)', function($join) use($access_token)
                  {
                    $join->on('key_s.product_id', '=', 'products.id')
                         ->where('key_s.user_id', '=', $access_token);
                  })
                  ->leftJoin('categories', 'categories.id', '=', 'products.category')
                  ->where('products.is_dir', '=', isFolderProcess() ? 1 : 0)
                  ->groupByRaw('key_code_alt')
                  ->get()->toArray();

      $keycodes = [];

      foreach($products as $product)
      { 
        if($product['key_code'])
          $keycodes[$product['id']] = $product['key_code'];
      }

      return response()->json(compact('products', 'keycodes'));
    }



    // Guest Download
    public function guest_download(Request $request)
    {
      set_time_limit(0);

      $request->validate(['item_id' => 'required|numeric', 'access_token' => 'required|uuid']);

      $item_id = $request->post('item_id');
      $access_token = $request->post('access_token');

      $item = DB::select("SELECT products.slug, products.file_host, products.file_name, products.updated_at, products.direct_download_link
                          FROM products USE INDEX(primary, active)
                          WHERE products.id = ? AND products.active = 1",
                          [$item_id])[0] ?? abort(404);

      DB::table('transactions')
      ->whereRaw('products_ids REGEXP ?', [wrap_str($item_id, "'")])
      ->where('is_subscription', 0)
      ->where('refunded', 0)
      ->where('status', 'paid')
      ->where('guest_token', $access_token)
      ->exists() ?? abort(404);

      if($item->direct_download_link)
      {
        $response = get_remote_file_content($item->direct_download_link, $item->slug);

        if(isset($response['error']))
        {
          exists_or_abort(null, $response['error']);
        }

        return  response()->streamDownload(function() use($response) 
                {
                    echo $response['content'];
                }, $response['file_name']);
      }

      if(!$item->file_name)
      {
        return back()->with(['user_message' => __("This item doesn't have any file to download.")]);
      }

      if($item->file_host === 'local')
      {
        $extension = pathinfo($item->file_name, PATHINFO_EXTENSION);
        
        if(file_exists(storage_path("app/downloads/{$item->file_name}")))
        {
          return response()->streamDownload(function() use($item, $extension)
          {
            readfile(storage_path("app/downloads/{$item->file_name}"));
          }, "{$item->slug}.{$extension}");
        }
      }
      elseif($item->file_host === 'dropbox')
      {
        try
        {
          return DropBox::download($item->file_name, $item->slug);
        }
        catch(\Exception $e)
        {
          
        }
      }
      elseif($item->file_host === 'google')
      {
        try
        {
          return GoogleDrive::download($item->file_name, $item->slug);
        }
        catch(\Exception $e)
        {

        }
      }
      elseif(preg_match('/^wasabi|amazon_s3$/', $item->file_host))
      {
        try
        {
          $class_name = ['wasabi' => 'Wasabi', 'amazon_s3' => 'AmazonS3'][$item->file_host];

          return call_user_func_array(["\App\Libraries\\{$class_name}", 'download'], [$item->file_name, null, [], $item->slug, $item->updated_at]);
        }
        catch(\Exception $e)
        {

        }
      }
      elseif($item->file_host === 'yandex')
      {
        try
        {
          return YandexDisk::download($item->file_name, $item->slug);
        }
        catch(\Exception $e)
        {

        }
      }

      return redirect()->route('home.guest', ['token' => $access_token]);
    }




    // Download
    public function download(Request $request)
    {
      set_time_limit(0);

      $request->validate(['itemId' => 'required|numeric']);


      $item = DB::select("SELECT products.id, products.slug, products.file_host, products.file_name, products.direct_download_link, 
                          products.updated_at, 
                          (IF(product_price.price = 0 OR CURRENT_DATE between substr(products.free, 10, 10) and substr(products.free, 28, 10), 1, 0)) AS is_free
                          FROM products USE INDEX(primary, active)
                          LEFT JOIN licenses ON licenses.item_type = products.type AND licenses.regular = 1
                          LEFT JOIN product_price ON product_price.license_id = licenses.id AND product_price.product_id = products.id
                          WHERE products.id = ? AND products.active = 1 
                          GROUP BY products.id, products.slug, products.file_host, products.file_name, products.direct_download_link, 
                          products.updated_at, is_free", [$request->itemId])[0] ?? abort(404);


      if(!$item->is_free && !auth_is_admin())
      {
        $item_purchased = DB::table('transactions')
                              ->whereRaw('products_ids REGEXP ?', [wrap_str($request->itemId, "'")])
                              ->where(['is_subscription' => 0, 'refunded' => 0, 'status' => 'paid', 'confirmed' => 1])
                              ->where('user_id', $request->user()->id)
                              ->exists();

        if(!$item_purchased)
        {
          $subscription = DB::select("SELECT user_subscription.id, user_subscription.downloads, subscriptions.limit_downloads,
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
                            [$item->id, $request->user()->id, $request->itemId])[0] ?? [];

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
              User_Subscription::find($subscription->id)->update(['daily_downloads_date' => date('Y-m-d'),
                                                                  'daily_downloads' => 1]);
            }
            else
            {
              User_Subscription::find($subscription->id)->increment('daily_downloads', 1);
            }
          }

          if($subscription->limit_downloads_same_item > 0)
          {
            DB::insert('INSERT INTO subscription_same_item_downloads (subscription_id, product_id, downloads) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE downloads = downloads + 1', [$subscription->id, $item->id, 1]);
          }

          if($subscription->limit_downloads > 0)
          {
            User_Subscription::find($subscription->id)->increment('downloads', 1);
          }
        }
      }

      if($item->direct_download_link)
      {
          $remote_file = get_remote_file_content($item->direct_download_link, $item->slug);

          if(isset($remote_file['error']))
          {
            exists_or_abort(null, $remote_file['error']);
          }

          return  response()->streamDownload(function() use($remote_file) 
                  {
                      echo $remote_file['content'];
                  }, $remote_file['file_name']);
      }


      if($item->file_host === 'local')
      {
        $extension = pathinfo($item->file_name, PATHINFO_EXTENSION);
        
        if(file_exists(storage_path("app/downloads/{$item->file_name}")))
        {
          return response()->streamDownload(function() use($item, $extension)
          {
            readfile(storage_path("app/downloads/{$item->file_name}"));
          }, "{$item->slug}.{$extension}");
        }
      }
      else
      {
        $host_class = [
          'dropbox'   => 'DropBox',
          'google'    => 'GoogleDrive',
          'yandex'    => 'YandexDisk',
          //'onedrive'  => 'OneDrive',
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

      return back();
    }





    public function download_license(Request $request)
    {
      $request->validate(['itemId' => 'required|numeric']);

      $item_id = $request->itemId;
      $product = Product::find($item_id) ?? abort(404);

      $transaction = Transaction::useIndex('user_id')
                      ->whereRaw('products_ids REGEXP ?', [wrap_str($item_id, "'")])
                      ->where([ 'is_subscription' => 0, 
                                'refunded' => 0, 
                                'status' => 'paid', 
                                'confirmed' => 1])
                      ->orderBy('id', 'desc');

      if(!Auth::check())
      {
        $request->validate(['access_token' => 'required|uuid']);

        $transaction->where('guest_token', $request->post('access_token'));
      }
      else
      {
        $this->middleware('auth');
      }

      $transaction = $transaction->first();

      if($transaction)
      {
        if($licenses = json_decode($transaction->licenses))
        {
          if(property_exists($licenses, $item_id))
          {
            $license_key = $licenses->$item_id;
            $file_name = __('LICENSE KEY - :for', ['for' => $product->name]);

            return response()->streamDownload(function() use($license_key)
            {
              echo $license_key;
            }, "{$file_name}.txt")->send();
          }
        }
      }

      return back();
    }



    // Favorites
    public function favorites(Request $request)
    {
        Self::init_notifications();

        $this->meta_data->name  = config('app.name');
        $this->meta_data->title = __(':app_name - My Collection', ['app_name' => config('app.name')]);

        return view_('user', ['meta_data' => $this->meta_data]);
    }




    // Support
    public function support(Request $request)
    {
      if($request->method() === 'POST')
      {
        $rules = [
          'email' => 'required|email|bail',
          'subject' => 'required|bail',
          'message' => 'required'
        ];

        if(captcha_is_enabled('contact'))
        {
          if(captcha_is('mewebstudio'))
          {
            $rules['captcha'] = 'required|captcha';
          }
          elseif(captcha_is('google'))
          {
            $rules['g-recaptcha-response'] = 'required';
          }
        }

        $request->validate($rules, [
            'g-recaptcha-response.required' => __('Please verify that you are not a robot.'),
            'captcha.required' => __('Please verify that you are not a robot.'),
            'captcha.captcha' => __('Wrong captcha, please try again.'),
        ]);

        $user_email = $request->input('email');

        $email = Support_Email::insertIgnore(['email' => $user_email]);

        if(!($email->id ?? null))
        {
          $email = Support_Email::where('email', $user_email)->first();
        }

        $support = new Support();

        $support->email_id = $email->id;
        $support->subject  = strip_tags($request->input('subject'));
        $support->message  = strip_tags($request->input('message'));

        $support->save();

        $mail_props = [
          'data'   => ['subject' => $support->subject, 'text' => $support->message],
          'action' => 'send',
          'view'   => 'mail.message',
          'to'     => config('mail.mailers.smtp.username'),
          'subject' => $support->subject,
          'reply_to' => $user_email,
          'forward_to' => config('mail.forward_to')
        ];

        NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));

        $request->session()->flash('support_response', __('Message sent successfully'));

        return redirect()->route('home.support');
      }

      $faqs = Faq::useIndex('active')->where('active', 1)->get();

      $meta_data = $this->meta_data;

      $meta_data->url   = route('home.support');
      $meta_data->title = __('Support');

      Self::init_notifications();

      return view_('support', compact('faqs', 'meta_data'));
    }



    // User subscriptions
    public function user_subscriptions(Request $request)
    {
      $user_subscriptions = User_Subscription::useIndex('user_id')
                            ->selectRaw("subscriptions.name, user_subscription.id, user_subscription.downloads, subscriptions.limit_downloads, user_subscription.starts_at, user_subscription.ends_at,
                              user_subscription.daily_downloads, subscriptions.limit_downloads_per_day,
                              ((user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP > user_subscription.ends_at) OR
                              (subscriptions.limit_downloads > 0 AND user_subscription.downloads >= subscriptions.limit_downloads) OR 
                              (subscriptions.limit_downloads_per_day > 0 AND user_subscription.daily_downloads >= subscriptions.limit_downloads_per_day AND user_subscription.daily_downloads_date = CURDATE())) AS expired, transactions.status = 'paid' as payment_status")
                            ->join('subscriptions', 'subscriptions.id', '=', 'user_subscription.subscription_id')
                            ->join('transactions USE INDEX(primary)', 'user_subscription.transaction_id', '=', 'transactions.id')
                            ->where('user_subscription.user_id', auth()->user()->id)
                            ->orderBy('user_subscription.starts_at', 'DESC')
                            ->paginate(5);

      $meta_data = $this->meta_data;

      Self::init_notifications();

      return view_('user', compact('meta_data', 'user_subscriptions'));
    }


    // Pricing
    public function subscriptions(Request $request)
    {
      $subscriptions = Subscription::useIndex('position')->orderBy('position', 'asc')->get();
      $meta_data = $this->meta_data;

      $meta_data->url   = route('home.subscriptions');
      $meta_data->title = __('Pricing - :app_name', ['app_name' => config('app.name')]);
      $active_subscription = null;

      if(Auth::check() && !config('app.subscriptions.accumulative'))
      {
        $user_subscription =  User_Subscription::useIndex('user_id', 'subscription_id')
                              ->select('user_subscription.id')
                              ->join('subscriptions USE INDEX(primary)', 'subscriptions.id', '=', 'user_subscription.subscription_id')
                              ->join('transactions USE INDEX(products_ids, is_subscription)', function($join)
                              {
                                $join->on('transactions.products_ids', '=', DB::raw('QUOTE(subscriptions.id)'))
                                     ->where('transactions.is_subscription', '=', 1);
                              })
                              ->where('user_subscription.user_id', Auth::id())
                              ->whereRaw("user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP < user_subscription.ends_at")
                              ->where(function($query)
                              {
                                $query->where('transactions.refunded', '0')
                                      ->orWhere('transactions.refunded', null);
                              })
                              ->first();

        $active_subscription = $user_subscription ? true : false;
      }

      Self::init_notifications();

      return view_('pricing', compact('meta_data', 'subscriptions', 'active_subscription'));
    }




    // Checkout
    public function checkout(Request $request)
    {
      if(Session::has('transaction_details') && ($request->query('token')))
      {
          $transaction_details = Session::pull('transaction_details');
          $payment_processor = Session::pull('payment_processor');
          $cart              = Session::pull('cart');
          $coupon            = Session::pull('coupon');
          $subscription_id   = Session::pull('subscription_id');
          $products_ids      = Session::pull('products_ids');

          $transaction = new Transaction;

          $transaction->reference_id      = generate_transaction_ref();
          $transaction->user_id           = Auth::check() ? Auth::id() : null;
          $transaction->updated_at        = date('Y-m-d H:i:s');
          $transaction->processor         = $payment_processor;
          $transaction->details           = json_encode($transaction_details, JSON_UNESCAPED_UNICODE);
          $transaction->amount            = $transaction_details['total_amount'];
          $transaction->discount          = $coupon->coupon->discount ?? 0;
          $transaction->exchange_rate     = $transaction_details['exchange_rate'] ?? 1;
          $transaction->guest_token       = Auth::check() ? null : uuid6();
          $transaction->items_count       = count($cart);
          $transaction->status            = 'canceled';

          if(($transaction_details['currency'] != config('payments.currency_code')) && $transaction->exchange_rate != 1)
          {
            $transaction->amount = format_amount($transaction_details['total_amount'] / $transaction->exchange_rate, true);
          }

          if($coupon->status)
          {
            $transaction->coupon_id = $coupon->coupon->id;
          }

          if($subscription_id)
          {
            $subscription = array_shift($cart);

            $subscription = Subscription::find($subscription->id) ?? abort(404);

            $transaction->is_subscription = 1;
            $transaction->products_ids    = wrap_str($subscription->id);
            $transaction->guest_token     = null;
            $transaction->items_count     = 1;
          }
          else
          {
            $transaction->products_ids = implode(',', array_map('wrap_str', $products_ids));
          }

          $query = $request->query();

          if($payment_processor === 'paypal')
          {
              unset($query['token']);

              $order_details = (new PayPalCheckout)->order_details($request->token);

              $response = json_decode($order_details);

              if(property_exists($response, 'name'))
                return redirect()->route('home');

              $transaction->order_id          = $response->id;
              $transaction->transaction_id    = null;
              $transaction->reference_id      = $response->purchase_units[0]->reference_id;
              $transaction->payment_url       = collect($response->links)->where('rel', 'approve')->first()->href ?? null;
          }

          $transaction->save();

          return redirect()->route('home.checkout', $query);
      }

      $meta_data = $this->meta_data;

      $meta_data->name  = __(':app_name - Checkout', ['app_name' => config('app.name')]);
      $meta_data->description = __('Checkout');

      $type = $request->query('type');

      Self::init_notifications();

      $payment_processors = collect(config('payments'))->where('enabled', 'on');

      $payment_processor = $payment_processors->count() > 1 ? null : $payment_processors->first();

      if(strtolower($type) === 'subscription')
      { 
        $subscription_id    = $request->query('id') ?? abort(404);
        $subscription_name  = $request->query('slug') ?? abort(404);
        $subscription       = Subscription::find($request->id) ?? abort(404);

        $meta_data->title = __(":subscription_name subscription - :app_name", ['subscription_name' => $subscription->name, 'app_name' => config('app.name')]);;

        $meta_data->url   = pricing_plan_url($subscription);
        $meta_data->title = __(':app_name - Subscription checkout', ['app_name' => config('app.name')]);

        if(!config('app.subscriptions.accumulative'))
        {
          $active_subscription =  User_Subscription::useIndex('user_id', 'subscription_id')
                              ->select('user_subscription.id')
                              ->join('subscriptions USE INDEX(primary)', 'subscriptions.id', '=', 'user_subscription.subscription_id')
                              ->join('transactions USE INDEX(products_ids, is_subscription)', function($join)
                              {
                                $join->on('transactions.products_ids', '=', DB::raw('QUOTE(subscriptions.id)'))
                                     ->where('transactions.is_subscription', '=', 1);
                              })
                              ->where('user_subscription.user_id', Auth::id())
                              ->whereRaw("user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP < user_subscription.ends_at")
                              ->where(function($query)
                              {
                                $query->where('transactions.refunded', '0')
                                      ->orWhere('transactions.refunded', null);
                              })
                              ->first();

          if($active_subscription ?? false)
          {
            return redirect('/')->with(['user_message' => __("It's not possible to subscribe to another membership plan while your previous one has not expired yet.")]);
          }
        }

        $subscription->price = price($subscription->price, false, false, 0, null, null);

        return view_('checkout.subscription', [
          'title'             => $meta_data->title,
          'meta_data'         => $meta_data,
          'subscription'      => $subscription,
          'payment_processor' => $payment_processor['name'] ?? null
        ]);
      }
      else
      {
        $meta_data->title = __(':app_name - Checkout', ['app_name' => config('app.name')]);
        $meta_data->url = route('home.checkout');

        return view_('checkout.shopping_cart', [
          'title'             => $meta_data->title,
          'meta_data'         => $meta_data,
          'payment_processor' => $payment_processor['name'] ?? null
        ]);
      }
    }


    public function checkout_error(Request $request)
    {
      $message = session('message') ?? abort(404);

      $meta_data = $this->meta_data;

      $meta_data->url   = '';
      $meta_data->title = __('Payment failed');

      Self::init_notifications();

      return view_('checkout.failure', compact('message', 'meta_data'));
    }



    // List Product folder To Preview Its Files (POST)
    public function product_folder_async(Request $request)
    {
      $request->validate([
        'id' => 'required|numeric',
        'slug' => 'required|string'
      ]);

      config('filehosts.working_with') == 'folders' || abort(404);

      $item = Product::useIndex('primary')->select('file_name', 'file_host')
                                          ->where(['slug' => $request->slug, 'id' => $request->id])->first() ?? abort(404);

      if($item->file_host === 'google')
      {
        $files_list = GoogleDrive::list_folder($item->file_name)->original['files_list'] ?? [];
      }
      /*if($item->file_host === 'onedrive')
      {
        $files_list = OneDrive::list_folder($item->file_name)->original['files_list'] ?? [];
      }*/
      elseif($item->file_host === 'dropbox')
      {
        $files_list = DropBox::list_folder($item->file_name)->original['files_list'] ?? [];
      }
      elseif($item->file_host === 'local')
      {
        $zip        = new ZipArchive;
        $files_list = ['files' => []];
        $item_file  = get_main_file($item->file_name);

        if($zip->open($item_file) === TRUE)
        {
          for($i = 1; $i < $zip->numFiles; $i++ )
          { 
              $stat = $zip->statIndex($i); 

              $files_list['files'][] = ['name' => File::basename($stat['name']), 'mimeType' => File::extension($stat['name'])];
          }
        }
      }
      else
      {
        $files_list = [];
      }

      return response()->json($files_list);
    }



    // List Product folder To Download Its Files (GET)
    public function product_folder_sync(Request $request)
    {
      $request->id && $request->slug || abort(404);

      config('filehosts.working_with') === 'folders' || abort(404);

      if(!Auth::check() && !config('payments.guest_checkout') && $request->query('guest_token'))
      {
        abort(404);
      }

      $user_id = Auth::id() ?? $request->query('guest_token') ?? '';

      $product = Product::by_id($request->id, $user_id) ?? abort(404);

      $product->is_dir || abort(403, __('Working with folders while :item_name is not a folder.', ['item_name' => $product->name]));

      if(!$product->free && !$product->purchased && !auth_is_admin())
      {
        abort(404);
      }

      $meta_data = $this->meta_data;

      $meta_data->image       = asset_("storage/covers/{$product->cover}");
      $meta_data->title       = $product->name;
      $meta_data->description = $product->short_description;

      $files_list = [];

      if($product->file_host === 'google')
      {
        $response = GoogleDrive::list_folder($product->file_name)->original['files_list'];

        $files_list[] = $response->files ?? [];

        while($response->nextPageToken ?? null)
        {
          $response = GoogleDrive::list_folder($product->file_name, $response->nextPageToken)->original['files_list'];

          $files_list[] = $response->files ?? [];
        }

        $files_list = array_merge(...$files_list);
      }
     /* if($product->file_host === 'onedrive')
      {
        $files_list = OneDrive::list_folder($product->file_name)->original['files_list']->files ?? [];
      }*/
      elseif($product->file_host === 'dropbox')
      {
        $files_list = DropBox::list_folder($product->file_name)->original['files_list']['files'] ?? [];
      }
      elseif($product->file_host === 'local')
      {
        $zip        = new ZipArchive;
        $item_file  = get_main_file($product->file_name);

        if($zip->open($item_file) === TRUE)
        {
          for($i = 1; $i < $zip->numFiles; $i++ )
          { 
              $stat = $zip->statIndex($i); 

              $files_list[] = (object)['id' => File::basename($stat['name']), 'name' => File::basename($stat['name']), 'mimeType' => File::extension($stat['name'])];
          }
        }
      }

      return view_('folder',  ['product'     => $product,
                               'title'       => mb_ucfirst($product->name),
                               'files_list'  => $files_list,
                               'meta_data'   => $meta_data]);
    }



    // Download Folder File (POST)
    public function product_folder_sync_download(Request $request)
    {
      (config('filehosts.working_with') === 'folders' && $request->file_name) || abort(404);

      $item = DB::select("SELECT products.id, products.slug, products.file_host, products.file_name, 
                          (product_price.price = 0 || free IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN SUBSTR(products.free, 10, 10) and SUBSTR(products.free, 28, 10)) AS free
                          FROM products USE INDEX(primary, active)
                          LEFT JOIN licenses ON licenses.item_type = products.`type` AND licenses.regular = '1'
                          LEFT JOIN product_price ON product_price.license_id = licenses.id AND product_price.product_id = products.id
                          WHERE products.slug = ? AND products.id = ? AND products.is_dir = 1 AND products.active = 1
                          GROUP BY products.id, products.slug, products.file_host, products.file_name, product_price.price, free", [$request->slug, $request->id])[0] ?? abort(404);


      if(!$item->free && !auth_is_admin())
      {
        $item_purchased = DB::table('transactions')
                              ->whereRaw('products_ids REGEXP ?', [wrap_str($item->id, "'")])
                              ->where(['is_subscription' => 0, 'refunded' => 0, 'status' => 'paid', 'confirmed' => 1])
                              ->where(function($query) use($request)
                              {
                                $query->where('user_id', Auth::id())
                                      ->orWhere('guest_token', $request->query('guest_token'));
                              })
                              ->exists();

        if(!$item_purchased && Auth::check())
        {
          $subscription = DB::select("SELECT user_subscription.id, user_subscription.downloads, subscriptions.limit_downloads,
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
                            [$item->id, Auth::id(), $item->id])[0] ?? [];

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
              User_Subscription::find($subscription->id)->update(['daily_downloads_date' => date('Y-m-d'),
                                                                  'daily_downloads' => 1]);
            }
            else
            {
              User_Subscription::find($subscription->id)->increment('daily_downloads', 1);
            }
          }

          if($subscription->limit_downloads_same_item > 0)
          {
            DB::insert('INSERT INTO subscription_same_item_downloads (subscription_id, product_id, downloads) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE downloads = downloads + 1', [$subscription->id, $item->id, 1]);
          }

          if($subscription->limit_downloads > 0)
          {
            User_Subscription::find($subscription->id)->increment('downloads', 1);
          }
        }
      }

      if($item->file_host === 'local')
      {
          $zip        = new ZipArchive;
          $item_file  = get_main_file($item->file_name);

          $zipper = new \Madnest\Madzipper\Madzipper;
          $zipper->make($item_file);

          $files = $zipper->listFiles("/{$request->file_name}$/i");
          $file_to_download = $files[0] ?? abort(404);
          
          return  response()->streamDownload(function() use($file_to_download, $zipper)
                  {
                    echo $zipper->getFileContent($file_to_download);
                  }, $request->file_name);
      }
      else
      {
          $host_class = [
            'dropbox'  => 'DropBox',
            'google'   => 'GoogleDrive',
            //'onedrive' => 'OneDrive'
          ];

          $class_name = $host_class[$item->file_host];

          try
          {
            return call_user_func_array(["\App\Libraries\\{$class_name}", 'download'], [$request->file_name, $request->file_client_name]);
          }
          catch(\Exception $e)
          {
            
          }          
      }

      return back();
    }


    // Newsletter
    public function subscribe_to_newsletter(Request $request)
    {
      $subscription = Newsletter_Subscriber::insertIgnore(['email' => strip_tags($request->email)]);

      $request->session()->flash('newsletter_subscription_msg', ($subscription->id ?? null) 
                                                                ? __('Subscription done')
                                                                : __('You are already subscribed to our newsletter'));

      return redirect(($request->redirect ?? '/') . '#footer');
    }


    // Newsletter / unsubscribe
    public function unsubscribe_from_newsletter(Request $request)
    {
      if($request->isMethod('POST'))
      {        
        $request->validate(['newsletter_email' => 'required|email']);

        DB::delete("DELETE FROM newsletter_subscribers WHERE email = ?", [$request->post('newsletter_email')]);

        return redirect()->route('home.unsubscribe_from_newsletter')->with(['unsubscribed' => true]);
      }

      return view('mail.unsubscribe');
    }



    private function set_home_categories($limit = 20)
    {
      $categories    = config('categories.category_parents');
      $subcategories = config('categories.category_children');

      if($categories && $subcategories)
      {
        $_categories  = [];

        foreach($categories as $category)
        {
          if(!key_exists($category->id, array_keys($subcategories)))
            continue;

          foreach($subcategories[$category->id] as $subcategory)
          {
            $_categories[] = (object)['name' => $subcategory->name, 
                                      'url' => route('home.products.category', [$category->slug, $subcategory->slug])];
          }
        }

        shuffle($_categories);

        $_categories = array_slice($_categories, 0, $limit);

        Config::set('home_categories', $_categories);
      }
    }


    public function notifications(Request $request)
    {
      Self::init_notifications();

      $notifications = Notification::useIndex('users_ids', 'updated_at')
                            ->selectRaw("products.id as product_id, products.name, products.slug, notifications.updated_at, notifications.id, `for`,
                                          CASE
                                            WHEN `for` = 0 THEN IFNULL(products.cover, 'default.png')
                                            WHEN `for` = 1 OR `for` = 2 THEN IFNULL(users.avatar, 'default.jpg')
                                          END AS `image`,
                                          CASE notifications.`for`
                                            WHEN 0 THEN 'New version is available for'
                                            WHEN 1 THEN 'Your comment has been approved for'
                                            WHEN 2 THEN 'Your review has been approved for'
                                          END AS `text`,
                                          IF(users_ids LIKE CONCAT('%|', ?,':0|%'), 0, 1) AS `read`", [Auth::id()])
                            ->leftJoin('products', 'products.id', '=', 'notifications.product_id')
                            ->leftJoin('users', 'users.id', '=', DB::raw(Auth::id()))
                            ->where('users_ids', 'REGEXP', '\|'.Auth::id().':(0|1)\|')
                            ->where('products.slug', '!=', null)
                            ->orderBy('updated_at', 'desc')
                            ->paginate(5);

      return view_('user', ['notifications' => $notifications, 'meta_data' => $this->meta_data]);
    }
    



    public function notifications_read(Request $request)
    {
      ctype_digit($request->notif_id) || abort(404);

      $user_id = Auth::id();

      return DB::update("UPDATE notifications SET users_ids = REPLACE(users_ids, CONCAT('|', ?, ':0|'), CONCAT('|', ?, ':1|')) 
                          WHERE users_ids REGEXP CONCAT('/|', ? ,':0|/') AND id = ?",
                          [$user_id, $user_id, $user_id, $request->notif_id]);
    }


    public function add_to_cart_async(Request $request)
    {
      ctype_digit($request->input('item.id')) || abort(404);

      $item = (object)$request->post('item');

      $product = Product::useIndex('primary, active')
                  ->selectRaw('products.id, products.name, products.slug, products.cover, products.stock, categories.name as category_name, licenses.id as license_id, 
                    (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id AND key_s.user_id IS NULL) as `remaining_keys`,
                    (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id) as has_keys,
                    licenses.name as license_name, licenses.regular as regular_license, products.minimum_price,
                          CASE
                            WHEN product_price.`promo_price` IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10)))
                            THEN product_price.promo_price
                            ELSE
                            NULL
                          END AS `promotional_price`,
                          IF(product_price.`promo_price` IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10), promotional_price_time, null) AS promotional_price_time,
                          product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) AS free_item,
                          IF(product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) = 1, 0, product_price.price) AS price')
                 ->join('categories', 'categories.id', '=', 'products.category')
                 ->join('licenses', 'licenses.id', '=', DB::raw($item->license_id))
                 ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')
                                 ->on('product_price.product_id', '=', 'products.id');
                          })
                 ->where(['products.active' => 1, 'products.id' => $item->id, 'products.for_subscriptions' => 0])
                 ->first();

      if(!$product)
      {
        return;
      }

      if(is_numeric($product->stock) && $product->stock == 0 || ($product->has_keys > 0 && $product->remaining_keys == 0))
      {
        return;
      }

      if($product->minimum_price && ($item->custom_price ?? null))
      {
        $product->price = ($item->custom_price >= $product->minimum_price) ? $item->custom_price : $product->minimum_price;
        $product->custom_price = $item->custom_price;
      }
      else
      {
        $product->price = $product->promotional_price ? $product->promotional_price : $product->price;
      }

      $product->cover     = asset("storage/covers/{$product->cover}");
      $product->url       = item_url($product);

      $product->price = price($product->price, false, false, 0, null, null);

      return response()->json(['product' => $product]);
    }


    public function update_price(Request $request)
    {
      $request->validate(['items' => 'array|required']);

      $items = array_filter($request->post('items'));

      $ids = array_column($items, 'id');

      $products = Product::useIndex('primary, active')
                  ->selectRaw('products.id, products.name, licenses.id as license_id, licenses.name as license_name, products.minimum_price,
                          CASE
                            WHEN product_price.`promo_price` IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10)))
                            THEN product_price.promo_price
                            ELSE
                            NULL
                          END AS `promotional_price`,
                          IF(product_price.`promo_price` IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10), promotional_price_time, null) AS promotional_price_time,
                          product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) AS free_item,
                          IF(product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) = 1, 0, product_price.price) AS price')
                  ->join('categories', 'categories.id', '=', 'products.category')
                  ->join('licenses', function($join) use($items)
                  {
                    $join->on('licenses.item_type', '=', 'products.type')
                         ->whereIn('licenses.id', array_column($items, 'license_id'));
                  })
                  ->leftJoin('product_price', function($join)
                  {
                    $join->on('product_price.license_id', '=', 'licenses.id')
                         ->on('product_price.product_id', '=', 'products.id');
                  })
                  ->where('products.active', 1)
                  ->whereIn('products.id', $ids)
                  ->get();


      if($products->count())
      {
        foreach($products as $product)
        {
          foreach($items as &$item)
          {
            if($item['id'] == $product->id && ($item['license_id'] ?? null) == $product->license_id)
            {
              if($product->minimum_price && ($item['custom_price'] ?? null))
              {
                $product->price = ($item['custom_price'] >= $product->minimum_price) ? $item['custom_price'] : $product->minimum_price;
              }
              else
              {
                $product->price = $product->promotional_price ? $product->promotional_price : $item['price'];
              }

              $item['price'] = price($product->price, false, false, 0, null, null);

              if($item['promotional_price'] > 0)
              {
                $item['promotional_price'] = price($product->promotional_price, false, false, 0, null, null);
              }
            }
          }
        }
      }

      return response()->json(['items' => json_decode(json_encode($items), true)]);
    }


                                    
    public static function init_notifications()
    {
      $notifications = [];

      if($user_id = Auth::id())
      {
        $notifications = DB::select("SELECT products.id as product_id, products.name, products.slug, notifications.updated_at, 
                                      notifications.id, `for`,
                                        CASE
                                          WHEN `for` = 0 THEN IFNULL(products.cover, 'default.png')
                                          WHEN `for` = 1 OR `for` = 2 THEN IFNULL(users.avatar, 'default.jpg')
                                        END AS `image`,
                                        CASE notifications.`for`
                                          WHEN 0 THEN 'New release is available for :product_name'
                                          WHEN 1 THEN 'Your comment has been approved for :product_name'
                                          WHEN 2 THEN 'Your review has been approved for :product_name'
                                        END AS `text`
                                       FROM notifications USE INDEX (users_ids, updated_at)
                                       JOIN products ON products.id = notifications.product_id
                                       JOIN users ON users.id = ?
                                       WHERE users_ids REGEXP CONCAT('/|', ? ,':0|/')
                                       ORDER BY updated_at DESC
                                       LIMIT 5", [$user_id, $user_id]);

        config(['notifications' => $notifications]);
      }

      return $notifications;
    }
    

    public function export_invoice(Request $request)
    {
      $transaction_id = $request->itemId ?? abort(404);
      $transaction = Transaction::find($transaction_id) ?? abort(404);
      $buyer = User::find($transaction->user_id) ?? abort(404);

      if(!$details = json_decode($transaction->details, true))
      {
        return back();
      }

      $items = array_filter($details['items'], function($k)
      {
        return is_numeric($k);
      }, ARRAY_FILTER_USE_KEY);

      $fee       = $details['items']['fee']['value'] ?? 0;
      $tax       = $details['items']['tax']['value'] ?? 0;
      $discount  = $details['items']['discount']['value'] ?? 0;
      $subtotal  = array_sum(array_column($items, 'value'));
      $total_due = $details['total_amount'];
      $currency  = $details['currency'];
      $refunded  = $transaction->refunded;
      $reference = $transaction->reference_id ?? $transaction->order_id ?? $transaction->transaction_id;
      $is_subscription = $transaction->is_subscription;
      $custom_amount = $transaction->custom_amount;

      $pdf = \PDF::loadView('invoice', compact('items', 'fee', 'tax', 'discount', 'subtotal', 'currency', 'is_subscription',
                                              'total_due', 'reference', 'transaction', 'buyer', 'refunded', 'custom_amount'));
      
      return $pdf->download('invoice.pdf'); // stream | download
    }



    public function save_reaction(Request $request)
    {
      $this->middleware('auth');

      $request->validate([
        'product_id' => 'required|numeric',
        'item_id' => 'required|numeric',
        'item_type' => 'required|string',
        'reaction' => 'required|string|max:255'
      ]);

      $res = DB::insert("INSERT INTO reactions (product_id, item_id, item_type, user_id, name) VALUES (?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE name = ?", 
                  [$request->product_id, $request->item_id, $request->item_type, \Auth::id(), $request->reaction, $request->reaction]);

      $reactions = $this->get_reactions($request);

      return response()->json(['status' => $res, 'reactions' => $reactions]);
    }


    // App installation
    public function install(Request $request)
    {
      if(config('app.installed'))
      {
        return redirect('/');
      }

      if($request->method() === 'POST')
      {        
        $request->validate([
          'database.*'          => 'string|required',
          'site.name'           => 'string|required',
          'site.title'          => 'string|required',
          'site.items_per_page' => 'string|numeric|gt:0',
          'site.purchase_code'  => 'string|required',
          'admin.username'      => 'required',
          'admin.email'         => 'required|email',
          'admin.password'      => 'required|max:255',
          'admin.avatar'        => 'nullable|image',
        ]);

        /** CREATE DATABASE CONNECTION STARTS **/
          $db_params = $request->input('database');

          Config::set("database.connections.mysql", array_merge(config('database.connections.mysql'), $db_params));

          try 
          {
            DB::connection()->getPdo();
          }
          catch (\Exception $e)
          {
            $validator = Validator::make($request->all(), [])
                         ->errors()->add('Database', $e->getMessage());

            return redirect()->back()->withErrors($validator)->withInput();
          }
        /** CREATE DATABASE CONNECTION ENDS **/


        /** CREATE DATABASE TABLES STARTS **/
          DB::transaction(function()
          {
            DB::unprepared(File::get(base_path('database/db_tables.sql')));
          });
        /** CREATE DATABASE TABLES ENDS **/



        /** SETTING .ENV VARS STARTS **/
          $env =  array_reduce(file(base_path('.env'), FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES), 
                    function($carry, $item)
                    {
                      list($key, $val) = explode('=', $item, 2);

                      $carry[$key] = $val;

                      return $carry;
                    }, []);

          $env['DB_HOST']       = wrap_str($db_params['host']);
          $env['DB_DATABASE']   = wrap_str($db_params['database']);
          $env['DB_USERNAME']   = wrap_str($db_params['username']);
          $env['DB_PASSWORD']   = wrap_str($db_params['password']);
          $env['APP_NAME']      = wrap_str($request->input('site.name'));
          $env['APP_URL']       = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}";
          $env['APP_INSTALLED'] = 'true';
          $env['PURCHASE_CODE'] = wrap_str($request->input('site.purchase_code'));

          foreach($env as $k => &$v)
            $v = "{$k}={$v}";

          file_put_contents(base_path('.env'), implode("\r\n", $env));
        /** SETTING .ENV VARS ENDS **/


        /** CREATE ADMIN USER STARTS **/
          if(!$user = \App\User::where('email', $request->input('admin.email'))->first())
          {
            $user = new \App\User;

            $user->name = $request->input('admin.username');
            $user->email = $request->input('admin.email');
            $user->password = Hash::make($request->input('admin.password'));
            $user->email_verified_at = date('Y-m-d');
            $user->role = 'admin';
            $user->avatar = 'default.jpg';

            // Avatar
            if($avatar_file = $request->file('admin.avatar'))
            {
              $user_auto_inc_id = DB::select("SHOW TABLE STATUS LIKE 'users'")[0]->Auto_increment;

              $ext    = $avatar_file->extension();
              $avatar = $avatar_file->storeAs('avatars', "{$user_auto_inc_id}.{$ext}", ['disk' => 'public']);

              $user->avatar = pathinfo($avatar, PATHINFO_BASENAME);
            }

            $user->save();
          }
        /** CREATE ADMIN USER END **/


        $settings = Setting::first();

        /** GENERAL SETTINGS STARTS **/
        //----------------------------
          $general_settings = json_decode($settings->general);

          $general_settings->name           = $request->input('site.name');
          $general_settings->title          = $request->input('site.title');
          $general_settings->description    = $request->input('site.description');
          $general_settings->items_per_page = $request->input('site.items_per_page');
          $general_settings->timezone       = $request->input('site.timezone');

          $settings->general = json_encode($general_settings);
        /** GENERAL SETTINGS ENDS **/


        /** MAILER SETTINGS STARTS **/
        //----------------------------
          $mailer_settings = json_decode($settings->mailer);

          $mailer_settings->mail = json_encode($request->input('mailer.mail'));

          $mailer_settings = json_encode($mailer_settings);
        /** MAILER SETTINGS ENDS **/


        $settings->save();

        Auth::loginUsingId($user->id, true);

        return redirect()->route('admin');
      }

      generate_app_key();

      $mysql_user_version = ['distrib' => '', 'version' => null, 'compatible' => false];

      if(function_exists('exec') || function_exists('shell_exec'))
      {
        $mysqldump_v = function_exists('exec') ? exec('mysqldump --version') : shell_exec('mysqldump --version');

        if($mysqld = str_extract($mysqldump_v, '/Distrib (?P<destrib>.+),/i'))
        {
          $destrib = $mysqld['destrib'] ?? null;

          $mysqld = explode('-', mb_strtolower($destrib), 2);

          $mysql_user_version['distrib'] = $mysqld[1] ?? 'mysql';
          $mysql_user_version['version'] = $mysqld[0];

          if($mysql_user_version['distrib'] == 'mysql' && $mysql_user_version['version'] >= 5.6)
          {
            $mysql_user_version['compatible'] = true;
          }
          elseif($mysql_user_version['distrib'] == 'mariadb' && $mysql_user_version['version'] >= 10)
          {
            $mysql_user_version['compatible'] = true;
          }
        }
      }
      
      $requirements = [
        "php" => ["version" => 7.3, "current" => phpversion()],
        "mysql" => ["version" => 5.6, "current" => $mysql_user_version],
        "php_extensions" => [
          "curl" => false,
          "fileinfo" => false,
          "intl" => false,
          "json" => false,
          "mbstring" => false,
          "openssl" => false,
          "mysqli" => false,
          "zip" => false,
          "ctype" => false,
          "dom" => false,
          "calendar" => false,
          "xml" => false,
          "xsl" => false,
        ],
      ];

      $php_loaded_extensions = get_loaded_extensions();

      foreach($requirements['php_extensions'] as $name => &$enabled)
      {
          $enabled = in_array($name, $php_loaded_extensions);
      }

      return view('install', compact('requirements'));
    }



    public function get_reactions(Request $request)
    {
      if($request->users)
      {
        $reactions = Reaction::selectRaw("reactions.name, users.name as user_name, IFNULL(users.avatar, 'default.jpg') as user_avatar")
                          ->join('users USE INDEX(primary)', 'users.id', '=', 'reactions.user_id')
                          ->where(['reactions.item_id'    => $request->item_id, 
                                   'reactions.product_id' => $request->product_id,
                                   'reactions.item_type'  => $request->item_type]);

        //$reactions = $request->reaction ? $reactions->where('reactions.name', $request->reaction) : $reactions;

        $reactions = $reactions->get();

        return response()->json(['reactions' => $reactions->groupBy('name')->toArray()]);
      }
      else
      {
        return Reaction::selectRaw("COUNT(reactions.item_id) as `count`, reactions.name")
                          ->join('users USE INDEX(primary)', 'users.id', '=', 'reactions.user_id')
                          ->where(['reactions.item_id'    => $request->item_id, 
                                   'reactions.product_id' => $request->product_id,
                                   'reactions.item_type'  => $request->item_type])
                          ->groupBy('reactions.name')
                          ->get()->pluck('count', 'name')->toArray();
      }
    }


    public function get_cities(Request $request)
    {
      config('app.products_by_country_city') || abort(404);

      $country = $request->post('country') ?? abort(404);
      $cities = config("app.countries_cities.{$country}") ?? abort(404);

      return response()->json(compact('cities'));
    }



    public function proceed_payment_link(Request $request)
    {      
      $token = $request->token ?? abort(404);

      $short_link = route('home.proceed_payment_link', ['token' => $token]);

      $payment_link = Payment_Link::useIndex('short_link')
                      ->where('short_link', $short_link)
                      ->first() ?? abort(403, __('Payment link expired.'));

      list($user_email, $payment_identifer) = explode('|', decrypt(base64_decode($payment_link->token), false));

      if(!Auth::check())
      {
        return redirect()->route('login', ['redirect' => url()->current()])->with(['email' => $user_email]);
      }
      elseif(strtolower($request->user()->email) != strtolower($user_email))
      {
        return redirect('/')->with(['user_message' => __('You must be logged in with this email address : :email_address', ['email_address' => $user_email])]);
      }

      $user = \App\User::where('email', $user_email)->where('blocked', 0)->first() ?? abort(404);

      Auth::login($user, true);

      $payment_data = json_decode($payment_link->content, true);

      Session::put('short_link', $short_link);

      return redirect()->away($payment_data['payment_link']);
    }
}
