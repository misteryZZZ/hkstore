<?php

namespace App\Http\Middleware;

use Closure;
use Validator;
use Illuminate\Http\Request;

class PaymentMethodValidation
{
    private $payment_processors = [
      "paypal", 
      "stripe", 
      "skrill", 
      "razorpay", 
      "iyzico", 
      "coingate", 
      "spankpay", 
      "omise", 
      "paymentwall",
      "midtrans", 
      "paystack", 
      "adyen", 
      "instamojo", 
      "n-a", 
      "offline", 
      "payhere", 
      "coinpayments", 
      "authorize_net", 
      "sslcommerz",
      "flutterwave",
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
      config(['payments' => array_merge(config('payments'), ['n-a' => ['enabled' => 1, 'fee' => 0]])]);

      $supported_processors = array_filter(config('payments'), function($v, $k)
                              {
                                return is_array($v) && in_array($k, $this->payment_processors) && key_exists('enabled', $v);
                              }, 1);

      $supported_processors = implode(',', array_keys($supported_processors ?? []));

      !Validator::make($request->all(), [
                    'processor' => "required|in:{$supported_processors},bail",
                    'cart' => ['required']
                  ])->fails() || abort(404);

      return $next($request);
    }
}
