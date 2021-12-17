<?php

namespace App\Libraries;


class Sslcommerz 
{
    public $name = 'sslcommerz';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["BDT", "EUR", "GBP", "AUD", "USD", "CAD"];
    public $currency_code;
    public $exchange_rate = 1;
    public $methods = ['internetbank', 'mastercard', 'visacard','othercard', 'amexcard', 'mobilebank'];
    public $decimals;
    public $details  = [];
    public $error_msg;


		public function __construct()
		{
			$this->status_url = route('home.checkout.webhook');
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

			if(!config('payments.sslcommerz.enabled'))
			{
				return response()->json(['user_message' => __(':payment_proc is not enabled', ['payment_proc' =>  'sslcommerz'])]);
			}

			if(config('payments.sslcommerz.methods', ''))
			{
				$this->methods = array_filter(explode(',', config('payments.sslcommerz.methods', [])));
			}
            
			$this->currency_code = config('payments.currency_code');
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			if(session('currency') && !in_array(session('currency'), $this->supported_currencies))
			{
				$this->error_msg = ['user_message' => __('Selected currency :currency_code not supported. Please use one of the following currencies : :currencies', ['currency_code' => session('currency'), 'currencies' => implode(',', ($this->supported_currencies))])];
						
				return;
			}

			//prepare_currency($this);

      $this->details = [
      	'items' => [],
	      'total_amount' => 0,
	      'currency' => session('currency', $this->currency_code),
	      'exchange_rate' => 1,
	      'custom_amount' => null
	    ];
		}



		public function create_order(array $params, $user)
		{
			/* DOC : https://developer.sslcommerz.com/doc/v4/#create-and-get-session
			-------------------------------------------------------------------------
				curl -X POST https://sandbox.sslcommerz.com/gwprocess/v4/api.php \
				-d 'store_id=codem613bb607ea725&store_passwd=codem613bb607ea725@ssl&total_amount=100&currency=EUR&tran_id=REF123&success_url=http://yoursite.com/success.php&fail_url=http://yoursite.com/fail.php&cancel_url=http://yoursite.com/cancel.php&cus_name=Customer Name&cus_email=cust@yahoo.com&cus_add1=Dhaka&cus_add2=Dhaka&cus_city=Dhaka&cus_state=Dhaka&cus_postcode=1000&cus_country=Bangladesh&cus_phone=01711111111&cus_fax=01711111111&shipping_method=NO&multi_card_name=mastercard,visacard,amexcard&product_name=Purchase%20From%20Valexa%20Store&product_category=digital&product_profile=non-physical-goods'
			*/

			extract($params);

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


      if($handling = config('payments.sslcommerz.fee', 0))
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

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $tran_id = generate_transaction_ref();

      $this->details['tran_id'] = $tran_id;

      $payload = [
      	"store_id" 				 => config('payments.sslcommerz.store_id'),
				"store_passwd" 		 => config('payments.sslcommerz.store_passwd'),
				"total_amount" 		 => $total_amount,
				"currency" 				 => $this->currency_code,
				"tran_id" 			   => $tran_id,
				"success_url" 		 => $this->return_url,
				"fail_url" 				 => $this->cancel_url,
				"cancel_url" 			 => $this->cancel_url,
				"ipn_url" 				 => $this->status_url,
				"cus_name" 				 => "{$user->lastname} {$user->firstname}",
				"cus_email" 			 => $user->email,
				"cus_add1" 				 => $user->address,
				"cus_city" 				 => $user->city,
				"cus_state" 			 => $user->state,
				"cus_postcode" 		 => $user->zip_code,
				"cus_country"			 => $user->country,
				"cus_phone" 			 => $user->phone,
				"shipping_method"  => "NO",
				"multi_card_name"  => implode(',', $this->methods),
				"product_name" 		 => __('Purchase from :app_name', ['app_name' => config('app.name')]),
				"product_category" => "digital",
				"product_profile"  => "non-physical-goods",
      ];
      
			$api_url = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';

			if(config('payments.sslcommerz.mode') == 'live')
			{
				$api_url = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
			}

			$ch = curl_init($api_url);

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);
			
			if(curl_errno($ch) || !json_decode($result))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				$this->error_msg = ['user_message' => $error_msg];

				return;
			}
			
			curl_close($ch);

			$result = json_decode($result);

			if($result->status == 'failed')
			{
				$this->error_msg = ['user_message' => $result->failedreason];

				return;
			}

			return $result;
		}



		public static function check_order_status($sessionKey)
		{
			/*
			DOC : https://developer.sslcommerz.com/doc/v4/#order-validation-api
			-------------------------------------------------------------------
			curl -X GET 'https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php?sessionkey=C3329C5E252DF44B323D9BAF47ACBCD9&store_id=testbox&store_passwd=qwerty&format=json'
			*/

			$store_id 		= config('payments.sslcommerz.store_id');
			$store_passwd = config('payments.sslcommerz.store_passwd');

			$url_params = "sessionkey={$sessionKey}&store_id={$store_id}&store_passwd={$store_passwd}&format=json";

			$request_url = "https://sandbox.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php?{$url_params}";

			if(config('payments.sslcommerz.mode') == 'live')
			{ 
				$request_url = "https://securepay.sslcommerz.com/validator/api/merchantTransIDvalidationAPI.php?{$url_params}";
			}

			$ch = curl_init($request_url);

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = json_decode(curl_exec($ch) ?? []);

			curl_close($ch);

			return $result;
		}


		public static function validate_ipn()
		{
			/*
			DOC : https://developer.sslcommerz.com/doc/v4/#order-validation-api
			-------------------------------------------------------------------
			curl -X GET 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=1709162025351ElIuHtUtFReBwE&store_id=testbox&store_passwd=qwerty&format=json'
			*/

			$store_id 		= config('payments.sslcommerz.store_id');
			$store_passwd = config('payments.sslcommerz.store_passwd');

			$url_params = "sessionkey={$sessionKey}&store_id={$store_id}&store_passwd={$store_passwd}&format=json";

			$request_url = "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?{$url_params}";

			if(config('payments.sslcommerz.mode') == 'live')
			{
				$request_url = "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?{$url_params}";
			}

			$ch = curl_init($request_url);

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = json_decode(curl_exec($ch) ?? []);

			curl_close($ch);

			return $result;	
		}



		public static function validate_ipn_hash($post_body)
		{
			$verify_keys = explode(',', ($post_body['verify_key']));
			$new_params = [];

			foreach($verify_keys as $key)
			{
				$new_params[$key] = $post_body[$key];
			}

			$new_params['store_passwd'] = md5(config('payments.sslcommerz.store_passwd'));

			ksort($new_params);

			$hash_string_md5 = md5(urldecode(http_build_query($new_params)));

			return $hash_string_md5 == $post_body['verify_sign'];
		}

}