<?php

namespace App\Http\Controllers;

use App\Libraries\{ GoogleDrive, DropBox, YandexDisk, OneDrive, AmazonS3, Sitemap, Wasabi };
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\{ Category, Product, Product_Price, License };
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{ DB, Storage, Validator, File, Cache };
use ZipArchive;
use Intervention\Image\Facades\Image;


class ProductsController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {      
      if(!file_exists(public_path('products.xml')))
      {
        Sitemap::create('products');
      }

      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(id|name|price|newest|sales|category|active|trending|featured|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $request->keywords];

        $products = Product::useIndex('description')
                            ->selectRaw('products.id, products.name, products.newest, products.`type`, products.slug, products.trending, 
                                         products.featured, products.active, product_price.price, products.file_name,
                                         products.updated_at, count(transactions.id) as sales, products.preview,
                                         categories.name as category, products.is_dir')
                            ->leftJoin('transactions', 'products_ids', 'LIKE', DB::raw('concat("\'%", products.id, "%\'")'))
                            ->leftJoin('categories', 'categories.id', '=', 'products.category')
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
                            ->where('products.name', 'like', "%{$keywords}%")
                            ->orWhere('products.slug', 'like', "%{$keywords}%")
                            ->orWhere('products.overview', 'like', "%{$keywords}%")
                            ->orWhere('products.tags', 'like', "%{$keywords}%")
                            ->orWhere('products.short_description', 'like', "%{$keywords}%")
                            ->groupBy('products.id', 'products.name', 'products.slug', 'products.trending', 
                                        'products.featured', 'products.active', 'products.type', 'product_price.price', 
                                        'products.file_name','products.updated_at', 'categories.name', 
                                        'products.is_dir', 'products.preview', 'products.newest')
                            ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }
        
        $index = $request->orderby ?? 'primary';
        $index = preg_match('/^sales|price|id$/i', $index) ? 'primary' : $index;
        
        $products = Product::useIndex($index)
                            ->selectRaw('products.id, products.name, products.newest, products.`type`, products.slug, products.trending, 
                                         products.featured, products.active, product_price.price as price, products.file_name,
                                         products.updated_at, count(transactions.id) as sales,
                                         categories.name as category, products.is_dir, products.preview')
                            ->leftJoin('transactions', 'products_ids', 'LIKE', DB::raw('concat("\'%", products.id, "%\'")'))
                            ->leftJoin('categories', 'categories.id', '=', 'products.category')
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
                            ->groupBy('products.id', 'products.name', 'products.type', 'products.newest', 'products.slug', 'products.trending', 
                                        'products.featured', 'products.active', 'product_price.price', 
                                        'products.updated_at', 'categories.name', 'products.is_dir', 'products.file_name', 'products.preview')
                            ->orderBy($request->orderby ?? 'id', $request->order ?? 'DESC');
      }


      if(preg_match('/^(-|audio|graphic|video|ebook)$/i', $request->type))
      {
        $products = $products->where('type', $request->type);

        $base_uri['type'] = $request->type;
      }

      $products = $products->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.products.index', compact('products', 'items_order', 'base_uri'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      extract(Category::products());

      $product_id = Product::get_auto_increment();

      $cover       = file_uploaded('public/storage/covers', $product_id);
      $download    = file_uploaded('storage/app/downloads', $product_id);
      $screenshots = file_uploaded('public/storage/screenshots', $product_id);
      $preview     = file_uploaded('public/storage/previews', $product_id);

