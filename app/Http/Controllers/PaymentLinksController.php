<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{ Payment_Link, Product_Price, License, User, Product, Subscription, Coupon };
use Illuminate\Support\Facades\{ Validator, Session, Cache };
use Illuminate\Validation\Rule;
use App\Http\Controllers\CheckoutController;
use App\Events\NewMail;


class PaymentLinksController extends Controller
{
  public $payment_services = [];

  public function __construct()
  {
      $this->payment_services = collect(config('payments', []))->where('enabled', 'on')->filter(function($service)
      {
        return in_array($service['name'], ['paypal', 'razorpay', 'adyen', 'coingate', 'instamojo', 'midtrans', 'paystack', 'iyzico', 'paymentwall', 'flutterwave', 'sslcommerz']);
      })->pluck('fee', 'name')->toArray();
  }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(name|user|updated_at|short_link|processor|status)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      !$validator->fails() || abort(404);

      $base_uri = [];

      $payment_links =  Payment_link::useIndex($request->orderby ?? 'primary')
                        ->selectRaw('payment_links.id, payment_links.name, payment_links.short_link, payment_links.updated_at,
                           payment_links.content, payment_links.amount, payment_links.reference, IFNULL(transactions.status, "-") as status, users.email as user')
                        ->join('users', 'users.id', '=', 'payment_links.user_id')
                        ->leftJoin('transactions USE INDEX(processor, user_id)', function($join)
                        {
                          $join->on('transactions.user_id', '=', 'payment_links.user_id')
                               ->on('transactions.processor', '=', 'payment_links.processor')
                               ->on(function($join)
                               {
                                  $join->on('transactions.reference_id', '=', 'payment_links.reference')
                                        ->orOn('transactions.order_id', '=', 'payment_links.reference')
                                        ->orOn('transactions.cs_token', '=', 'payment_links.reference')
                                        ->orOn('transactions.transaction_id', '=', 'payment_links.reference')
                                        ->orOn('transactions.payment_url', '=', 'payment_links.short_link');
                               });
                        })
                        ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');

      $payment_links = $payment_links->paginate(15);

      foreach($payment_links as &$payment_link)
      {
        $user = $payment_link->user;

        $payment_link->forceFill(json_decode($payment_link->content, true));

        $payment_link->user = $user;
        $payment_link->amount = $payment_link['transaction_details']['total_amount'] ?? null;
        $payment_link->discount = $payment_link['transaction_details']['items']['discount']['value'] ?? 0;
        $payment_link->exchange_rate = $payment_link['transaction_details']['exchange_rate'] ?? null;
        $payment_link->currency = $payment_link['transaction_details']['currency'] ?? null;

        $payment_link->discount = format_amount($payment_link->discount, false, config("payments.currencies.{$payment_link->currency}.decimals"));
      }

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.payment_links.index', compact('payment_links', 'items_order', 'base_uri'));
    }




    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    { 
        $subscriptions    = Subscription::all();
        $payment_services = $this->payment_services;

        return view('back.payment_links.create', compact('subscriptions', 'payment_services'));
    }




    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {    
        if(!$user = User::useIndex('primary')->where('id', $request->user_id)->first())
        {
          return $request->from_user_subscriptions ? ['user_message' => __('User not found')] : back()->with(['user_message' => __('User not found')]);
        }

        $request->validate([
          'name' => 'nullable|string',
          'user_id' => 'required|numeric',
          'is_subscription' => 'nullable|string|in:0,1',
          'products' => 'nullable|array',
          'subscription' => 'nullable|array',
          'discount' => 'nullable|numeric|gte:0',
          'custom_amount' => 'nullable|numeric|gt:0',
          'currency' => 'nullable|string',
          'exchange_rate' => 'nullable|numeric|gt:0',
          'payment_service' => 'required|string|in:'.implode(',', (array_keys($this->payment_services)))
        ]);


        $request_params = [
          'processor' => $request->payment_service,
          'coupon' => null,
          'subscription_id' => $request->input('subscription.id'),
          'custom_amount' => $request->custom_amount,
          'cart' => [],
        ];

        if($is_subscription = $request->post('is_subscription'))
        {
          if(!$subscription = Subscription::find($request->input('subscription.id')))
          {
            if($request->from_user_subscriptions)
            {
              return ['error' => __('Invalid subscription')];
            }

            return back()->with(['message' => __('Invalid subscription')]);
          }

          $request_params['cart'] = [(object)[
            'id'        => $subscription->id,
            'quantity'  => 1,
            'name'      =>   $subscription->name,
            'category'  => __('Subscription'),
            'price'     => $subscription->price,
          ]];
        }
        else
        {
          if(!$products = array_filter($request->products))
          {
            return back()->with(['message' => __('There must be at least one product selected.')]);
          }

          $ids          = array_filter($products['id']);
          $licenses     = array_filter($products['license']);
          $prices       = array_filter($products['price'], function($price)
                          {
                            return ctype_digit(trim($price));
                          });

          $licenses_ids = $licenses;
          $ids_licenses = array_combine($ids, $licenses);
          $ids_prices   = array_combine($ids, $prices);

          $items    = Product::useIndex('primary')->select('id', 'name', 'slug')->whereIn('id', $ids)->get();
          $licenses = License::useIndex('primary')->select('id', 'name')->whereIn('id', $licenses)->get()
                      ->pluck('name', 'id')->toArray();

          foreach($items as &$item)
          {
            $item->license_id = $ids_licenses[$item->id];
            $item->license_name = $licenses[$item->license_id];
            $item->price = $ids_prices[$item->id];
          }

          $request_params['cart'] = array_reduce($items->toArray(), function($ac, $item)
          {
            $ac[] = (object)[
                      'id' => $item['id'],
                      'name' => $item['name'],
                      'slug' => $item['slug'],
                      'license_id' => $item['license_id'],
                      'license_name' => $item['license_name'],
                      'price' => $item['price']
                    ];

            return $ac; 
          }, []);
        }


        if($currency = $request->currency)
        {
          session(['currency' => $currency]);
        }



        if($request->exchange_rate)
        {
          session(['admin_exchange_rate' => $request->exchange_rate]);
        }


        if($request->coupon_code)
        {
          $request_params['coupon'] = $request->coupon_code;
        }
        elseif($request->discount > 0)
        {
          $request_params['coupon'] = (new \App\Http\Controllers\CouponsController)->generate()->getData()->code;

          Coupon::insert([
            'value' => $request->discount,
            'is_percentage' => false,
            'code' => $request_params['coupon'],
            'users_ids' => wrap_str($user->id),
            'starts_at' => now()->subMinutes(30)->format('Y-m-d H:i:s'),
            'expires_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'for' => $request->post('is_subscription') ? 'subscriptions' : 'products'
          ]);
        }

        $customPaymentRequest = new Request();

        $customPaymentRequest->setMethod('POST');

        $request_params['cart'] = json_encode($request_params['cart']);

        $customPaymentRequest->request->add($request_params);

        $checkout_controller = new CheckoutController;

        $payment_link = $checkout_controller->payment($customPaymentRequest, true, $user);

        if(is_array($payment_link))
        {
          if($request->from_user_subscriptions)
          {
            return ['error' => $payment_link['user_message']];
          }

          return back()->with($payment_link);
        }
        
        $uuid = uuid6();

        $token = base64_encode(encrypt($user->email.'|'.$uuid, false));

        $short_link = route('home.proceed_payment_link', ['token' => uuid6()]);

        $payment_link_obj = new Payment_Link;

        $content = array_merge(['transaction_details' => $checkout_controller->transaction_details], $checkout_controller->transaction_params, 
                               compact('payment_link'), cache((string)cache("payment_{$user->id}"), []));

        $payment_link_obj->name          = $request->name ?? "{$user->id} - {$request->payment_service}";
        $payment_link_obj->user_id       = $request->user_id;
        $payment_link_obj->content       = json_encode($content);
        $payment_link_obj->token         = $token;
        $payment_link_obj->reference     = cache("payment_{$user->id}");
        $payment_link_obj->short_link    = route('home.proceed_payment_link', ['token' => uuid6()]);
        $payment_link_obj->processor     = $request->payment_service;

        $payment_link_obj->save();

        Session::remove('currency');
        Session::remove('admin_exchange_rate');

        if($request->from_user_subscriptions)
        {
          return ['success' => ['id' => $payment_link_obj->id, 'short_link' => $short_link]];
        }

        return redirect()->route('payment_links')->with(['user_message' => __('Done')]);
    }


