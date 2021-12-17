<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Libraries\{ Stripe, PayPalCheckout, Skrill, Razorpay, IyzicoLib, CoinGate, Midtrans, Paymentwall, Authorize_Net,
                    Paystack, Adyen, Instamojo, OfflinePayment, Payhere, Coinpayments, Spankpay, Omise, Sslcommerz, Flutterwave };
use App\Models\{ Transaction, Coupon, Product, Skrill_Transaction, Subscription, User_Subscription, Key, Affiliate_Earning };
use App\User;
use Illuminate\Support\Facades\{ DB, Session, Cache, Validator, Auth };
use Ramsey\Uuid;
use App\Events\NewMail;


class CheckoutController extends Controller
{
    public $transaction_details = [];
    public $transaction_params = [];
    public $payment_link = null;
    public $pending_transaction;



    public function payment(Request $request, $return_url = false, $user = null)
    {
      $processor = strtolower($request->processor);
      $minimum_custom_amount = config("payments.{$processor}.minimum");

      $validator =  Validator::make($request->all(), [
                        'custom_amount' => "nullable|numeric|digits_between:0,25|gte:{$minimum_custom_amount}",
                    ]);

      if($validator->fails())
      {
        return back()->with(['user_message' => implode(',', $validator->errors()->all())]);
      }

      if($request->subscription_id)
      {
        $subscription = Subscription::find($request->subscription_id) ?? abort(404);
        config(['checkout_cancel_url' => route('home.checkout', ['id' => $subscription->id, 'slug' => $subscription->slug, 'type' => 'subscription'])]);

        $cart = [(object)[
                  'id'        => $subscription->id,
                  'quantity'  => 1,
                  'name'      =>   $subscription->name,
                  'category'  => __('Subscription'),
                  'price'     => $subscription->price,
                ]];

        $request->merge(['products' => json_encode($cart)]);
      }
      else
      {
        config(['checkout_cancel_url' => route('home.checkout')]);

        if(!$cart = $this->validate_request($request))
        {
          return back();
        }

        $licenses_ids = array_column($cart, 'license_id');

        $request->merge(['products' => json_encode($cart)]);
      }
      
      $products_ids = array_column($cart, 'id');

      $coupon = $this->validate_coupon($request, $request->subscription_id ? 'subscription' : 'products', false, $user)->getData();

      if($this->cart_has_only_free_items($cart, $coupon, $processor, $request->custom_amount, $request->subscription_id ? 'subscriptions' : 'products'))
      {        
        $transaction_details = [];

        $items = array_reduce($cart, function($ac, $item)
        {
          $ac[] = [
                    'name' => $item->name,
                    'value' => $item->price
                  ];

          return $ac; 
        }, []);

        $items = array_merge($items, ['fee' => [
                                        'name' => __('Handling fee'),
                                        'value' => price(0, false, true, 2, null)
                                      ],
                                      'tax' => [
                                        'name' => __('Tax'),
                                        'value' => price(0, false, true, 2, null)
                                      ],
                                      'discount' => [
                                        'name' => __('Discount'),
                                        'value' => price($coupon->coupon->discount ?? 0, false, true, 2, null)
                                      ]]);

        $transaction_details['exchange_rate'] = config('payments.exchange_rate');
        $transaction_details['items']         = $items;
        $transaction_details['total_amount']  = format_amount(0, true);
        $transaction_details['custom_amount'] = $request->custom_amount;
        $transaction_details['currency']      = session('currency', config('payments.currency_code'));

        $this->transaction_details = $transaction_details;

        return $this->proceed_free_purchase($cart, $processor, $coupon, $transaction_details, null, $processor === 'stripe');
      }

      $params = [
        'processor'         => $processor,
        'cart'              => $cart,
        'subscription_id'   => null,
        'subscription_days' => null,
        'coupon'            => $coupon,
        'products_ids'      => null,
        'licenses_ids'      => null,
        'custom_amount'     => $request->custom_amount,
        'user_email'        => $user->email ?? (Auth::check() ? $request->user()->email : $request->input('buyer.email')),
        'user_id'           => $user->id ?? (Auth::check() ? Auth::id() : null),
        'fee'               => config("payments.{$processor}.fee"),
        'guest_token'       => ($user ?? Auth::check()) ? null : uuid6()
      ];

      if($request->subscription_id)
      {
        $params = array_merge($params, [
          'subscription_id'   => $subscription->id,
          'subscription_days' => $subscription->days,
          'products_ids'      => $subscription->id,
        ]);
      }
      else
      {
        $params = array_merge($params, [
          'products_ids'      => $products_ids,
          'licenses_ids'      => $licenses_ids,
        ]);
      }

      $this->transaction_params = $params;
      
      if($processor === 'flutterwave')
      {
        if(!$return_url && !$user)
        {
          $request->validate([
            'buyer.firstname' => 'string|required',
            'buyer.lastname'  => 'string|required',
            'buyer.phone'     => 'string|required',
            'buyer.email'     => 'email|required'
          ]);
        }

        $user = $user ?? (object)$request->input('buyer');

        $flutterwave = new Flutterwave();

        if($flutterwave->error_msg)
        {
          return $return_url ? $flutterwave->error_msg : back()->with($flutterwave->error_msg);
        }

        $order = $flutterwave->create_payment_link($params, $user);

        if($flutterwave->error_msg)
        {
          return $return_url ? $flutterwave->error_msg : back()->with($flutterwave->error_msg);
        }

        $params['transaction_details'] = $flutterwave->details;
        
        $params['payment_token']  = str_extract($order->payment_link, '/pay\/(?P<token>.+)\/?/i', 'token');


        Cache::put($flutterwave->details['tx_ref'], $params, now()->addDays(1));
        
        $this->transaction_details = $flutterwave->details;


        $this->payment_link = $order->payment_link;
      }
      elseif($processor === 'sslcommerz')
      {
        if(!$return_url && !$user)
        {
          $request->validate([
            'buyer.firstname' => 'string|required',
            'buyer.lastname'  => 'string|required',
            'buyer.phone'     => 'string|required',
            'buyer.zip_code'  => 'string|required',
            'buyer.city'      => 'string|required',
            'buyer.country'   => 'string|required',
            'buyer.address'   => 'string|required',
            'buyer.email'     => 'email|required'
          ]);
        }

        $buyer = $user ?? (object)$request->input('buyer');

        $sslcommerz = new Sslcommerz();

        if($sslcommerz->error_msg)
        {
          return $return_url ? $sslcommerz->error_msg : back()->with($sslcommerz->error_msg);
        }

        $order = $sslcommerz->create_order($params, $buyer);

        if($sslcommerz->error_msg)
        {
          return $return_url ? $sslcommerz->error_msg : back()->with($sslcommerz->error_msg);
        }

        $params['transaction_details'] = $sslcommerz->details;
        $params['sessionkey']          = $order->sessionkey;

        Cache::put($sslcommerz->details['tran_id'], $params, now()->addDays(1));
      
        $this->payment_link = $order->GatewayPageURL;
      }
      elseif($processor === 'paypal')
      {
        $paypal = new PayPalCheckout();

        if($paypal->error_msg)
        {
          return $return_url ? $paypal->error_msg : back()->with($paypal->error_msg);
        }

        $created_order = json_decode($paypal->create_order($params)) ?? abort(404);

        if($created_order->message ?? null)
        {
          $error_details = $created_order->details[0];

          $user_message = ['user_message' => "{$error_details->issue} - {$error_details->description}"];
          
          return $return_url ? $user_message : back()->with($user_message);
        }

        foreach($created_order->links as $link)
        {
          if($link->rel === 'approve')
          {
            $params['transaction_details'] = $paypal->details;

            if($return_url && $user)
            {
              Cache::put("payment_{$user->id}", $created_order->id, now()->addDays(1)); 
            }
            else
            {
              Session::put('payment', $created_order->id);
            }

            Cache::put($created_order->id, $params, now()->addDays(1));
                      
            $this->payment_link = $link->href;

            break;
          }
        }
      }
      elseif($processor === 'authorize_net')
      {
        $validator = Validator::make($request->all(), [
          'messages.resultCode' => 'required|string|in:Ok',
          'encryptedCardData.cardNumber' => 'required|string',
          'encryptedCardData.expDate' => 'required|string',
          'encryptedCardData.bin' => 'required|string',
          'customerInformation.firstName' => 'required|string',
          'customerInformation.lastName' => 'required|string',
          'opaqueData.dataDescriptor' => 'required|string',
          'opaqueData.dataValue' => 'required|string',
        ]);

        if($validator->fails())
        {
          return response()->json(['user_message' => implode(',', $validator->errors()->all())]);
        }

        $params['dataValue']       = $request->input('opaqueData.dataValue');
        $params['dataDescriptor']  = $request->input('opaqueData.dataDescriptor');
        $params['firstName']       = $request->input('customerInformation.firstName');
        $params['lastname']        = $request->input('customerInformation.lastname');

        $authorize_net = new Authorize_Net;

        if($authorize_net->error_msg)
        {
          return response()->json($authorize_net->error_msg);
        }

        $response = $authorize_net->create_order($params);

        if($authorize_net->error_msg)
        {
          return response()->json($authorize_net->error_msg);
        }


        if($response->getResponseCode() != 1)
        {
          $errors = $response->getErrors();

          foreach($errors as &$error)
          {
            $error = $error->getErrorText();
          }

          return response()->json(['user_message' => json_encode($errors)]);
        }

        $params['transaction_details'] = $authorize_net->details;
        $params['transaction_id']      = $response->getTransId();
        $params['reference_id']        = $response->getRefTransID();

        Session::put('payment', $params['transaction_id']);

        Cache::put($params['transaction_id'], $params, now()->addDays(1));

        return response()->json(['status' => true, 'redirect_url' => route('home.checkout.order_completed', ['trx_id' => $params['transaction_id']])]);
      }
      elseif($processor === 'omise')
      {
        $omise = new Omise;

        if($request->prepare)
        {
          $response = $omise->create_order($params, true);

          if($omise->error_msg)
          {
            return response()->json($omise->error_msg);
          }

          return response()->json($response); 
        }
        else
        {
          $source = $request->post('omiseSource') 
                    ? ['source' => $request->post('omiseSource')] 
                    : ($request->post('omiseToken') ? ['card' => $request->post('omiseToken')] : null);

          if(!$source)
          {
            return back()->with(['user_message' => __('Missing source or token.')]);
          }

          $response = $omise->create_order($params, false, $source);
          
          if($omise->error_msg)
          {
            return back()->with($omise->error_msg);
          }

          if($authorize_uri = ($response->authorize_uri ?? null))
          {
            $params['transaction_details'] = $omise->details;
            $params['transaction_id'] = $response->id;

            Session::put('payment', $response->id);

            Cache::put($response->id, $params, now()->addDays(1));

            return redirect()->away($authorize_uri);
          }
          
          return back()->with(['user_message' => __('Missing autorize url.')]);
        }
      }
      elseif($processor === 'paymentwall')
      {
        $paymentwall = new Paymentwall;

        if($paymentwall->error_msg)
        {
          return $return_url ? $paymentwall->error_msg : back()->with($paymentwall->error_msg);
        }

        $payment_url = $paymentwall->create_order($params);

        if($paymentwall->error_msg)
        {
          return $return_url ? $paymentwall->error_msg : back()->with($paymentwall->error_msg);
        }

        $order_id = get_url_param($payment_url, 'order_id') ?? abort(404);

        $params['transaction_details'] = $paymentwall->details;
        $params['order_id'] = $order_id;


        if($return_url && $user)
        {
          Cache::put("payment_{$user->id}", $order_id, now()->addDays(1));  
        }
        else
        {
          Session::put('payment', $order_id);
        }

        Cache::put($order_id, $params, now()->addDays(1));
      
        $this->payment_link = $payment_url;
      }
      elseif($processor === 'stripe')
      {
        $stripe = new Stripe;

        $response = $stripe->create_checkout_session($params);

        if($stripe->error_msg)
        {
          return response()->json(['user_message' => $stripe->error_msg]);
        }

        $params['transaction_details'] = $stripe->details;
        $params['cs_token'] = $response->id;
        $params['order_id'] = $response->payment_intent;

        Session::put('payment', $response->id);

        Cache::put($response->id, $params, now()->addDays(1));

        return response()->json(['id' => $response->id]);
      }
      elseif($processor === 'skrill')
      {
        if(!$user && !Auth::check())
        {
          return back()->with(['user_message' => __('Guest checkout not possible with Skrill')]);
        }
        
        $skrill = new Skrill;

        if($skrill->error_msg)
        {
          return $return_url ? $skrill->error_msg :back()->with($skrill->error_msg);
        }
        
        $response = $skrill->checkout_session_id($params, $user->id ?? Auth::id());

        if($skrill->error_msg)
        {
          return $return_url ? $skrill->error_msg : back()->with($skrill->error_msg);
        }

        $params['transaction_details'] = $skrill->details;

        if($return_url && $user)
        {
          Cache::put("payment_{$user->id}", $response['order_id'], now()->addDays(1)); 
        }
        else
        {
          Session::put('payment', $response['order_id']);
        }

        Cache::put($response['order_id'], $params, now()->addDays(1));

        $url = "https://pay.skrill.com/app/?sid={$response['sid']}";
     
        $this->payment_link = $url;
      }
      elseif($processor === 'razorpay')
      {
        $razorpay = new Razorpay();
          
        if($razorpay->error_msg)
        {
          return $return_url ? $razorpay->error_msg : back()->with($razorpay->error_msg);
        }

        $user = $user ?? (Auth::check() ? auth()->user() : null);
        $response = $razorpay->create_payment_link($params, $user);

        if($razorpay->error_msg)
        {
          return $return_url ? $razorpay->error_msg : back()->with($razorpay->error_msg);
        }

        if($response)
        {
          $params['transaction_details'] = $razorpay->details;
          $params['order_id'] = $response->id;
          $params['reference_id'] = $response->order_id;

          if($return_url && $user)
          {
            Cache::put("payment_{$user->id}", $response->id, now()->addDays(1)); 
          }
          else
          {
            Session::put('payment', $response->id);
          }

          Cache::put($response->id, $params, now()->addDays(1));

          $url = urldecode($response->short_url) ?? '/';
        
          $this->payment_link = $url;
        }
      }
      elseif($processor === 'iyzico')
      {
        if(!$return_url && !$user)
        {
          $request->validate([
            'buyer.firstname' => 'string|required',
            'buyer.lastname'  => 'string|required',
            'buyer.id_number' => 'string|required',
            'buyer.city'      => 'string|required',
            'buyer.country'   => 'string|required',
            'buyer.address'   => 'string|required',
            'buyer.email'     => 'email|required'
          ]);
        }

        $buyer = $user ?? (object)$request->input('buyer');

        $buyer->ip_address = $request->ip();

        $iyzico = new IyzicoLib();

        if($iyzico->error_msg)
        {
          return $return_url ? $iyzico->error_msg : back()->with($iyzico->error_msg);
        }

        $response = $iyzico->init_payment($params, $buyer) ?? abort(404);

        if($iyzico->error_msg)
        {
          return $return_url ? $iyzico->error_msg : back()->with($iyzico->error_msg);
        }

        if($response->getErrorCode())
        {
          $user_message = ['user_message' => $response->getErrorMessage()];
          
          return $return_url ? $user_message : back()->with($user_message);
        }

        $paymentPageUrl = $response->getPaymentPageUrl() ?? abort(404);

        $params['transaction_details'] = $iyzico->details;
        $params['transaction_id'] = $response->getToken();

        if($return_url && $user)
        {
          Cache::put("payment_{$user->id}", $response->getToken(), now()->addDays(1)); 
        }

        Cache::put('iyzico-'.$response->getToken(), $response->getToken());

        Cache::put($response->getToken(), $params, now()->addDays(1));
      
        $this->payment_link = $paymentPageUrl;
      }
      elseif($processor === 'payhere')
      {        
        $request->validate([
                'buyer.firstname' => 'string|required',
                'buyer.lastname'  => 'string|required',
                'buyer.city'      => 'string|required',
                'buyer.country'   => 'string|required',
                'buyer.address'   => 'string|required',
                'buyer.email'     => 'email|required'
              ]);

        $buyer = (object) $request->input('buyer');

        $buyer->ip_address = $request->ip();

        $payhere = new Payhere();

        if($payhere->error_msg)
        {
          return back()->with($payhere->error_msg);
        }

        $payload = $payhere->create_order($params, $buyer) ?? abort(404);

        if($payhere->error_msg)
        {
          return back()->with($payhere->error_msg);
        }

        $params['transaction_details'] = $payhere->details;
        $params['order_id'] = $payload['order_id'];

        Session::put('payment', $payload['order_id']);

        Cache::put($payload['order_id'], $params, now()->addDays(1));

        return response()->json(compact('payload'));
      }
      elseif($processor === 'spankpay')
      {
        $spankpay = new Spankpay();

        if($spankpay->error_msg)
        {
          return response()->json($spankpay->error_msg);
        }

        $payload = $spankpay->create_order($params) ?? abort(404);

        if($spankpay->error_msg)
        {
          return response()->json($spankpay->error_msg);
        }

        $params['transaction_details'] = $spankpay->details;

        Session::put('payment', $payload['order_id']);

        Cache::put($payload['order_id'], $params, now()->addDays(1));

        return response()->json($payload);
      }
      elseif($processor === 'coingate')
      {
        $coingate = new CoinGate();

        if($coingate->error_msg)
        {
          return $return_url ? $coingate->error_msg : back()->with($coingate->error_msg);
        }

        if($order = $coingate->create_order($params))
        {          
          if($order->status === 'new')
          {
            $params['transaction_details'] = $coingate->details;
            $params['order_id'] = $order->order_id;
            $params['transaction_id'] = $order->id;

            if($return_url && $user)
            {
              Cache::put("payment_{$user->id}", $order->id, now()->addDays(1)); 
            }
            else
            {
              Session::put('payment', $order->id);
            }

            Cache::put($order->id, $params, now()->addDays(1));

            $this->payment_link = $order->payment_url;
          }
          else
          {
            $user_message = ['user_message' => __('Order already created.')];

            return $return_url ? $user_message : back()->with($user_message);
          }
        }
      }
      elseif($processor === 'coinpayments')
      {
        if(!$user_email = $request->user()->email ?? null)
        {
          $request->validate(['email' => 'required|email']);

          $user_email = $request->post('email');
        }

        $coinpayments = new Coinpayments();

        if($coinpayments->error_msg)
        {
          return back()->with($coinpayments->error_msg);
        }

        $transaction = $coinpayments->create_transaction($params, $user_email);

        if($coinpayments->error_msg)
        {
          return back()->with($coinpayments->error_msg);
        }

        if(!($transaction->txn_id ?? null))
        {
          return back()->with(['user_message' => __('Missing transaction ID.')]);
        }

        Session::put(['payment_processor' => 'coinpayments', 'transaction_id' => $transaction->txn_id]);

        return redirect()->away($transaction->checkout_url);
      }
      elseif($processor === 'midtrans')
      {
        $midtrans = new Midtrans();
        $order    = $midtrans->create_order($params);

        if($midtrans->error_msg)
        {
          return $return_url ? $midtrans->error_msg : back()->with($midtrans->error_msg);
        }

        if($order->redirect_url ?? null)
        {
          $params['transaction_details'] = $midtrans->details;
          $params['transaction_id'] = $order->token;
          $params['return_url'] = route('home.checkout.order_completed');
          $params['cancel_url'] = config('checkout_cancel_url');

          if($return_url && $user)
          {
            Cache::put("payment_{$user->id}", $order->token, now()->addDays(1)); 
          }
          else
          {
            Session::put('payment', $order->token);
          }

          Cache::put($order->token, $params, now()->addDays(1));

          $this->payment_link = $order->redirect_url;
        }
      }
      elseif($processor === 'paystack')
      {
        $paystack = new Paystack();

        $user_email = $params['user_email'] ?? null;

        if(!$user_email)
        {
          $request->validate(['email' => 'email|required']);

          $user_email = $request->post('email');
        }

        $response = $paystack->create_transaction($params, $user_email);
        
        if($paystack->error_msg)
        {
          return $return_url ? $paystack->error_msg : back()->with($paystack->error_msg);
        }

        $params['transaction_details'] = $paystack->details;
        $params['reference_id']        = $response->data->reference;
        $params['order_id']            = $response->data->access_code;

        if($return_url && $user)
        {
          Cache::put("payment_{$user->id}", $params['reference_id'], now()->addDays(1)); 
        }
        else
        {
          Session::put('payment', $params['reference_id']);
        }

        Cache::put($params['reference_id'], $params, now()->addDays(1));

        $this->payment_link = $response->data->authorization_url;
      }
      elseif($processor === 'adyen')
      {
        $adyen = new Adyen($request->post('locale'));

        $response = $adyen->create_payment_link($params);

        if($adyen->error_msg)
        {
          return $return_url ? $adyen->error_msg : back()->with($adyen->error_msg);
        }

        $url = $response->url ?? abort(404);

        $params['transaction_details'] = $adyen->details;
        $params['reference_id'] = $response->reference;
        $params['payment_id'] = $response->id;

        if($return_url && $user)
        {
          Cache::put("payment_{$user->id}", $params['reference_id'], now()->addDays(1)); 
        }
        else
        {
          Session::put('payment', $params['reference_id']);
        }

        Cache::put($params['reference_id'], $params, now()->addDays(1));

        $url = "{$url}?reference={$params['reference_id']}";

        $this->payment_link = $url;
      }
      elseif($processor === 'instamojo')
      {
        $instamojo = new Instamojo();

        $response = $instamojo->create_request($params, $user);

        if($instamojo->error_msg)
        {
          return $return_url ? $instamojo->error_msg : back()->with($instamojo->error_msg);
        }
        
        if(!$url = $response->longurl)
        {
          return back()->with(['user_message' => __('Missing payment URL')]);
        }

        $params['transaction_details'] = $instamojo->details;
        $params['transaction_id']   = $response->id;

        if($return_url && $user)
        {
          Cache::put("payment_{$user->id}", $params['transaction_id'], now()->addDays(1)); 
        }
        else
        {
          Session::put('payment', $params['transaction_id']);
        }

        Cache::put($params['transaction_id'], $params, now()->addDays(1));

        $this->payment_link = $url;
      }
      elseif($processor === 'offline')
      {
        $offline_payment = (new OfflinePayment)->create_payment($params);

        $details = $offline_payment->getDetails();

        $transaction = new Transaction();

        $transaction->processor         = $processor;
        $transaction->products_ids      = implode(',', array_map('wrap_str', $products_ids));
        $transaction->items_count       = count($cart);
        $transaction->details           = json_encode($details, JSON_UNESCAPED_UNICODE);
        $transaction->user_id           = Auth::check() ? Auth::id() : null;
        $transaction->guest_token       = Auth::check() ? null : uuid6();
        $transaction->amount            = $details['total_amount'];
        $transaction->discount          = $details['discount'] ?? 0;
        $transaction->exchange_rate     = $details['exchange_rate'] ?? null;
        $transaction->confirmed         = 0;
        $transaction->status            = 'pending';
        $transaction->reference_id      = $details['reference_id'];
        $transaction->is_subscription   = ($subscription->id ?? null) ? 1 : 0;
        $transaction->referrer_id       = config('referrer_id');
    
        if(($details['currency'] != config('payments.currency_code')) && $transaction->exchange_rate != 1)
        {
          $transaction->amount = format_amount($details['total_amount'] / $transaction->exchange_rate, true);

          $transaction->discount =  $transaction->discount 
                                    ? format_amount($transaction->discount / $transaction->exchange_rate, true)
                                    : null;
        }

        if($coupon->status && $transaction->user_id)
        {
          DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$coupon->coupon->code]);
        }

