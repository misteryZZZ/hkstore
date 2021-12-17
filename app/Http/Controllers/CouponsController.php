<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{ Coupon, User, Product, Subscription };
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class CouponsController extends Controller
{ 
    private $codes = null;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(code|used_by|amount|start_at|expire_at|updated_at)$/i', 'required_with:order'],
                      'order'   => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $coupons = Coupon::useIndex('primary')
                          ->selectRaw("coupons.id, code, value, is_percentage, starts_at, expires_at, 
                                       coupons.updated_at, products.name AS item_name, products.slug AS item_slug,
                                       IF(used_by IS NOT NULL, 
                                       CHAR_LENGTH(used_by) - CHAR_LENGTH( REPLACE ( used_by, ',', '') ) + 1, 0) as used_by")
                          ->leftJoin('products', 'products.id', 'coupons.product_id')
                          ->where('code', 'like', "%{$keywords}%")
                          ->orWhere('products.name', 'like', "%{$keywords}%")
                          ->orWhere('products.slug', 'like', "%{$keywords}%")
                          ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $coupons = Coupon::useIndex($request->orderby ?? 'primary')
                          ->selectRaw("coupons.id, code, value, is_percentage, starts_at, expires_at, updated_at,
                                      IF(used_by IS NOT NULL, 
                                      CHAR_LENGTH(used_by) - CHAR_LENGTH( REPLACE ( used_by, ',', '') ) + 1, 0) as used_by")
                          ->orderBy($request->orderby ?? 'id', $request->order ?? 'DESC');
      }

      $coupons = $coupons->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.coupons.index', [ 'coupons' => $coupons,
                                          'items_order' => $items_order,
                                          'base_uri' => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $products = Product::useIndex('active')->select('name', 'id')->where('active', 1)->get();
      $users    = User::useIndex('blocked')->select('email as name', 'id as value')->where('blocked', 0)->get()->toArray();
      $subscriptions = Subscription::select('name', 'id')->where('price', '>', 0)->get();

      return view('back.coupons.create', compact('products', 'users', 'subscriptions'));
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {      
      $request->validate([
        'code'          => 'required|max:255|unique:coupons,code|bail',
        'value'         => 'required|gt:0',
        'is_percentage' => 'required|in:0,1',
        'for'           => 'nullable|in:products,subscriptions',
        'once'          => 'nullable|in:0,1',
        'users_ids'     => ['nullable', 'regex:/^([\d,?]+)$/'],
        'starts_at'     => 'required',
        'expires_at'    => 'required',
        'regular_license_only' => 'nullable|in:0,1'
      ]);

      $coupon = new Coupon;

      $coupon->code           = $request->code;
      $coupon->value          = $request->value;
      $coupon->is_percentage  = $request->is_percentage;
      $coupon->for            = $request->for ?? 'products';
      $coupon->once           = $request->once;
      $coupon->regular_license_only = $request->regular_license_only ?? '0';
      $coupon->products_ids   = $request->products_ids;
      $coupon->subscriptions_ids = $request->subscriptions_ids;
      $coupon->users_ids      = $request->users_ids
                                ? implode(',', array_map('wrap_str', explode(',', $request->users_ids)))
                                : '';
      $coupon->starts_at      = date_format(new \DateTime($request->starts_at), 'Y-m-d H:i:s');
      $coupon->expires_at     = date_format(new \DateTime($request->expires_at), 'Y-m-d H:i:s');

      $coupon->save();

      return redirect()->route('coupons');
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $coupon = Coupon::find($id) ?? abort(404);

      $coupon->starts_at = str_ireplace(' ', 'T', $coupon->starts_at);
      $coupon->expires_at = str_ireplace(' ', 'T', $coupon->expires_at);

      $products = Product::useIndex('active')->select('name', 'id')->where('active', 1)->get();
      $users    = User::useIndex('blocked')->select('email', 'id')->where('blocked', 0)->get();
      $subscriptions = Subscription::select('name', 'id')->where('price', '>', 0)->get();

      return View('back.coupons.edit', compact('coupon', 'products', 'users', 'subscriptions'));
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $request->validate([
        'code'          => "required|max:255|unique:coupons,code,{$id}|bail",
        'value'         => 'required|gt:0',
        'is_percentage' => 'required|in:0,1',
        'for'           => 'nullable|in:products,subscriptions',
        'once'          => 'nullable|in:0,1',
        'users_ids'     => ['nullable', 'regex:/^([\d,?]+)$/'],
        'starts_at'     => 'required',
        'expires_at'    => 'required',
        'regular_license_only' => 'nullable|in:0,1'
      ]);

      $coupon = Coupon::find($id);

      $coupon->code           = $request->code;
      $coupon->value          = $request->value;
      $coupon->is_percentage  = $request->is_percentage;
      $coupon->for            = $request->for ?? 'products';
      $coupon->once           = $request->once;
      $coupon->regular_license_only = $request->regular_license_only ?? '0';
      $coupon->products_ids   = $request->products_ids;
      $coupon->subscriptions_ids = $request->subscriptions_ids;
      $coupon->users_ids      = $request->users_ids
                                ? implode(',', array_map('wrap_str', explode(',', $request->users_ids)))
                                : '';
      $coupon->starts_at      = date_format(new \DateTime($request->starts_at), 'Y-m-d H:i:s');
      $coupon->expires_at     = date_format(new \DateTime($request->expires_at), 'Y-m-d H:i:s');

      $coupon->save();

      return redirect()->route('coupons');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy($ids)
    {
      Coupon::destroy(explode(',', $ids));

      return redirect()->route('coupons');
    }



    /**
    * Generate coupon
    * @param int $limit (max 40)
    * @return String
    */
    public function generate($limit = 12)
    {
      if(is_null($this->codes))
      {
        $this->codes = Coupon::select('code')->pluck('code')->toArray();
      }

      $limit = ($limit > 40) ? 12 : $limit;

      $arr = array_merge(range('A', 'Z'), range(0, 15));
    
      shuffle($arr);
      
      $coupon = implode('', array_slice($arr, 0, $limit));

      while(in_array($coupon, $this->codes))
      {
        $this->generate($limit);
      }

      return response()->json(['code' => $coupon]);
    }
}