    /**
     * Send payment link.
     */
    public function send(Request $request)
    {
        $payment_link = Payment_link::find($request->id) ?? abort(404);

        $payment_link_content = json_decode($payment_link->content, true);
        $transaction_details = $payment_link_content['transaction_details'];
        $buyer = User::find($payment_link->user_id);
        $order = array_merge($payment_link->getAttributes(), $transaction_details, ['username' => $buyer->name ?? explode('@', $buyer->email)[0]]);
        $products_ids = $payment_link_content['products_ids'];

        $order_id = $order['order_id'] ?? $order['transaction_id'] ?? $order['reference_id'] ?? null;
        $order['text'] = $request->text;

        $mail_props = [
          'data'    => $order,
          'action'  => $request->action ?? 'send',
          'view'    => 'mail.payment_link',
          'to'      => $buyer->email,
          'subject' => $request->subject ?? __('New payment request from :app_name', ['app_name' => config('app.name')])
        ];

        NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));
        
        $response_message = __(":id sent to :email successfully.", ['id' => $request->id, 'email' => $buyer->email]);

        if($request->from_user_subscriptions)
        {
          return ['success' => $response_message];
        }
        return response()->json(['response' => $response_message]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      Payment_Link::destroy(explode(',', $ids));

      return redirect()->route('payment_links');
    }


    public function item_licenses(Request $request)
    {
      $request->validate(['item_id' => 'required|numeric']);

      $licenses_prices = Product_Price::useIndex('product_id')
                         ->select('product_price.price', 'licenses.name as license_name', 'licenses.id as license_id')
                         ->where('product_id', $request->post('item_id'))
                         ->join('licenses', 'licenses.id', '=', 'product_price.license_id')
                         ->get();

      return response()->json(compact('licenses_prices'));
    }
}
