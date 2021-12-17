<?php

namespace App\Libraries;

class Payhere 
{
    public $name = 'payhere';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["LKR", "USD", "GBP", "EUR", "AUD"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;


		public function __construct()
		{
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

			if(!config('payments.payhere.enabled'))
			{
				return response()->json(['user_message' => __(':payment_proc is not enabled', ['payment_proc' =>  'payhere'])]);
			}
            
			$this->currency_code = config('payments.currency_code');
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			prepare_currency($this);

      $this->details = [
      	'items' => [],
	      'total_amount' => 0,
	      'currency' => $this->currency_code,
	      'exchange_rate' => $this->exchange_rate,
	      'custom_amount' => null
	    ];
		}



		public function create_order(array $params, object $buyerInf)
		{			
			extract($params);

			$ch 			= curl_init();
			$api_url 	= 'https://sandbox.payhere.lk/pay/checkout';

			if(config('payments.payhere.mode') === 'live')
				$api_url = 'https://www.payhere.lk/pay/checkout';

			$total_amount = 0;

			$items = [];

			foreach($cart as $item)
			{
				$total_amount += $item->price;

				$this->details['items'][] = [
					'name' => $item->name, 
					'value' => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
					'license' => $item->license_id ?? null
				];
			}

			if(config("pay_what_you_want.enabled") && config('pay_what_you_want.for.'.($subscription_id ? 'subscriptions': 'products')) && $custom_amount)
      {
        $total_amount = $custom_amount;

        $this->details['custom_amount'] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
      }


			if(($coupon->status ?? null) && !$custom_amount)
      {
      	$total_amount -= $coupon->coupon->discount ?? 0;

      	$this->details['items']['discount'] = ['name' => __('Discount'), 'value' => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
      }


      $total_amount = $unit_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);

			$breakdown = [];

      $items[] = [
      	'item_name_1' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
      	'amount_1' 	  => $unit_amount,
      	'quantity_1' 	  => 1
      ];


      if($vat = config('payments.vat', 0))
      {
      	$tax = ($unit_amount * $vat) / 100;
      	$value = format_amount($tax, false, $this->decimals);

      	$items[] = [
	      	'item_name_2' => __('Tax'),
	      	'amount_2' 	  => $value,
	      	'quantity_2' 	  => 1
	      ];

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

	      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.payhere.fee', 0))
      {
      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

      	$items[] = [
	      	'item_name_3' => __('Fee'),
	      	'amount_3' 	  => $value,
	      	'quantity_3' 	  => 1
	      ];

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

	      $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      session(['transaction_details' => $this->details]);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $payload = [
      	'merchant_id' => config('payments.payhere.merchant_id'),
      	'return_url' => $this->return_url,
      	'cancel_url' => $this->cancel_url,
      	'notify_url' => route('home.checkout.webhook'),
      	'first_name' => $buyerInf->firstname,
      	'last_name' => $buyerInf->lastname,
      	'email' => $buyerInf->email,
				'address' => $buyerInf->address,
				'city' => $buyerInf->city,
				'country' => $buyerInf->country,
				'order_id' => generate_transaction_ref(),
				'items' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
				'currency' => $this->currency_code,
				'amount' => $total_amount
      ];

      /*$cache_data = ['payload' => $payload, 
						      	 'transaction_details' => $this->details,
						      	 'processor' => 'payhere',
						      	 'user_id' => \Auth::id()]; 			 

      $cache_data = array_merge($params, $cache_data);*/

      return array_merge($payload, ...$items);
		}



}