      return view("back.products.create", compact('category_children', 'category_parents', 'product_id', 
                                                   'cover', 'download', 'screenshots', 'preview'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $product_id = Product::get_auto_increment();

        $product = new Product;

        $request->validate([
            'name'              => 'bail|required|unique:products|max:255',
            'license'           => 'bail|required|array',
            'license.price'     => 'nullable|array',
            'license.price.*'     => 'nullable|numeric|min:0',
            'license.promo_price' => 'nullable|array',
            'overview'          => 'required',
            'short_description' => 'nullable|string',
            'category'          => 'required|numeric',
            'preview_url'       => 'url|nullable',
            'pages'             => 'nullable|numeric|gte:0',
            'words'             => 'nullable|numeric|gte:0',
            'minimum_price'     => 'nullable|numeric|gte:0',
            'language'          => 'nullable|string|max:255',
            'formats'           => 'nullable|string|max:255',
            'authors'           => 'nullable|string|max:255',
            'tags'              => 'string|nullable|max:255',
            'bpm'               => 'nullable|string|max:255',
            'label'             => 'nullable|string|max:255',
            'bit_rate'          => 'numeric|nullable',
            'type'              => 'string|nullable',
            'question'          => 'array|nullable',
            'question.*'        => 'nullable|string',
            'answer'            => 'array|nullable',
            'answer.*'          => 'nullable|string',
            '_name_'     => 'array|nullable',
            '_name_.*'   => 'nullable|string',
            '_value_'    => 'array|nullable',
            '_value_.*'  => 'nullable|string',
            'preview'           => 'nullable|file',
            'text'              => 'nullable|array',
            'text_type'         => 'nullable|array',
            'free'         => 'nullable|array',
            'free.*'       => 'nullable|string',
            'promotional_price_time'        => 'nullable|array',
            'promotional_price_time.*'      => 'nullable|string',
            'file_host'         => ['nullable', 'regex:/^(local|onedrive|dropbox|google|yandex|amazon_s3|wasabi)$/i'],
            'direct_download_link' => 'nullable|url',
            'direct_upload_link'   => 'nullable|url',
            'stock'             => 'nullable|numeric|gte:0',
            'preview_type'      => 'nullable|string|in:video,audio,pdf,zip,other',
            'enable_license'    => 'nullable|in:0,1',
            'for_subscriptions' => 'nullable|in:0,1'
        ]);

        if($subcategories = $request->input('subcategories'))
        {
          $product->subcategories = implode(',', array_map('wrap_str', explode(',', $subcategories)));
        }

        $product->name                = $request->input('name');
        $product->slug                = Str::slug($product->name, '-');
        $product->short_description   = $request->input('short_description');
        $product->overview            = $request->input('overview');
        $product->category            = $request->input('category');
        $product->notes               = $request->input('notes');
        $product->version             = $request->input('version');
        $product->preview_url         = $request->input('preview_url');
        $product->pages               = $request->input('pages');
        $product->words               = $request->input('words');
        $product->label               = $request->input('label');
        $product->language            = $request->input('language');
        $product->formats             = $request->input('formats');
        $product->authors             = $request->input('authors');
        $product->release_date        = $request->input('release_date');
        $product->last_update         = $request->input('last_update');
        $product->included_files      = $request->input('included_files');
        $product->tags                = $request->input('tags');
        $product->software            = $request->input('software');
        $product->db                  = $request->input('database');
        $product->compatible_browsers = $request->input('compatible_browsers');
        $product->compatible_os       = $request->input('compatible_os');
        $product->file_host           = $request->input('file_host');
        $product->high_resolution     = $request->input('high_resolution');
        $product->preview_type        = $request->input('preview_type');
        $product->for_subscriptions   = $request->input('for_subscriptions') ?? '0';
        $product->faq                 = json_encode(array_filter($this->faq($request)));
        $product->table_of_contents   = json_encode(array_filter($this->tableOfContents($request)));
        $product->additional_fields   = json_encode(array_filter($this->additional_fields($request)));
        $product->is_dir              = isFolderProcess() ? 1 : 0;
        $product->promotional_price_time = null;
        $product->hidden_content      = $request->input('hidden_content');
        $product->hidden_content      = mb_strlen(strip_tags($product->hidden_content)) ? $product->hidden_content : null;
        $product->stock               = $request->input('stock') ?? null;
        $product->enable_license      = $request->input('enable_license') ?? null;
        $product->type                = $request->input('type') ?? null;
        $product->bpm                 = $request->input('bpm') ?? null;
        $product->bit_rate            = $request->input('bit_rate') ?? null;
        $product->minimum_price       = $request->input('minimum_price') ?? null;
        
        if(config('app.products_by_country_city'))
        {
          $product->country_city = json_encode($request->country_city);
        }

        if(array_filter($request->input('promotional_price_time')))
        {
          if($request->input('promotional_price_time.from') > $request->input('promotional_price_time.to'))
          {
            return back()->withErrors(['promotional_price_time' => __('The given time for promotional price is incorrect.')])
                         ->withInput();
          }

          $product->promotional_price_time = json_encode($request->input('promotional_price_time'));
        }
        
        if($free = array_filter($request->input('free')))
        {
          $product->free = json_encode($free);
        }


        // Main file | folder
        if($main_file_upload_link = $request->post('main_file_upload_link'))
        {
          $request->validate(['main_file_upload_link' => 'url']);
          
          $response = get_remote_file_content($main_file_upload_link, $product_id);

          if(isset($response['error']))
          {
            return back_with_errors(['main_file_upload_link' => $response['error']]);
          }

          if(File::put(storage_path("app/downloads/{$response['file_name']}"), (string)$response['content']))
          {
            $product->file_name = $response['file_name'];
          }
        }
        elseif($main_file_download_link = $request->post('main_file_download_link'))
        {           
          $product->direct_download_link = urldecode($main_file_download_link);
        }
        elseif($request->post('file_name'))
        {
          $product->file_name = $request->post('file_name');
          $product->file_host = $request->post('file_host');
        }
        elseif($file_name = file_uploaded('storage/app/downloads', $product_id))
        {
          $extension = pathinfo($file_name, PATHINFO_EXTENSION);

          if(!in_array($extension, ['zip', 'rar', '7z']))
          {
            return back()->withInput()->withErrors(['main_file' => __('Only zip, rar and 7z file type are allowed for main file.')]);
          }

          $product->file_name = $file_name;
          $product->file_host = 'local';
        }


        // Cover
        if($cover = file_uploaded('public/storage/covers', $product_id))
        {
          $extension = pathinfo($cover, PATHINFO_EXTENSION);

          if(!in_array($extension, ['jpg', 'jpeg', 'svg', 'png', 'gif', 'webp']))
          {
            return back()->withInput()->withErrors(['main_file' => __('Only jpg, jpeg, svg, png and gif file type are allowed for cover.')]);
          }

          $product->cover = $cover;
        }



        // Screenshots
        if($screenshots_zip = file_uploaded('public/storage/screenshots', $product_id))
        {
          if(pathinfo($screenshots_zip, PATHINFO_EXTENSION) === 'zip')
          {
            $zip = new ZipArchive;

            if($zip->open(public_path("storage/screenshots/{$screenshots_zip}")))
            {
              $files = [];

              for($i = 0; $i < $zip->numFiles; $i++)
              {
                $filename  = $zip->getNameIndex($i);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if(in_array($extension, ['jpeg', 'jpg', 'png', 'svg']))
                {
                  $new_name = "{$product_id}-{$i}.{$extension}";

                  $zip->renameIndex($i, $new_name);

                  $files[] = $new_name;
                }
                else
                {
                  $zip->deleteIndex($product_id);
                }
              }

              $zip->close();

              $zip->open(public_path("storage/screenshots/{$screenshots_zip}"));

              if($zip->extractTo(public_path("storage/screenshots")))
              {
                $product->screenshots = implode(',', $files);
              }

              $zip->close();
            }
          }
        }


        // preview
        if($preview = $request->post('preview_upload_link'))
        {
            $request->validate(['preview_upload_link' => 'url']);

            $response = get_remote_file_content($preview, $product_id);

            if(isset($response['error']))
            {
              return back_with_errors(['preview_upload_link' => $response['error']]);
            }

            if(File::put(public_path("storage/previews/{$response['file_name']}"), (string)$response['content']))
            {
              $product->preview = $response['file_name'];
            }
        }
        elseif($preview = $request->post('preview_direct_link'))
        {
            $product->preview = urldecode($preview);
        }
        else
        {
          if($local_file = file_uploaded('public/storage/previews', $product_id))
          {
            $product->preview = $local_file;
          }
          elseif($request->input('type') == 'audio')
          {
            return back_with_errors(['preview' => __('A preview file is required for items of type Audio.')]); 
          }
        }



        $product->save();

        if(isset($screenshots_zip))
        {
          File::delete(public_path("storage/screenshots/{$screenshots_zip}"));
        }

        $this->save_product_prices($request, $product_id);

        $item_url = '<url><loc>' . item_url($product) . '</loc></url>';

        Sitemap::append($item_url, 'products');

        return redirect()->route('products');
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(int $id)
    {
      $product = Product::find($id) ?? abort(404);
      $product_prices = Product_Price::where('product_id', $id)->get()->toArray();
      $product_prices = array_combine(array_column($product_prices, 'license_id'), $product_prices);

      $product->free = json_decode($product->free);
      $product->promotional_price_time = json_decode($product->promotional_price_time);

      $product->table_of_contents      = json_decode($product->table_of_contents, true) ?? [];

      $product->faq = json_decode($product->faq, true) ?? [];

      $product->additional_fields   = json_decode($product->additional_fields, true) ?? [];
      $product->country_city = json_decode($product->country_city) ?? (object)[];

      $product->question = array_column($product->faq, 'question');
      $product->answer   = array_column($product->faq, 'answer');
      
      $product->text_type = array_column($product->table_of_contents, 'text_type');
      $product->text      = array_column($product->table_of_contents, 'text');

      $product->_name_  = array_column($product->additional_fields, '_name_');
      $product->_value_ = array_column($product->additional_fields, '_value_');

      extract(Category::products());

      if($product->subcategories)
      {
        $subcategories = explode(',', $product->subcategories);
        $subcategories = array_map('unwrap_str', $subcategories);

        $product->subcategories = implode(',', $subcategories);
      }

      if($screenshots_files = File::glob(public_path("storage/screenshots/{$product->id}-*.*")))
      {
        foreach($screenshots_files as &$screenshot_file)
        {
          $screenshot_file = basename($screenshot_file);
        }
      }

      $cover       = file_uploaded('public/storage/covers', $product->id);
      $download    = file_uploaded('storage/app/downloads', $product->id);
      $screenshots = file_uploaded('public/storage/screenshots', $product->id);
      $preview     = file_uploaded('public/storage/previews', $product->id);

      $product->setAttribute('preview_direct_link', preg_match('/^http/i', $product->preview) ? $product->preview : null); 

      return view("back.products.edit",  compact('download', 'cover', 'screenshots', 'preview', 'product', 'product_prices',
                                                    'category_children', 'category_parents', 'screenshots_files'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        /*if(!$request->isMethod('POST'))
        {
          $errors = new \Illuminate\Support\MessageBag([
            'URLs' => __('One of the urls your are using as a download or upload link for a file is not supported, please try to shorten it first with bitly.com or a similar service to bypass this issue.')
          ]);

          return back()->withErrors($errors)->withInput();
        }*/

        $product = Product::find($id);
        $copy    = clone $product;

        $request->validate([ 
            'name'                      => ['bail', 'required', 'max:255', Rule::unique('products')->ignore($id)],
            'license'                   => 'bail|required|array',
            'license.price'             => 'bail|required|array',
            'license.price.*'           => 'nullable|numeric|min:0',
            'license.promo_price'       => 'nullable|array',
            'overview'                  => 'required',
            'short_description'         => 'string|nullable',
            'category'                  => 'required|numeric',
            'promotional_price'         => 'bail|nullable|numeric|gt:0',
            'country_city'              => 'nullable|array',
            'country_city.*'            => 'nullable|string',
            'stock'                     => 'nullable|numeric|gte:0',
            'enable_license'            => 'nullable|in:0,1',
            'for_subscriptions'         => 'nullable|in:0,1',
            'preview_url'               => 'url|nullable',
            'pages'                     => 'nullable|numeric|gte:0',
            'words'                     => 'nullable|numeric|gte:0',
            'minimum_price'             => 'nullable|numeric|gte:0',
            'language'                  => 'nullable|string|max:255',
            'formats'                   => 'nullable|string|max:255',
            'authors'                   => 'nullable|string|max:255',
            'tags'                      => 'string|nullable|max:255',
            'label'                     => 'nullable|string|max:255',
            'text'                      => 'nullable|array',
            'text_type'                 => 'nullable|array',
            'preview'                   => 'nullable|file',
            'preview_type'              => 'nullable|string|in:video,audio,pdf,zip,other',
            'direct_download_link'      => 'nullable|url',
            'direct_upload_link'        => 'nullable|url',
            '_name_'                    => 'array|nullable',
            '_name_.*'                  => 'nullable|string',
            '_value_'                   => 'array|nullable',
            '_value_.*'                 => 'nullable|string',
            'free'                      => 'nullable|array',
            'free.*'                    => 'nullable|string',
            'promotional_price_time'    => 'nullable|array',
            'promotional_price_time.*'  => 'nullable|string',
        ]);

        if($subcategories = $request->input('subcategories'))
        {
          $subcategories = explode(',', $subcategories);
          $subcategories = array_map('unwrap_str', $subcategories);

          $product->subcategories = implode(',', array_map('wrap_str', $subcategories));
        }
        
        $product->name                = $request->input('name');
        $product->slug                = Str::slug($product->name, '-');
        $product->short_description   = $request->input('short_description');
        $product->overview            = $request->input('overview');
        $product->category            = $request->input('category');
        $product->notes               = $request->input('notes');
        $product->version             = $request->input('version');
        $product->preview_url         = $request->input('preview_url');
        $product->preview_type        = $request->input('preview_type');
        $product->pages               = $request->input('pages');
        $product->words               = $request->input('words');
        $product->language            = $request->input('language');
        $product->label               = $request->input('label');
        $product->formats             = $request->input('formats');
        $product->authors             = $request->input('authors');
        $product->release_date        = $request->input('release_date');
        $product->last_update         = $request->input('last_update');
        $product->included_files      = $request->input('included_files');
        $product->tags                = $request->input('tags');
        $product->software            = $request->input('software');
        $product->db                  = $request->input('database');
        $product->faq                 = json_encode(array_filter($this->faq($request)));
        $product->table_of_contents   = json_encode(array_filter($this->tableOfContents($request)));
        $product->additional_fields   = json_encode(array_filter($this->additional_fields($request)));
        $product->compatible_browsers = $request->input('compatible_browsers');
        $product->compatible_os       = $request->input('compatible_os');
        $product->high_resolution     = $request->input('high_resolution');
        $product->for_subscriptions   = $request->input('for_subscriptions') ?? '0';
        $product->free                = array_filter($request->free ?? []) ? json_encode($request->free) : null;
        $product->is_dir              = isFolderProcess() ? 1 : 0;
        $product->promotional_price_time = null;
        $product->hidden_content      = $request->input('hidden_content');
        $product->hidden_content      = mb_strlen(strip_tags($product->hidden_content)) ? $product->hidden_content : null;
        $product->stock               = $request->input('stock') ?? null;
        $product->enable_license      = $request->input('enable_license') ?? null;
        $product->type                = $request->input('type') ?? null;
        $product->bpm                 = $request->input('bpm') ?? null;
        $product->bit_rate            = $request->input('bit_rate') ?? null;
        $product->minimum_price       = $request->input('minimum_price') ?? null;

        if(config('app.products_by_country_city'))
        {
          $product->country_city = json_encode($request->country_city);
        }

        if(array_filter($request->input('promotional_price_time')))
        {
          if($request->input('promotional_price_time.from') > $request->input('promotional_price_time.to'))
          {
            return back()->withErrors(['promotional_price_time' => __('The given time for promotional price is incorrect.')])
                         ->withInput();
          }

          $product->promotional_price_time = json_encode($request->input('promotional_price_time'));
        }


        $product->direct_download_link = null;

        // Main file | folder
        if($main_file_upload_link = $request->post('main_file_upload_link'))
        {
          $request->validate(['main_file_upload_link' => 'url']);
          
          $response = get_remote_file_content($main_file_upload_link, $id);

          if(isset($response['error']))
          {
            return back_with_errors(['main_file_upload_link' => $response['error']]);
          }

          if(File::put(storage_path("app/downloads/{$response['file_name']}"), (string)$response['content']))
          {
            $product->file_name = $response['file_name'];
          }
        }
        elseif($main_file_download_link = $request->post('main_file_download_link'))
        {
          $product->direct_download_link = urldecode($main_file_download_link);
        }
        elseif($request->post('file_name'))
        {
          $product->file_name = $request->post('file_name');
          $product->file_host = $request->post('file_host');
        }
        elseif($file_name = file_uploaded('storage/app/downloads', $id))
        {
          $extension = pathinfo($file_name, PATHINFO_EXTENSION);

          if(!in_array($extension, ['zip', 'rar', '7z']))
          {
            return back()->withInput()->withErrors(['main_file' => __('Only zip, rar and 7z file type are allowed for main file.')]);
          }

          $product->file_name = $file_name;
          $product->file_host = 'local';
        }


        // Cover
        if($cover = file_uploaded('public/storage/covers', $id))
        {
          $extension = pathinfo($cover, PATHINFO_EXTENSION);

          if(!in_array($extension, ['jpg', 'jpeg', 'svg', 'png', 'gif', 'webp']))
          {
            return back()->withInput()->withErrors(['main_file' => __('Only jpg, jpeg, svg, png and gif file type are allowed for cover.')]);
          }

          $product->cover = $cover;
        }


        // Screenshots
        if($screenshots_zip = File::glob(public_path("storage/screenshots/{$id}.zip")))
        {
          $screenshots_zip = $screenshots_zip[0];

          $zip = new ZipArchive;

          if($zip->open($screenshots_zip))
          {
            $files = [];

            for($i = 0; $i < $zip->numFiles; $i++)
            {
              $filename  = $zip->getNameIndex($i);
              $extension = pathinfo($filename, PATHINFO_EXTENSION);

              if(!in_array($extension, ['jpg', 'jpeg', 'png', 'svg']))
                continue;

              $new_name = "{$id}-{$i}.{$extension}";

              $zip->renameIndex($i, $new_name);

              $files[] = $new_name;
            }

            $zip->close();

            $zip->open($screenshots_zip);

            if($zip->extractTo(public_path("storage/screenshots")))
            {
              $product->screenshots = implode(',', $files);
            }

            $zip->close();
          }
        }
        else
        {
          if($screenshots = File::glob(public_path("storage/screenshots/{$id}-*.*")))
          {
            $files = [];

            foreach($screenshots as $file)
            {
              $files[] = basename($file);
            }

            $product->screenshots = implode(',', $files);
          }
          else
          {
            $product->screenshots = null;
          }
        }



        // preview
        if($preview = $request->post('preview_upload_link'))
        {
            $request->validate(['preview_upload_link' => 'url']);

            $response = get_remote_file_content($preview, $id);

            if(isset($response['error']))
            {
              return back_with_errors(['preview_upload_link' => $response['error']]);
            }

            if(File::put(public_path("storage/previews/{$response['file_name']}"), (string)$response['content']))
            {
              $product->preview = $response['file_name'];
            }
        }
        elseif($preview = $request->post('preview_direct_link'))
        {
          $product->preview = urldecode($preview);
        }
        else
        {
          if($local_file = file_uploaded('public/storage/previews', $id))
          {
            $product->preview = $local_file;
          }
          elseif($request->input('type') == 'audio')
          {
            return back_with_errors(['preview' => __('A preview file is required for items of type Audio.')]); 
          }
        }


        $product->updated_at = date('Y-m-d H:i:s');

        $product->save();

        $sitemap_old_url = '<url><loc>' . item_url($copy) . '</loc></url>';
        $sitemap_new_url = '<url><loc>' . item_url($product) . '</loc></url>';

        Sitemap::update($sitemap_old_url, $sitemap_new_url, 'products');

        if($request->notify_buyers)
        {
          DB::insert("INSERT IGNORE INTO notifications (`id`, `for`, `users_ids`) 
                      SELECT ?, 0, CONCAT('|', GROUP_CONCAT(CONCAT(user_id, ':0') SEPARATOR '|'), '|')
                      FROM transactions USE INDEX (products_ids) WHERE products_ids LIKE CONCAT('\'%', ?, '%\'')", [$product->id, $product->id]);
        }

        if(isset($screenshots_zip))
        {
          $screenshots_zip ? File::delete($screenshots_zip) : null;
        }

        $this->remove_old_files($product, $copy);

        $this->save_product_prices($request, $id);

        return redirect()->route('products');
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      $ids = array_filter(explode(',', $ids));
      
      $sitemap_urls = Product::useIndex('primary')
                      ->selectRaw("CONCAT('<url>', CONCAT('<loc>', CONCAT(?, CONCAT(id, CONCAT('/', slug))), '</loc>'), '</url>') AS sitemap_url", [url('/item').'/'])
                      ->whereIn('id', $ids)->pluck('sitemap_url')->toArray();


      if(Product::destroy($ids))
      {
        foreach($ids as $id)
        {
          @$this->unlink_files($id);
        }

        Sitemap::delete($sitemap_urls, 'products');
      }

      return redirect()->route('products');
    }




    public function status(Request $request)
    {      
      $res = DB::update("UPDATE products USE INDEX(primary) SET {$request->status} = IF({$request->status} = 1, 0, 1) WHERE id = ?", 
                      [$request->id]);

      if($request->status === 'active')
      {
        $product =  Product::useIndex('primary')
                    ->selectRaw("active, CONCAT('<url>', CONCAT('<loc>', CONCAT(?, CONCAT(id, CONCAT('/', slug))), '</loc>'), '</url>') AS sitemap_url", [url('/item').'/'])
                    ->where('id', $request->id)->get()->first();

        if(!$product->active)
        {
          Sitemap::delete($product->sitemap_url, 'products');
        }
        else
        {
          Sitemap::append($product->sitemap_url, 'products');
        }
      }

      return response()->json(['success' => (bool)$res ?? false]);
    }



    private function faq(Request $request)
    {
      $faq = [];

      if($request->post('question') && $request->post('answer'))
      {
        foreach($request->post('question') ?? [] as $k => $question)
        {
          if(! isset($request->post('answer')[$k])) continue;

          $faq[] = (object)['question' => strip_tags($question), 'answer' => strip_tags($request->post('answer')[$k])];
        }
      }

      return $faq;
    }




    private function additional_fields(Request $request)
    {
      $faq = [];

      if($request->post('_name_') && $request->post('_value_'))
      {
        foreach($request->post('_name_') ?? [] as $k => $name)
        {
          if(! isset($request->post('_value_')[$k])) continue;

          $faq[] = (object)['_name_' => strip_tags($name), '_value_' => strip_tags($request->post('_value_')[$k])];
        }
      }

      return $faq;
    }


    // Unlink "main file", "screenshots" and "cover"
    private function unlink_files(int $product_id)
    {
      try
      {
        File::delete(glob(storage_path("app/downloads/{$product_id}.*")));

        $screenshots = glob(public_path("storage/screenshots/{$product_id}-*.*"));
        $cover       = glob(public_path("storage/covers/{$product_id}.*"));
        $preview     = glob(public_path("storage/previews/{$product_id}.*"));

        File::delete(array_merge($screenshots, $cover, $preview));
      }
      catch(Exception $e)
      {
        
      }
    }



    public function list_files(Request $request)
    {
      return call_user_func("\App\Libraries\\$request->files_host::list_files", $request);
    }



    public function list_folders(Request $request)
    {
      return call_user_func("\App\Libraries\\$request->files_host::list_folders", $request);
    }
    

    
    // Search products for newsletter selections and others
    public function api(Request $request, $ids = null)
    {
      $products = \App\Models\Product::selectRaw('products.id, products.name, products.slug, products.`type`, 
                  products.short_description, products.cover, product_price.price, licenses.name as license_name, 
                  licenses.id as license_id, products.preview,
                  CASE
                    WHEN product_price.promo_price IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, "%Y-%m-%d") BETWEEN SUBSTR(promotional_price_time, 10, 10) and SUBSTR(promotional_price_time, 28, 10)))
                      THEN product_price.promo_price
                    ELSE
                      NULL
                  END AS promotional_price')
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
                  ->where('active', '1');

      if($ids)
      {
        return $products->whereIn('products.id', $ids)->get();
      }

      if($request->keywords)
      {
        $products = $products->where('products.name', 'like', "%{$request->keywords}%");
      }

      if($request->where)
      {
        $products = $products->where($request->where);
      }

      $products = $products->limit($request->limit ?? 50)->get();

      return response()->json(['products' => $products]);
    }





    public function upload_file_async(Request $request)
    {
      if($file = $request->file('file'))
      {
        $id = $request->post('id') ?? Product::get_auto_increment();
        $destination = $request->post('destination') ?? abort(404);

        $file_name = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        if($destination === 'covers')
        {
          Image::configure(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
          
          $img = Image::make($file);

          if($crop = config("image.crop.{$request->type}"))
          {
            $img = $img->crop(...$crop);
          }

          if(config("image.watermark.{$request->type}") && config('app.watermark'))
          {
            $watermark = 'storage/images/'.config('app.watermark');

            $img = $img->insert($watermark, 'top-left')
                        ->insert($watermark, 'top')
                        ->insert($watermark, 'top-right')
                        ->insert($watermark, 'left')
                        ->insert($watermark, 'center')
                        ->insert($watermark, 'right')
                        ->insert($watermark, 'bottom-left')
                        ->insert($watermark, 'bottom')
                        ->insert($watermark, 'bottom-right');
          }
          
          $img->save("storage/covers/{$id}.{$extension}");

          $path = public_path("storage/covers/{$id}.{$extension}");
        }
        else
        {
          $path = $file->storeAs($destination, "{$id}.{$extension}", $destination === 'downloads' ? [] : ['disk' => 'public']);
        }

        return response()->json(['file_name' => $file_name, 'file_path' => $path, 'name' => "{$id}.{$extension}", 'status' => 'success']);
      }

      return response()->json(['status' => 'error']);
    }



    public function delete_file_async(Request $request)
    {
      $path = urldecode($request->path);

      if(is_file(base_path($path)))
      {
        File::delete(base_path($path));
      }      
    }



    private function remove_old_files($product, $copy)
    {
      if($product->file_name !== $copy->file_name)
      {
        $file_path = storage_path("app/downloads/{$copy->file_name}");

        if(is_file($file_path))
        {
          File::delete($file_path);
        }
      }


      $files = [];

      foreach(['cover', 'preview'] as $file)
      {
        if($product->$file !== $copy->$file)
        {
          $file_path = public_path("storage/{$file}s/{$copy->$file}");

          if(is_file($file_path))
          {
            $files[] = $file_path;
          }
        }
      }

      if($files)
      {
        File::delete($files);
      }
    }


    private function tableOfContents(Request $request)
    {
      $table_of_contents = [];

      if($request->post('text_type') && $request->post('text'))
      {
        foreach($request->post('text_type', []) as $k => $text_type)
        {
          if(! isset($request->post('text')[$k])) continue;

          $table_of_contents[] = (object)['text_type' => strip_tags($text_type), 'text' => strip_tags($request->post('text')[$k])];
        }
      }

      return $table_of_contents;
    }


    public function get_temp_url(Request $request)
    {
      $response = get_remote_file_content($request->url, $request->id);

      if(isset($response['error']))
      {
        return '';
      }

      if(File::put(public_path("storage/temp/{$response['file_name']}"), (string)$response['content']))
      {
        return asset_("storage/temp/{$response['file_name']}");
      }
    } 



    public function save_wave(Request $request)
    {
      $request->validate([
        'peaks'     => 'required',
        'id'        => 'required|numeric',
        'filename' => 'string|nullable'
      ]);

      if(json_decode($request->peaks))
      {
        $peaks_arr = cache('peaks') ?? [];

        $peaks_arr[$request->id] = $request->peaks;

        Cache::forever('peaks', $peaks_arr);

        $temp_file = public_path("storage/temp/{$request->filename}");

        try
        {
          if(File::exists($temp_file))
          {
            File::delete($temp_file);
          }
        }
        catch(\Exception $e)
        {

        }
      }
    } 


    private function save_product_prices(Request $request, $product_id)
    {
      $product_prices = [];

      foreach($request->input('license.price', []) as $license_id => $price)
      {
        $is_regular_license = License::find($license_id)->regular;
          
        if(is_null($price) && !$is_regular_license) continue;
        
        $price = $price ?? null;
        
        $promo_price = $request->input("license.promo_price.{$license_id}");
        
        if($price >= 0)
        {
          $product_prices[] = ['product_id' => $product_id, 'license_id' => $license_id, 'price' => $price, 'promo_price' => $promo_price];
        }
      }

      Product_Price::where('product_id', $product_id)->delete();

      if(array_filter(array_column($product_prices, 'price')))
      {
        return Product_Price::insert($product_prices);
      }
    }

}