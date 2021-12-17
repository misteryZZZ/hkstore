<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Cache, Session, Auth };


	class Midtrans 
	{
		public $name = 'midtrans';
		public $supported_currencies = ["IDR"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals = 0;
		public $details = [];
		public  $error_msg;
		public $enabled_payments = [
                    "credit_card", 
                	"mandiri_clickpay", 
                	"cimb_clicks", 
                	"bca_klikbca", 
                	"bca_klikpay", 
                	"bri_epay", 
                	"echannel", 
                	"mandiri_ecash", 
                	"permata_va", 
                	"bca_va", 
                	"bni_va", 
                	"other_va", 
                	"gopay", 
                	"indomaret", 
                	"alfamart", 
                	"danamon_online", 
                	"akulaku"
            ];



        public function __construct()
		{
			exists_or_abort(config('payments.midtrans.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Midtrans']));

			$this->currency_code = config('payments.currency_code');
			$this->decimals 	   = config("payments.currencies.{$this->currency_code}.decimals");
			$this->enabled_payments = array_filter(explode(',', config('payments.midtrans.methods'))) ?? $this->enabled_payments;

			prepare_currency($this);

      $this->details = [
      	'items' => [],
	      'total_amount' => 0,
	      'currency' => $this->currency_code,
	      'exchange_rate' => $this->exchange_rate,
	      'custom_amount' => null
	    ];
		}




		public function create_order(array $params)
		{			
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


      $total_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);

      $item_details = [];

      $item_details[] = [
			      		'id' 			 => 'PURCHASE',
                'name' 		 => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
                'price' 	 => ceil($total_amount),
                'quantity' => 1
              ];


      if($vat = config('payments.vat', 0))
      {
      	$tax = ($total_amount * $vat) / 100;

      	$value = format_amount($tax ?? 0, false, $this->decimals);

      	$item_details[] = [
      		'id' 			 => 'TAX',
          'name' 		 => __('VAT :percent%', ['percent' => $vat]),
          'price' 	 => ceil($value),
          'quantity' => 1
      	];

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

	      $total_amount += $value;
      }


      if($handling = config('payments.midtrans.fee', 0))
      {
      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

      	$item_details[] = [
      		'id' 			 => 'FEE',
          'name' 		 => __('Handling fee'),
          'price' 	 => ceil($value),
          'quantity' => 1
      	];

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

	      $total_amount += $value;
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      session(['transaction_details' => $this->details]);

      $total_amount = format_amount($total_amount, false, $this->decimals);


			$payload = [
				'transaction_details' => 	[
				    'order_id' => generate_transaction_ref(),
				    'gross_amount' => array_sum(array_column($item_details, 'price')),
				],
				'item_details' => $item_details,
				'enabled_payments' => $this->enabled_payments
			];

			$ch 			= curl_init();
			$api_url 	= 'https://app.sandbox.midtrans.com/snap/v1/transactions/';

			if(config('payments.midtrans.mode') === 'live')
			{
				$api_url = 'https://app.midtrans.com/snap/v1/transactions/';
			}
			
			$headers = [
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Basic ' . base64_encode(config('payments.midtrans.server_key').':'),
			];

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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

      if(($result->error_messages ?? null))
      {
      	settype($result->error_messages, 'array');

      	$this->error_msg = ['user_message' => implode(',', array_values($result->error_messages))];

				return;
      }

      return $result;
		} 



		public function status(string $orderId)
		{
			$ch 			= curl_init();
			$api_url 	= "https://api.sandbox.midtrans.com/v2/{$orderId}/status";

			if(config('payments.midtrans.mode') === 'live')
				$api_url = "https://api.midtrans.com/v2/{$orderId}/status";
			
			$headers = [
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Basic ' . base64_encode(config('payments.midtrans.server_key').':'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			return json_decode($result);
		}




		public function approve($orderId)
		{
			$ch 			= curl_init();
			$api_url 	= "https://api.sandbox.midtrans.com/v2/{$orderId}/approve";

			if(config('payments.midtrans.mode') === 'live')
				$api_url = "https://api.midtrans.com/v2/{$orderId}/approve";
			
			$headers = [
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Basic ' . base64_encode(config('payments.midtrans.server_key').':'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			return json_decode($result);
		}




		public function capture($transaction_id)
		{
			$ch 			= curl_init();
			$api_url 	= "https://api.sandbox.midtrans.com/v2/capture";

			if(config('payments.midtrans.mode') === 'live')
				$api_url = "https://api.midtrans.com/v2/capture";
			
			$headers = [
				'Accept: application/json',
				'Content-Type: application/json',
				'Authorization: Basic ' . base64_encode(config('payments.midtrans.server_key').':'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['transaction_id' => $transaction_id]));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			return json_decode($result);
		}
	}