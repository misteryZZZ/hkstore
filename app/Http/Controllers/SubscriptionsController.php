<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use Illuminate\Support\Str;


class SubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $subscriptions = Subscription::selectRaw("*, IF(limit_downloads = 0, 'Unlimited', limit_downloads) AS limit_downloads,
                        IF(limit_downloads_per_day = 0, 'Unlimited', limit_downloads_per_day) AS limit_downloads_per_day,
                        IF(days = 0, 'Unlimited', days) AS days")
                      ->get();

      return View('back.pricing.index', ['title' => __('Pricing table'), 
                                         'subscriptions' => $subscriptions]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      return view('back.pricing.create', ['title'  => __('Create subscription')]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      $subscription = new Subscription;

      $request->validate([
          'name'            => 'bail|required|max:255|unique:subscriptions,name',
          'title'           => 'nullable|string|max:255',
          'price'           => 'nullable|numeric|gte:0',
          'days'            => 'nullable|numeric|gte:0',
          'limit_downloads' => 'nullable|numeric|gte:0',
          'limit_downloads_per_day' => 'nullable|numeric|gte:0',
          'limit_downloads_same_item' => 'nullable|numeric|gte:0',
          'description'     => 'required|string',
          'color'           => 'string|nullable',
          'products'        => ['nullable', 'regex:/^([\d,?]+)$/'],
          'position'        => 'nullable|numeric|gte:0'
      ]);

      $subscription->name             = $request->post('name');
      $subscription->title            = $request->post('title');
      $subscription->slug             = Str::slug($subscription->name, '-');
      $subscription->price            = $request->post('price') ?? 0;
      $subscription->days             = $request->post('days') ?? 0;
      $subscription->limit_downloads  = $request->post('limit_downloads') ?? 0;
      $subscription->limit_downloads_per_day = $request->post('limit_downloads_per_day') ?? 0;
      $subscription->limit_downloads_same_item = $request->post('limit_downloads_same_item') ?? null;
      $subscription->description      = $request->post('description');
      $subscription->color            = $request->post('color');
      $subscription->products         = $request->post('products');
      $subscription->position         = $request->post('position');

      $subscription->save();

      return redirect()->route('subscriptions');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      if(!$subscription = Subscription::find($id))
        abort(404);

      return view('back.pricing.edit', ['title'         => $subscription->name,
                                        'subscription'  => $subscription]);
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
        'name'            => "bail|required|max:255|unique:subscriptions,name,{$id}",
        'title'           => 'nullable|string|max:255',
        'price'           => 'nullable|numeric|gte:0',
        'days'            => 'nullable|numeric|gte:0',
        'limit_downloads' => 'nullable|numeric|gte:0',
        'limit_downloads_per_day' => 'nullable|numeric|gte:0',
        'limit_downloads_same_item' => 'nullable|numeric|gte:0',
        'description'     => 'required|string',
        'color'           => 'string|nullable',
        'products'        => ['nullable', 'regex:/^([\d,?]+)$/'],
        'position'        => 'nullable|numeric|gte:0'
      ]);

      $subscription = Subscription::find($id);

      $subscription->name             = $request->post('name');
      $subscription->title            = $request->post('title');
      $subscription->slug             = Str::slug($subscription->name, '-');
      $subscription->price            = $request->post('price') ?? 0;
      $subscription->days             = $request->post('days') ?? 0;
      $subscription->limit_downloads  = $request->post('limit_downloads') ?? 0;
      $subscription->limit_downloads_per_day = $request->post('limit_downloads_per_day') ?? 0;
      $subscription->limit_downloads_same_item = $request->post('limit_downloads_same_item');
      $subscription->description      = $request->post('description');
      $subscription->color            = $request->post('color');
      $subscription->updated_at       = date('Y-m-d H:i:s');
      $subscription->products         = $request->post('products');
      $subscription->position         = $request->post('position');

      $subscription->save();

      return redirect()->route('subscriptions');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      Subscription::destroy(explode(',', $ids));

      return redirect()->route('subscriptions');
    }
}