        $transaction->save();

        return redirect()->route('home.checkout.offline', ['reference' => $details['reference_id']]);
      }

      if($this->payment_link)
      {
        return $return_url ? $this->payment_link : redirect()->away($this->payment_link);
      }

      return back()->with(['user_message' => __('An error occurred during the payment process.')]);
    }
    



    public function offline(Request $request)
    {
      $request->reference || abort(404);

      $transaction =  Transaction::useIndex('reference_id', 'status')
                      ->where('reference_id', $request->reference)
                      ->where('status', 'pending')
                      ->where('confirmed', 0)
                      ->first();

      if(!$transaction)
      {
        return redirect()->route('home');
      }

      if($transaction->created_at->addDays(1) < now())
      {
        if($transaction->coupon_id && Auth::check() && Auth::id() == $transaction->user_id)
        {
          DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, REPLACE(used_by, ?, '')) WHERE code = ?", ['', "'{$transaction->user_id}'", $transaction->coupon_id]);
        }

        $transaction->delete();

        abort(403, __('Payment expired.'));
      }

      $transaction_details = json_decode($transaction->details);

      $meta_data = (object)['name' => __('Confirm offline payment - :app_name', ['app_name' => config('app.name')]),
                            'title'       => __('Offline payment confirmation'),
                            'description' => null, 
                            'url'         => config('app.url'),
                            'fb_app_id'   => config('app.fb_app_id'),
                            'image'       => asset('storage/images/'.(config('app.cover') ?? 'cover.jpg'))];

      return view_('checkout.offline', compact('transaction_details', 'transaction', 'meta_data'));
    }



    public function offline_confirm(Request $request)
    {
      (($request->confirm || $request->cancel) && $request->reference) || abort(404);

      $transaction =  Transaction::useIndex('reference_id', 'status')
                      ->where('reference_id', $request->reference)
                      ->where('status', 'pending')
                      ->where('confirmed', 0)
                      ->first() ?? abort(404);

      if($request->confirm)
      {
        $transaction->setTable('transactions');

        $transaction->confirmed = 1;
        $transaction->referrer_id = config('referrer_id');

        $products_ids = explode(',', str_replace("'", '', $transaction->products_ids));
        $licenses     = null;
        $licensed_products_ids = Product::useIndex('primary', 'enable_license')->select('id')
                                 ->whereIn('id', $products_ids)->where('enable_license', 1)
                                 ->get()->pluck('id')->toArray();

        if($licensed_products_ids)
        {
          $licenses = [];

          foreach(array_intersect($products_ids, $licensed_products_ids) as $licensed_product_id)
          {
            $licenses[$licensed_product_id] = uuid6();
          }

          $transaction->licenses = json_encode($licenses);
        }
        
        $this->update_keys($products_ids, $transaction);

        if($transaction->is_subscription)
        {
          $subscription = Subscription::find(str_ireplace("'", '', $transaction->products_ids)) ?? abort(404);

          DB::transaction(function() use($transaction, $subscription)
          {
            $transaction->save();

            User_Subscription::insert([
              'user_id'         => Auth::id(),
              'subscription_id' => $subscription->id,
              'transaction_id'  => $transaction->id,
              'ends_at'         => is_numeric($subscription->days) && $subscription->days > 0
                                   ? date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " + {$subscription->days} days"))
                                   : null,
              'daily_downloads' => 0,
              'daily_downloads_date' => $subscription->limit_downloads_per_day ? date('Y-m-d') : null
            ]);
          });
        }
        else
        {
          $transaction->save();
        }

        $transaction_response = 'Done';
        $transaction_status   = 'success';    
        $guest_token          = $transaction->guest_token;
        $transaction_id       = $transaction->reference_id;
        $transaction_details  = json_decode($transaction->details, true);

        $awating_payment = __('You order :number is waiting for payment', ['number' => $this->pending_transaction['id'] ?? null]);

        return redirect()->route('home.checkout.success')
             ->with(compact('transaction_response', 'transaction_status', 'transaction_details', 'guest_token', 
                            'transaction_id', 'awating_payment'));
      }

      $transaction->setTable('transactions')->delete();

      return redirect()->route('home');
    }


    private function validate_request(Request $request)
    {      
      $cart   = json_decode($request->cart);
      $ids    = array_column($cart, 'id');

      $i = [];

      foreach($cart as $k => &$item)
      {
          $product = Product::useIndex('primary, active')
                      ->selectRaw('products.id, products.name, products.stock, products.slug, products.cover, categories.name as category_name, 
                        (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id AND key_s.user_id IS NULL) as `remaining_keys`,
                        (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id) as has_keys,
                        licenses.id as license_id, licenses.name as license_name, products.minimum_price,
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
                     ->join('licenses', 'licenses.id', '=', DB::raw($item->license_id ?? null))
                     ->leftJoin('product_price', function($join)
                          {
                            $join->on('product_price.license_id', '=', 'licenses.id')
                                 ->on('product_price.product_id', '=', 'products.id');
                          })
                     ->where(['products.active' => 1, 'products.id' => $item->id, 'products.for_subscriptions' => 0])
                     ->first() ?? abort(404);
                     $i[] = $product;

          if(out_of_stock($product))
          {
            unset($cart[$k]);
            continue;
          }

          unset($item->url, $item->thumbnail, $item->screenshots);

          if($product->minimum_price && ($item->custom_price ?? null))
          {
            $product->price = ($item->custom_price >= $product->minimum_price) ? $item->custom_price : $product->minimum_price;
          }
          else
          {
            $product->price = $product->promotional_price ? $product->promotional_price : $product->price;
          }

          $item->price      = $product->promotional_price ? $product->promotional_price : $product->price;
          $item->promo_price =  $product->promotional_price;
          $item->category   = $product->category_name;
          $item->name       = $product->name;
          $item->cover      = $product->cover;
          $item->slug       = $product->slug;
          $item->free       = $item->price == 0;
      }

      return $cart;
    }



    public function validate_coupon(Request $request, $for = null, $async = true, $user = null)
    {
      $discount = 0;

      if(!$coupon = $request->post('coupon'))
      {
        return response()->json(['status' => false, 'msg' => __('Invalid coupon')]);
      }

      $user_id = $user->id ?? Auth::id();


      if(!$coupon = Coupon::where('code', $coupon)->get()->first())
      {
        return response()->json(['status' => false, 'msg' => __('Coupon unavailable')]);
      }

      if($request->post('for', $for) === 'products')
      {
          if($coupon->for !== 'products')
          {
            return response()->json(['status' => false, 'msg' => __('Invalid coupon')]);
          }


          if(!$products = is_iterable($request->products) ? $request->products : json_decode($request->products, true))
          {
            return response()->json(['status' => false, 'msg' => __('Missing/Invalid parameter')]);
          }


          if($coupon->products_ids)
          {
            $products_ids = array_column($products, 'id');

            foreach($products_ids as $product_id)
            {
              if(!is_numeric($product_id))
              {
                return response()->json(['status' => false, 'msg' => __('Misformatted request')]);
              }
            }

            $_coupon_products_ids = array_filter(explode(',', str_ireplace("'", "", $coupon->products_ids)));

            if(!array_intersect($_coupon_products_ids, $products_ids))
            {
              return response()->json(['status' => false, 'msg' => __('This coupon is not for the selected product(s)')]);
            }

            foreach($products as $k => $product)
            {
              settype($product, 'object');

              $regular_license_only = $coupon->regular_license_only ? ($product->regular_license ?? null) : true;

              if(in_array($product->id, $_coupon_products_ids) && $regular_license_only)
              {
                if($coupon->is_percentage)
                {
                  $coupon_value = $product->price * $coupon->value / 100;
                }
                else
                {
                  $coupon_value = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
                }

                $discount += $coupon_value > $product->price ? $product->price : $coupon_value;
              }
            }
          }
          else 
          {
            if(!$coupon->regular_license_only)
            {
              $total_amount = array_sum(array_column($products, 'price'));

              if($coupon->is_percentage)
              {
                $discount = $total_amount * $coupon->value / 100;
              }
              else
              {
                $discount = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
              }

              $discount = $discount > $total_amount ? $total_amount : $discount;
            }
            else
            {
              $total_amount = 0;

              foreach($$products as $product)
              {
                if($product['regular_license'])
                {
                  $total_amount += $product['price'];
                }
              }

              if($coupon->is_percentage)
              {
                $discount = $total_amount * $coupon->value / 100;
              }
              else
              {
                $discount = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
              }

              $discount = $discount > $total_amount ? $total_amount : $discount;
            }
          }
      }
      elseif($request->post('for', $for) === 'subscription')
      {
        if($coupon->for !== 'subscriptions')
        {
          return response()->json(['status' => false, 'msg' => __('Invalid coupon')]);
        }

        if(!$subscription_id = $request->subscription_id)
        {
          return response()->json(['status' => false, 'msg' => __('Missing/Invalid parameter (subscription_id).')]);
        }

        if(!$subscription = Subscription::find($subscription_id))
        {
          return response()->json(['status' => false, 'msg' => __('Subscription currently not available')]);
        }

        if($coupon->subscriptions_ids)
        {
          if(!is_numeric($subscription_id))
          {
            return response()->json(['status' => false, 'msg' => __('Misformatted request')]);
          }

          if(!in_array($subscription_id, array_filter(explode(',', $coupon->subscriptions_ids))))
          {
            return response()->json(['status' => false, 'msg' => __('This coupon is not for the selected subscription.')]);
          }
        }

        if($coupon->is_percentage)
        {
          $discount = $subscription->price * $coupon->value / 100;
        }
        else
        {
          $discount = $async ? price($coupon->value, false, true, 2, null) : $coupon->value;
        }

        $discount = $discount > $subscription->price ? $subscription->price : $discount;
      }
      else
      {
        return;
      }
      
      if($coupon->expires_at < date('Y-m-d H:i:s'))
      {
        return response()->json(['status' => false, 'msg' => __('Coupon expired')]);
      }


      if($coupon->starts_at >= date('Y-m-d H:i:s'))
      {
        return response()->json(['status' => false, 'msg' => __('Coupon not available yet')]);
      }


      if($coupon->users_ids && $user_id)
      {
        if(!in_array($user_id, array_filter(explode(',', str_replace("'", '', $coupon->users_ids)))))
        {
          return response()->json(['status' => false, 'msg' => __('You are not allowed to use this coupon')]);
        }
      }

      if($coupon->used_by && $coupon->once && $user_id)
      {
        if(in_array($user_id, array_filter(explode(',', str_replace("'", '', $coupon->used_by)))))
        {
          return response()->json(['status' => false, 'msg' => __('Coupon already used')]);
        }
      }

      return response()->json([
                          'status' => true, 
                          'msg'    => 'Coupon applied',
                          'coupon' => [
                                       'value' => $coupon->value,
                                       'is_percentage' => $coupon->is_percentage ? true : false,
                                       'expires_at' => $coupon->expires_at,
                                       'code' => $coupon->code,
                                       'discount' => $discount ?? 0,
                                       'id' => $coupon->id
                                     ]
                        ]);
    }



    private function attach_coupon(&$cart, $coupon)
    {
      if($coupon->status ?? null)
      {
        foreach($cart as $k => &$item)
        {
          $item->discount  = 0;
          $item->coupon    = null;
          $item->coupon_id = null;
          $item->free      = $item->price == 0;
          $item->off       = false;

          if($k == $coupon->coupon->product->key)
          {
            $discount = $coupon->coupon->is_percentage
                        ? number_format(($item->price * $coupon->coupon->value / 100), 2, '.', '')
                        : number_format($coupon->coupon->value, 2, '.', '');

            $item->coupon    = (string)$coupon->coupon->code;
            $item->discount  = $discount < $item->price ? $discount : $item->price;
            $item->off       = $discount >= $item->price;
            $item->coupon_id = $coupon->coupon->id;
          }
        }
      }
      else
      {
        foreach($cart as $k => &$item)
        {
          $item->discount   = 0;
          $item->coupon     = null;
          $item->coupon_id  = null;
          $item->free       = $item->price == 0;
          $item->off        = false;
        }  
      }
    }



    private function cart_has_only_free_items($cart, $coupon, $processor, $custom_amount, $for = 'products')
    {
      $total_amount = array_reduce($cart, function($ac, $item)
                      {
                        $ac += (float)$item->price;

                        return $ac;
                      }, 0);

      $pay_what_you_want = config('pay_what_you_want.enabled') && config("pay_what_you_want.for.{$for}");

      if($pay_what_you_want && (config("payments.{$processor}.minimum") === '0') && $custom_amount === '0')
      {
        return true;
      }

      if(!$total_amount)
      {
        return true;
      }

      if($coupon->status)
      {
        return $coupon->coupon->discount >= $total_amount;
      }
    }



    private function proceed_free_purchase($cart, $processor, $coupon, $transaction_details, $subscription = null, $async = false)
    {
        $transaction = new Transaction;

        $transaction->amount            = 0;
        $transaction->user_id           = Auth::check() ? Auth::id() : null;
        $transaction->processor         = $processor;
        $transaction->guest_token       = Auth::check() ? null : uuid6();
        $transaction->updated_at        = date('Y-m-d H:i:s');
        $transaction->products_ids      = $subscription
                                          ? wrap_str($subscription->id)
                                          : implode(',', array_map('wrap_str', array_column($cart, 'id')));
        $transaction->licenses_ids      = $subscription
                                          ? null
                                          : implode(',', array_map('wrap_str', array_column($cart, 'license_id')));
        $transaction->is_subscription   = $subscription ? 1 : 0;
        $transaction->items_count       = $subscription ? 1 : count($cart);
        $transaction->details           = json_encode($transaction_details, JSON_UNESCAPED_UNICODE);
        $transaction->amount            = $transaction_details['total_amount'];
        $transaction->discount          = $coupon->coupon->discount ?? 0;
        $transaction->exchange_rate     = $transaction_details['exchange_rate'] ?? null;
        $transaction->reference_id      = generate_transaction_ref();
        $transaction->coupon_id         = $coupon->coupon->id ?? null;
        $transaction->referrer_id       = config('referrer_id');
        $transaction->status            = 'paid';


        if(!$subscription)
        {
          $products_ids = array_column($cart, 'id');
          $licenses = null;
          $licensed_products_ids = Product::useIndex('primary', 'enable_license')->select('id')
                                   ->whereIn('id', $products_ids)->where('enable_license', 1)
                                   ->get()->pluck('id')->toArray();

          if($licensed_products_ids)
          {
            $licenses = [];

            foreach(array_intersect($products_ids, $licensed_products_ids) as $licensed_product_id)
            {
              $licenses[$licensed_product_id] = uuid6();
            }

            $transaction->licenses = json_encode($licenses);
          }

          $this->update_keys($products_ids, $transaction);
        }
        
        if(isset($subscription))
        {
          DB::transaction(function() use($transaction, $subscription, $coupon)
          {
            $transaction->save();

            User_Subscription::insert([
              'user_id'         => Auth::id(),
              'subscription_id' => $subscription->id,
              'transaction_id'  => $transaction->id,
              'ends_at'         => is_numeric($subscription->days) && $subscription->days > 0
                                   ? date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " + {$subscription->days} days"))
                                   : null,
              'daily_downloads' => 0,
              'daily_downloads_date' => $subscription->limit_downloads_per_day ? date('Y-m-d') : null
            ]);
          });

          if($coupon->status)
          {
            DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$coupon->coupon->code]);
          }
        }
        else
        {
          DB::transaction(function() use($transaction, $coupon)
          {
            $transaction->save();

            if($coupon->status && $transaction->user_id)
            {
              DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$coupon->coupon->code]);
            }
          });
        }

        if($transaction->status === 'paid')
        {
          $this->update_affiliate_earnings($transaction);
        }

        $transaction_response = 'done';
        $transaction_status   = 'success';    
        $guest_token          = $transaction->guest_token;
        $transaction_id       = null;
        $processor            = $transaction->processor;

        $fash_data = compact('transaction_response', 'transaction_status', 'transaction_details', 'guest_token', 'transaction_id', 'processor');

        if($async)
        {
          foreach($fash_data as $key => $value)
          {
            Session::flash($key, $value);
          }

          return response()->json(['status' => true, 'redirect' => route('home.checkout.success')]);
        }

        return redirect()->route('home.checkout.success')
               ->with($fash_data);
    }



    public function success(Request $request)
    {
      if(session('transaction_status') !== 'success')
      {
        return redirect()->route('home');
      }

      $meta_data = (object)['name'        => config('app.name'),
                            'title'       => __('Transaction completed'),
                            'description' => null, 
                            'url'         => null,
                            'fb_app_id'   => config('app.fb_app_id'),
                            'image'       => null];

      return view_('checkout.success', compact('meta_data'));
    }




    // WEBHOOK
    public function webhook(Request $request)
    {
      set_time_limit(40);
      sleep(30);

      $user_agent       = $request->header('User-Agent');
      $response         = $request->all();
      $headers          = collect($request->header());
      $verified         = false;
      $processor        = null;
      $fields_to_update = [];

      $paypal_headers    = ['paypal-transmission-sig', 'paypal-cert-url', 'paypal-auth-algo', 'paypal-transmission-id', 'paypal-transmission-time'];
      $spankpay_headers  = ['x-spankpay-signature', 'x-spankpay-key'];
      $skrill_params     = ['transaction_id', 'mb_amount', 'md5sig', 'merchant_id', 'sha2sig', 'status', 'order_id', 'pay_to_email'];
      $instamojo_headers = ['x-webhook-signature', 'x-webhook-id'];
      $coingate_params   = ['id', 'order_id', 'status', 'pay_amount', 'pay_currency', 'price_amount', 'price_currency', 'receive_currency', 
      'receive_amount', 'created_at', 'underpaid_amount', 'overpaid_amount', 'is_refundable'];
      $midtrans_params   = ['transaction_time', 'transaction_status', 'transaction_id', 'status_message', 'status_code', 'signature_key', 'order_id', 'gross_amount'];
      $razorpay_headers  = ['x-razorpay-event-id', 'x-razorpay-signature'];
      $iyzico_params     = ['merchantId', 'token', 'status', 'iyziReferenceCode', 'iyziEventType', 'iyziEventTime'];
      $payhere_params    = ['merchant_id', 'order_id', 'payment_id', 'payhere_amount', 'payhere_currency', 'status_code'];
      $omise_params      = ['object', 'data', 'id', 'key', 'created_at'];


      if($headers->has('verif-hash') && $request->input('data.flw_ref')) // FLUTTERWAVE
      {
        $request->header('verif-hash') == config('payments.flutterwave.verif-hash') || abort(404);
        
        $processor    = 'flutterwave';
        $verified     = strtolower($request->input('event')) === 'charge.completed' && strtolower($request->input('data.status')) === 'successful';
        $where_params = ['processor' => $processor, 'transaction_id' => $request->input('data.id')];
        $fields_to_update = [
          'order_id' => $request->input('data.flw_ref')
        ];
      }
      elseif($request->store_id == config('payments.sslcommerz.store_id')) // SSLCOMMERZ
      {
        Sslcommerz::validate_ipn_hash($request->post()) || abort(404);

        $response = Sslcommerz::validate_ipn($request->val_id);

        $processor    = 'sslcommerz';
        $verified     =  ($response->status ?? null) === 'VALID' ? true : false;
        $where_params = ['transaction_id' => $request->input('tran_id')];
      }
      elseif($headers->has($paypal_headers)) // PAYPAL
      {
        $processor    = 'paypal';
        $verified     = (new PayPalCheckout())->verify_webhook_signature($request->all()) === 'SUCCESS' ? true : false;
        $where_params = ['transaction_id' => $request->input('resource.id')];
      }
      elseif($headers->has($spankpay_headers)) // SPANKPAY
      {
        $processor    = 'spankpay';
        $spankpay_sig = $request->header('X-SpankPay-Signature');

        parse_str($spankpay_sig, $sig_params);

        $webhook_response = file_get_contents("php://input");
        $timestamp    = $sig_params['t'] ?? null;
        $expected_sig = $sig_params['s'] ?? null;
        $actual_sig   = hash_hmac('sha256', "{$timestamp}.{$webhook_response}", config('payments.spankpay.secret_key'));       
        $webhook_response = json_decode($webhook_response);
        $status       = $webhook_response->status ?? null;
        $status       = $status === 'succeeded';

        if(hash_equals($expected_sig, $actual_sig) && $status)
        {
          $url          = \Spatie\Url\Url::fromString($webhook_response->invoice->callbackUrl);
          $order_id     = $url->getQueryParameter('order_id');
          $where_params = ['order_id' => $order_id];
          $verified     = true;
        }
      }
      elseif($request->has($skrill_params)) // SKRILL
      {
        $processor    = 'skrill';

        $merchant_id     = $request->merchant_id;
        $transaction_id  = $request->transaction_id;
        $secret_word     = strtoupper(md5(config('payments.skrill.mqiapi_secret_word')));
        $mb_amount       = $request->mb_amount;
        $mb_currency     = $request->mb_currency;
        $status          = $request->status;

        $digest = strtoupper(md5($merchant_id . $transaction_id . $secret_word . $mb_amount . $mb_currency . $status));

        if($digest == $request->md5sig && $status == 2)
        {
          $order_id     = $request->order_id;
          $verified     = true;
          $where_params = ['order_id' => $order_id];
          
          $fields_to_update = [
            'transaction_id' => $transaction_id
          ];
        }        
      }
      elseif(preg_match('/Adyen .+/i', $user_agent) && $request->has(['notificationItems'])) // ADYEN
      {
        $processor         = 'adyen';
        $notificationItems = $request->input('notificationItems');
        $notificationItems = array_shift($notificationItems) ?? [];

        if(($notificationItems['NotificationRequestItem']['success'] ?? null) === 'true')
        {
          $NotificationRequestItem = $notificationItems['NotificationRequestItem'];

          $pspReference         = $NotificationRequestItem['pspReference'];
          $originalReference    = $NotificationRequestItem['originalReference'] ?? '';
          $merchantAccountCode  = $NotificationRequestItem['merchantAccountCode'];
          $merchantReference    = $NotificationRequestItem['merchantReference'];
          $value                = $NotificationRequestItem['amount']['value'];
          $currency             = $NotificationRequestItem['amount']['currency'];
          $eventCode            = 'AUTHORISATION';
          $success              = $NotificationRequestItem['success'];

          $expected_hmacSignature = base64_encode(hash_hmac('sha256', "{$pspReference}:{$originalReference}:{$merchantAccountCode}:{$merchantReference}:{$value}:{$currency}:{$eventCode}:{$success}", pack("H*", config('payments.adyen.hmac_key')), true));
          
          $adyen_hmacSignature = $NotificationRequestItem['additionalData']['hmacSignature'] ?? null;

          if($expected_hmacSignature == $adyen_hmacSignature)
          {
            $reference     = $NotificationRequestItem['merchantReference'];
            $verified       = true;
            $where_params = ['reference_id' => $reference];
            
            $fields_to_update = [
              'transaction_id' => $NotificationRequestItem['additionalData']['paymentLinkId']
            ];
          }
        }
        else
        {
          return response('', 403);
        }
      }
      elseif(preg_match('/Instamojo-Webhook\/.+/i', $user_agent) && $headers->has($instamojo_headers)) // INSTAMOJO
      {
        $processor    = 'instamojo';

        $data = $request->post();
        unset($data['mac']);
        $ver = explode('.', phpversion());
        $major = (int)$ver[0];
        $minor = (int)$ver[1];

        ($major >= 5 and $minor >= 4) ? ksort($data, SORT_STRING | SORT_FLAG_CASE) : uksort($data, 'strcasecmp');

        $mac_calculated = hash_hmac("sha1", implode("|", $data), config('payments.instamojo.private_salt'));

        if($request->post('mac') == $mac_calculated && mb_strtolower($request->post('status')) === "credit")
        {
          $verified     = true;
          $where_params = ['transaction_id' => $request->post('payment_request_id')];

          $fields_to_update = [
            'order_id' => $request->post('payment_id')
          ];
        }
      }
      elseif($headers->has(['stripe-signature'])) // STRIPE
      {
        $processor    = 'stripe';

        parse_str(str_ireplace(',', '&', $request->header('stripe-signature')), $signature);
        
        $timestamp  = $signature['t'];
        $sig0       = $signature['v0'];
        $sig1       = $signature['v1'];

        $signed_payload = $timestamp.'.'.file_get_contents("php://input");

        $expected_sig =  hash_hmac('sha256', $signed_payload, cache('stripe_webhook.secret'));

        if(hash_equals($expected_sig, $sig0) || hash_equals($expected_sig, $sig1))
        {
          $verified = $request->input('data.object.captured') && $request->input('data.object.paid') && $request->input('data.object.status') === 'succeeded';
          
          $where_params = ['order_id' => $request->input('data.object.payment_intent')];
        }
      }
      elseif($request->has($coingate_params)) // COINGATE
      {
        $processor = 'coingate';

        $order_id = $request->post('order_id');
        $status   = strtolower($request->post('status')) === 'paid';

        if(Transaction::where('order_id', $order_id)->first() && $status)
        {
          $verified = true;
          $where_params = ['order_id' => $order_id];
        }
        else
        {
          return response('', 400);
        }
      }
      elseif($request->has($midtrans_params)) // MIDTRANS
      {
        $processor      = 'midtrans';

        $status         = $request->input('status_code') == '200';
        $transaction_id = $request->input('transaction_id');
        $order_id       = $request->input('order_id');
        //$fraud_status   = $request->input('fraud_status') ? (strtolower($request->input('fraud_status')) == 'accept') : true;
        $transaction_status = preg_match('/^settlement|capture$/i', $request->input('transaction_status'));

        $expected_sig = hash('sha512', $order_id . $request->input('status_code') . (string)$request->input('gross_amount') . config('payments.midtrans.server_key'));
        
        if(Transaction::where('order_id', $order_id)->first() && $status /*&& $fraud_status*/ && $transaction_status && ($expected_sig == $request->input('signature_key')))
        {
          $verified         = true;
          $where_params     = ['order_id' => $order_id];
          $fields_to_update = ['transaction_id' => $transaction_id];
        }
        else
        {
          return response('', 400);
        }
      }
      elseif(preg_match('/Razorpay-Webhook\/.+/i', $user_agent) && $headers->has($razorpay_headers)) // RAZORPAY
      {  
        $processor    = 'razorpay';

        $captured     = $request->input('payload.payment.entity.captured') == true;
        $expected_sig = hash_hmac('sha256', file_get_contents("php://input"), config('payments.razorpay.webhook_secret'));
        $razorpay_sig = $request->header('x-razorpay-signature');
        
        if($captured && ($expected_sig == $razorpay_sig))
        {
          $verified     = true;
          $where_params = ['order_id' => $request->input('payload.payment.entity.invoice_id')];
        }
        else
        {
          return response('', 400);
        }
      }
      elseif($request->has($iyzico_params)) // IYZICO
      {
        $processor = 'iyzico';

        $paid = strtolower($request->input('status')) === 'success';
        $expected_sig = base64_encode(hash('sha1', config('payments.iyzico.secret_id') . $request->input('iyziEventType') . $request->input('iyziReferenceCode')));
        $iyzico_sig   = $request->header('x-iyz-signature');

        if($paid)
        {
          $verified         = true;
          $where_params     = ['transaction_id' => $request->input('token')];
          $fields_to_update = ['reference_id' => $request->input('iyziReferenceCode')];
        }
      }
      elseif($headers->has(['x-paystack-signature'])) // PAYSTACK
      {
        $processor     = 'paystack';

        $paystack_sig  = $request->header('x-paystack-signature');
        $expected_sig  = hash_hmac('sha512', file_get_contents("php://input"), config('payments.paystack.secret_key'));
        $valid_event   = strtolower($request->input('event')) === 'charge.success';
        $paid          = strtolower($request->input('data.status')) === 'success';

        if($valid_event && $paid && ($paystack_sig == $expected_sig))
        {
          $verified = true;
          $where_params = ['reference_id' => $request->input('data.reference')];
          $fields_to_update = ['transaction_id' => $request->input('data.id')];
        }
        else
        {
          return response('', 400);
        }
      }
      elseif($request->has($payhere_params)) // PAYHERE
      {
        $processor      = 'payhere';

        $paid           = $request->input('status_code') == 2;
        $transaction_id = $request->input('payment_id');
        $order_id       = $request->input('order_id');
        $expected_sig   = hash_hmac('sha1', file_get_contents("php://input"), config('payments.payhere.merchant_secret'));
        $payhere_sig    = $request->header('http_x_signature');

        $verify_sig = $payhere_sig ? ($expected_sig == $payhere_sig) : true;

        if($paid && $verify_sig)
        {
          $verified = true;
          $where_params     = ['order_id' => $order_id];
          $fields_to_update = ['transaction_id' => $transaction_id];

          if(!Transaction::where(array_merge($where_params, ['processor' => $processor]))->first())
          {
            cache(["payhere-{$order_id}" => ['transaction_id' => $transaction_id]]);
          }
        }
      }
      elseif(preg_match('/Omise\/.+/i', $user_agent) && $request->has($omise_params)) // OMISE
      {
        $processor = 'omise';
        
        $paid           = $request->input('data.paid') == true;
        $authorized     = $request->input('data.authorized') == true;
        $capture        = $request->input('data.capture') == true;

        $transaction_id = $request->input('data.transaction');
        $order_id       = $request->input('data.id');
        $status_res     = Omise::check_order_status($order_id)->status ?? null;
        $status         = strtolower($status_res) == 'successful';

        if($paid && $authorized && $capture && $status)
        {
          $verified         = true;
          $where_params     = ['order_id' => $order_id];
          $fields_to_update = ['transaction_id' => $transaction_id];          
        }
      }
      elseif(preg_match('/Paymentwall/i', $user_agent)) // PAYMENTWALL
      {
        $processor = 'paymentwall';

        if($reference_id = Paymentwall::validate_webhook($request, null))
        {
          $verified         = true;
          $order_id         = $request->query('order_id');
          $where_params     = ['order_id' => $order_id];
          $fields_to_update = ['transaction_id' => $reference_id]; 

          if(!Transaction::where(array_merge($where_params, ['processor' => $processor]))->first())
          {
            cache(["paymentwall-{$order_id}" => ['transaction_id' => $reference_id]]);
          }
        }
      }
      elseif($headers->has(['x-anet-signature'])) // AUTHORIZE_NET
      {
        $processor = 'authorize_net';

        $authorize_net_sig = str_ireplace('sha512=', '', $request->header('x-anet-signature'));
        $expected_sig      = strtoupper(hash_hmac('sha512', file_get_contents("php://input"), config('payments.authorize_net.signature_key')));
  
        if($authorize_net_sig == $expected_sig && $request->input('payload.responseCode') == 1)
        {
          $verified = true;
          $where_params = ['transaction_id'   => $request->input('payload.id')];
          $fields_to_update = ['reference_id' => $request->input('payload.merchantReferenceId')];
        }
        else
        {
          return response('', 400);
        }
      }

      $transaction = isset($where_params) ? Transaction::where(array_merge($where_params, ['processor' => $processor]))->first() : null;

      if(!$transaction || !$verified)
      {
        return response('', 400);
      }


      if($verified && $transaction)
      {
        if($transaction->status != 'paid')
        {
            $updated = $transaction->update(array_merge(['status' => 'paid', 'processor' => $processor], $fields_to_update));
    
            $this->payment_confirmed_mail_notif($transaction);      
        }

        if($processor === 'adyen')
        {
          return response('accepted', 200);
        }
        elseif($processor === 'spankpay')
        {
          return response()->json(['received' => true], 200);  
        }
        else
        {
          http_response_code(200);
        }
      }

      if($transaction->status === 'paid')
      {
        $this->update_affiliate_earnings($transaction);
      }
      
      exit();
    }




    // ORDER COMPLETED
    public function order_completed(Request $request)
    {      
      $sess_payment = Cache::get('payment_'.Auth::id()) ?? Session::get('payment') ?? Cache::get("iyzico-{$request->token}") ?? Cache::get("paymentwall-{$request->order_id}") ?? Cache::get("payhere-{$request->order_id}") ?? $request->tran_id ?? $request->tx_ref ?? abort(404);

      $order_data = Cache::get((string)$sess_payment) ?? abort(404);

      extract($order_data);

      $transaction = new Transaction;

      $transaction->reference_id   = null;
      $transaction->transaction_id = null;
      $transaction->order_id       = null;
      $transaction->status         = 'pending';

      if($processor === 'flutterwave')
      {
        if(strtolower($request->query('status')) != 'successful')
        {
          if($request->query('status') == 'cancelled')
          {
            return redirect('/');
          }

          return redirect('/')->with(['user_message' => __("Your payment could not be completed. Please try again.")]);
        }

        $transaction->reference_id   = $request->query('tx_ref');
        $transaction->transaction_id = $request->query('transaction_id');
        $transaction->status         = 'pending';
      }
      elseif($processor === 'sslcommerz')
      {
        $request->val_id || abort(404);

        $response = Sslcommerz::check_order_status($sessionkey);

        $status = $response->status ?? null;

        if(preg_match('/^VALID|PENDING$/i', $status))
        {
          $transaction->reference_id   = $sessionkey;
          $transaction->transaction_id = $request->tran_id;
          $transaction->order_id       = $request->val_id;
          $transaction->status         = strtolower($status) === 'valid' ? 'paid' : 'pending';

          /*if($transaction->status == 'pending' && !config('payments.sslcommerz.use_ipn'))
          {
            pending_transactions([$transaction->transaction_id => array_merge($request->input(), $order_data)]);
          }*/
        }
        else
        { 
          $status_msg = [
            'VALIDATED '  => __('This transaction has already been validated.'),
            'FAILED'      => __('Failed to complete the transaction.')
          ];

          return redirect('/')->with(['user_message' => $status_msg[$request->status] ?? __('The transaction could not be completed.')]);
        }
      }
      elseif($processor === 'paypal')
      {
        if(!$token = $request->get('token'))
        {
          return redirect('/')->with(['user_message' => __('Invalid request.')]);
        }

        $capture = (new PayPalCheckout)->capture_order($token);

        $response = json_decode($capture);

        if(property_exists($response, 'name'))
        {
          Cache::forget($sess_payment);

          return redirect('/');
        }

        $transaction->order_id          = $response->order_id;
        $transaction->transaction_id    = $response->purchase_units[0]->payments->captures[0]->id;
        $transaction->reference_id      = $response->purchase_units[0]->reference_id;
        $transaction->status            = 'paid';
      }
      elseif($processor === 'spankpay')
      {
        $url = \Spatie\Url\Url::fromString($request->input('payment.receipt.url'));
        $order_id = $url->getQueryParameter('order_id');

        $transaction->order_id          = $order_id;
        $transaction->transaction_id    = $request->input('payment.invoiceId');
      }
      elseif($processor === 'skrill')
      {
        $transaction->order_id = $sess_payment;
      }
      elseif($processor === 'adyen')
      {
        $transaction->transaction_id  = $payment_id ?? null;
        $transaction->reference_id    = $reference_id ?? $sess_payment;
      }
      elseif($processor === 'instamojo')
      {
        $transaction->transaction_id = $transaction_id ?? $sess_payment;
      }
      elseif($processor === 'stripe')
      {
        $transaction->cs_token = $cs_token ?? $sess_payment;
        $transaction->order_id = $order_id;
      }
      elseif($processor === 'coingate')
      {
        $transaction->transaction_id = $transaction_id ?? $sess_payment;
        $transaction->order_id       = $order_id;
      }
      elseif($processor === 'midtrans')
      {
        $transaction->transaction_id = $sess_payment ?? $transaction_id;
        $transaction->order_id       = $request->order_id;
      }
      elseif($processor === 'razorpay')
      {
        $transaction->transaction_id = $request->query('razorpay_payment_id') ?? $sess_payment;
        $transaction->order_id       = $request->query('razorpay_invoice_id');
      }
      elseif($processor === 'iyzico')
      {
          $iyzico = new IyzicoLib();
		  $success = $iyzico->validate_payment($transaction_id);
		  
		  if(!$success)
		  {
			  return redirect('/')->with(['user_message' => __('Failed to complete the payment, please try again.')]);
		  }
		  
        $transaction->transaction_id = $transaction_id ?? $request->input('token') ?? $sess_payment;
        $transaction->status = 'paid';
		    $transaction->confirmed = 1;
      }
      elseif($processor === 'paystack')
      {
        $transaction->reference_id = $reference_id ?? $request->input('trxref') ?? $sess_payment;
      }
      elseif($processor === 'payhere')
      {
        $transaction->transaction_id = cache("payhere-{$order_id}.transaction_id");
        $transaction->order_id       = $request->input('order_id');
        $transaction->status         = $transaction->transaction_id ? 'paid' : 'pending';

        Cache::forget("payhere-{$order_id}");
      }
      elseif($processor === 'omise')
      {
        $transaction->order_id = $sess_payment;
      }
      elseif($processor === 'paymentwall')
      {
        $transaction->transaction_id = cache("paymentwall-{$sess_payment}.transaction_id");
        $transaction->order_id       = $sess_payment;
        $transaction->status         = $transaction->transaction_id ? 'paid' : 'pending';

        Cache::forget("paymentwall-{$sess_payment}");
      }
      elseif($processor === 'authorize_net')
      {
        $transaction->transaction_id = $request->query('trx_id') ?? $transaction_id;
        $transaction->reference_id   = $reference_id ?? null;
      }


      $transaction->reference_id      = $transaction->reference_id ?? generate_transaction_ref();
      $transaction->user_id           = $user_id ?? $user->id ?? Auth::id();
      $transaction->updated_at        = date('Y-m-d H:i:s');
      $transaction->processor         = $processor;
      $transaction->details           = json_encode($transaction_details, JSON_UNESCAPED_UNICODE);
      $transaction->amount            = $transaction_details['total_amount'];
      $transaction->discount          = $coupon->coupon->discount ?? 0;
      $transaction->exchange_rate     = $transaction_details['exchange_rate'] ?? 1;
      $transaction->guest_token       = $guest_token ?? null;
      $transaction->items_count       = count($cart);
      $transaction->custom_amount     = $transaction_details['custom_amount'] ?? null;
      $transaction->payment_url       = urldecode(Session::pull('short_link'));
      $transaction->referrer_id       = config('referrer_id');

      if(($transaction_details['currency'] != config('payments.currency_code')) && $transaction->exchange_rate != 1)
      {
        $transaction->amount = format_amount($transaction_details['total_amount'] / $transaction->exchange_rate, true);
        $transaction->custom_amount = format_amount($transaction_details['custom_amount'] / $transaction->exchange_rate, true);
      }

      if($coupon->status)
      {
        $transaction->coupon_id = $coupon->coupon->id;
      }

      if($subscription_id)
      {
        $subscription = Subscription::find($subscription_id) ?? abort(404);

        $transaction->is_subscription = 1;
        $transaction->products_ids    = wrap_str($subscription->id);
        $transaction->guest_token     = null;
        $transaction->items_count     = 1;

        DB::transaction(function() use($transaction, $subscription, $coupon)
        {
          $transaction->save();

          User_Subscription::insert([
            'user_id'         => Auth::id(),
            'subscription_id' => $subscription->id,
            'transaction_id'  => $transaction->id,
            'ends_at'         => is_numeric($subscription->days) && $subscription->days > 0
                                 ? date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " + {$subscription->days} days"))
                                 : null,
            'daily_downloads' => 0,
            'daily_downloads_date' => $subscription->limit_downloads_per_day ? date('Y-m-d') : null
          ]);
        });

        if($coupon->status)
        {
          DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$coupon->coupon->code]);
        }
      }
      else
      {
        $transaction->products_ids = implode(',', array_map('wrap_str', $products_ids));
        $transaction->licenses_ids = implode(',', array_map('wrap_str', $licenses_ids));

        $licenses = null;
        $licensed_products_ids = Product::useIndex('primary', 'enable_license')->select('id')
                                 ->whereIn('id', $products_ids)->where('enable_license', 1)
                                 ->get()->pluck('id')->toArray();

        if($licensed_products_ids)
        {
          $licenses = [];

          foreach(array_intersect($products_ids, $licensed_products_ids) as $licensed_product_id)
          {
            $licenses[$licensed_product_id] = uuid6();
          }

          $transaction->licenses = json_encode($licenses);
        }

        $this->update_keys($products_ids, $transaction);

        DB::transaction(function() use($transaction, $coupon)
        {
          $transaction->save();

          if($coupon->status && $transaction->user_id)
          {
            DB::update("UPDATE coupons SET used_by = IF(used_by IS NULL, ?, CONCAT_WS(',', used_by, ?)) WHERE code = ?", ["'{$transaction->user_id}'", "'{$transaction->user_id}'", (string)$coupon->coupon->code]);
          }
        });
      }
      
      if($transaction->status === 'paid' && preg_match('/^iyzico|payhere|paymentwall|paypal$/i', $processor))
      {
          $this->payment_confirmed_mail_notif($transaction);
      }

      if($transaction->status === 'paid')
      {
        $this->update_affiliate_earnings($transaction);
      }

      $transaction_response = 'done';
      $transaction_status   = 'success';    
      $transaction_id       = $transaction->transaction_id;

      if($request->post('async'))
      {
        Session::flash('transaction_response', $transaction_response);
        Session::flash('transaction_status', $transaction_status);
        Session::flash('transaction_details', $transaction_details);
        Session::flash('guest_token', $guest_token);
        Session::flash('transaction_id', $transaction_id);

        return response()->json([
          'status' => true,
          'redirect_url' => route('home.checkout.success')
        ]);
      }

      return redirect()->route('home.checkout.success')
            ->with(compact('guest_token', 'transaction_status', 'transaction_response', 'transaction_id'));
    }


    // NOTIFY BUYER ABOUT THE PAYMENT ONCE IT'S CONFIRMED
    public function payment_confirmed_mail_notif($transaction)
    {
      try
      {
        if(is_numeric($transaction->user_id))
        {
          $transaction_details = json_decode($transaction->details, true);

          $order        = array_merge($transaction->getAttributes(), $transaction_details);
          $buyer        = User::find($transaction->user_id);
          $products_ids = explode(',', str_replace("'", "", $transaction->products_ids));

          $order_id = $order['order_id'] ?? $order['transaction_id'] ?? $order['reference_id'] ?? null;

          $mail_props = [
            'data'    => $order,
            'action'  => 'send',
            'view'    => 'mail.order',
            'to'      => $buyer->email,
            'subject' => __('Order :number. is completed. Your payment has been confirmed', ['number' => $order_id])
          ];

          NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));

          if(!$transaction->is_subscription)
          {
            Product::whereIn('id', $products_ids)->where('stock', '>', 0)->decrement('stock', 1);
          }

          if(config('app.admin_notifications.sales'))
          {
            $message = [];

            foreach($transaction_details['items'] as $item)
            {
              $message[] = "- {$item['name']}";              
            }

            $message[] = "\n\n<strong>".__('You earned :amount', ['amount' => price($transaction_details['total_amount'], false)])."</strong>";

            $mail_props = [
              'data'    => ['text' => implode("\n", $message), 'subject' => __('A new sale has been completed by :buyer_name', ['buyer_name' => $buyer->username ?? explode('@', $buyer->email)[0]])],
              'action'  => 'send',
              'view'    => 'mail.message',
              'to'      => User::where('role', 'admin')->first()->email,
              'subject' => __('A new sale has been completed by :buyer_name', ['buyer_name' => $buyer->username ?? explode('@', $buyer->email)[0]])
            ];

            NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));
          }
        }
      }
      catch(\Exception $e){}
    }
    
    
    public function update_keys($products_ids, $transaction)
    {   
        $products_ids = array_filter($products_ids);
        
        foreach($products_ids as $product_id)
        {
            if($key = Key::useIndex('product_id')->where('product_id', $product_id)->where('user_id', null)->first())
            {
                DB::update("UPDATE key_s SET user_id = ?, purchased_at = ? WHERE id = ?", [$transaction->user_id ?? $transaction->guest_token, now()->format('Y-m-d H:i:s'), $key->id]);
            }
        }
    }


    public function update_affiliate_earnings($transaction)
    { 
        if(!$transaction->referrer_id)
        {
          return;
        }

        Affiliate_Earning::insert([
          'referrer_id'         => $transaction->referrer_id,
          'referee_id'          => $transaction->user_id ?? $transaction->guest_token,
          'transaction_id'      => $transaction->id,
          'commission_value'    => format_amount($transaction->amount * config('affiliate.commission', 0) / 100),
          'commission_percent'  => config('affiliate.commission', 0),
          'paid'                => 0,
        ]);
    }
}
