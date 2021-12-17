<?php

	namespace App\Libraries;

  use App\User;


	class Paystack 
	{
		public $name = 'paystack';
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ["USD", "GHS", "NGN"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $channels = ['card', 'bank', 'ussd', 'qr', 'mobile_money', 'bank_transfer'];
		public $error_msg;



		public function __construct()
		{
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

			exists_or_abort(config('payments.paystack.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Paystack']));
            
			$this->currency_code = config('payments.currency_code');
			$this->channels = array_filter(explode(',', config('payments.paystack.channels'))) ?? $this->channels;
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


    
		public function create_transaction(array $params, string $user_email)
		{

			/* DOC : https://paystack.com/docs/api/#transaction-initialize
			--------------------------------------------------------------
				curl https://api.paystack.co/transaction/initialize
				-H "Authorization: Bearer YOUR_SECRET_KEY"
				-H "Content-Type: application/json"
				-d '{ email: "customer@email.com", amount: "20000" }'
				-X POST*/

			extract($params);

			$total_amount = 0;

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


      $total_amount = (int)ceil(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));

			$line_items = [];

      $line_items[] = [
        'name' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
        'amount' => $total_amount
      ];


      if($vat = config('payments.vat', 0))
      {
      	$value = (int)ceil(($total_amount * $vat) / 100);

      	$line_items[] = [
      		'name' => __('Tax'),
          'description' => config('payments.vat').'%',
          'amount' => $value
	      ];

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	      $total_amount += $value ?? 0;
      }


      if($handling = config('payments.paystack.fee', 0))
      {
      	$value = (int)ceil(format_amount($handling * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

      	$line_items[] = [
      		'name' => __('Fee'),
          'description' => __('Handling fee'),
          'amount' => $value
	      ];

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	      $total_amount += $value;
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);


      session(['transaction_details' => $this->details]);


			$payload = [
				"email" 			 => $user_email,
        "amount" 			 => ceil($total_amount),
        "currency" 		 => $this->currency_code,
        "callback_url" => $this->return_url,
        "channels" 		 => $this->channels,
        "metadata" 		 => ["cancel_action" => $this->cancel_url, 'line_items' => $line_items]
      ];
      
      $ch 		 = curl_init();
			$api_url = 'https://api.paystack.co/transaction/initialize';

      $secret_key  = config('payments.paystack.secret_key');

      curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "Authorization: Bearer {$secret_key}"]);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
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

      if(!$result->status)
      {
      	$this->error_msg = ['user_message' => $result->message];

				return;
      }

			return $result;
		}



		public function verify_transaction($reference)
		{

			/* DOC : https://paystack.com/docs/api/#transaction-verify
			----------------------------------------------------------
			curl https://api.paystack.co/transaction/verify/:reference
			-H "Authorization: Bearer YOUR_SECRET_KEY"
			-X GET
			*/

			$ch 		 = curl_init();
			$api_url = "https://api.paystack.co/transaction/verify/{$reference}";

      $secret_key  = config('payments.paystack.secret_key');


      curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "Authorization: Bearer {$secret_key}"]);
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

	    if(!$result->status)
	    {
	      $this->error_msg = ['user_message' => $result->message];

				return;
	    }

			return $result;
		}



		public function fetch_transaction($transaction_id)
		{
			/* DOC : https://paystack.com/docs/api/#transaction-fetch
			---------------------------------------------------------
			curl https://api.paystack.co/transaction/:id
			-H "Authorization: Bearer YOUR_SECRET_KEY"
			-X GET
			*/

			$ch 		 = curl_init();
			$api_url = "https://api.paystack.co/transaction/{$transaction_id}";

      $secret_key  = config('payments.paystack.secret_key');

      curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "Authorization: Bearer {$secret_key}"]);
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

      if(!$result->status)
      {
        $this->error_msg = ['user_message' => $result->message];

				return;
      }

			return $result;
		}

	}