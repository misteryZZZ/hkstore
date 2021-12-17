<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Cache, Session, Auth };

	class Skrill 
	{
		public $name = 'skrill';
		public $status_url;
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ['EUR' , 'TWD', 'USD', 'THB', 'GBP', 'CZK', 'HKD', 'HUF', 'SGD', 'BGN', 'JPY', 'PLN', 'CAD', 'ISK', 'AUD', 'INR', 'CHF', 'KRW', 'DKK', 'ZAR', 'SEK', 'RON', 'NOK', 'HRK', 'ILS', 'JOD', 'MYR', 'OMR', 'NZD', 'RSD', 'TRY', 'TND', 'AED', 'MAD', 'QAR', 'SAR'];
		public $payment_methods = ['WLT', 'NTL', 'PSC', 'PCH', 'ACC', 'VSA', 'MSC', 'VSE', 'MAE', 'GCB', 'DNK', 'PSP', 'CSI', 'ACH', 'GCI', 'IDL', 'PWY', 'GLU', 'ALI', 'ADB', 'AOB', 'ACI'];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $error_msg;



		public function __construct()
		{
			$this->status_url = route('home.checkout.webhook');
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

      exists_or_abort(config('payments.skrill.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Skrill']));

      $this->api_key  = config('payments.skrill.api_key');
      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

      prepare_currency($this);

      if($method_types = array_filter(explode(',', config('payments.skrill.methods'))))
      {
      	$this->payment_methods = array_intersect($this->payment_methods, $method_types);
      }
      
      $this->details = [
        'items' => [],
        'gross_amount' => 0,
        'currency' => $this->currency_code,
        'exchange_rate' => $this->exchange_rate,
        'custom_amount' => null
      ];
		}



		public function checkout_session_id(array $params, $user_id = null)
		{
			extract($params);

			$ch 		 = curl_init();
			$api_url = 'https://pay.skrill.com';

			$gross_amount = 0;
			$fee 					= 0;
			$tax 					= 0;
			$total_due 		= 0;

			foreach($cart as $item)
			{
      	$gross_amount += $item->price;

				$this->details['items'][] = [
					'name' => $item->name, 
					'value' => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
					'license' => $item->license_id ?? null
				];
			}


			if(config("pay_what_you_want.enabled") && config('pay_what_you_want.for.'.($subscription_id ? 'subscriptions': 'products')) && $custom_amount)
			{
				$gross_amount = $custom_amount;

				$this->details['custom_amount'] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
			}


			if(($coupon->status ?? null) && !$custom_amount)
      {
      	$gross_amount -= $coupon->coupon->discount ?? 0;

      	$this->details['items']['discount'] = ['name' => __('Discount'), 'value' => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
      }


      $gross_amount = format_amount($gross_amount * $this->exchange_rate, false, $this->decimals);


      if($vat = config('payments.vat', 0))
      {
      	$tax = format_amount(($gross_amount * $vat) / 100, false, $this->decimals);

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $tax];

	      $gross_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.skrill.fee', 0))
      {
      	$fee = format_amount($handling * $this->exchange_rate, false, $this->decimals);

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $fee];

	      $gross_amount += format_amount($fee, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      // session(['transaction_details' => $this->details]);

      $total_due = format_amount(($gross_amount + $fee + $tax), false, $this->decimals);
      $order_id  = generate_transaction_ref();

			$payload = [
			  "pay_to_email" => config('payments.skrill.merchant_account'),
			  "prepare_only" => 1,
			  "status_url" => $this->status_url."?order_id={$order_id}",
			  "return_url" => $this->return_url,
			  "return_url_text" => "Return",
			  "return_url_target" => "1",
			  "cancel_url" => $this->cancel_url,
			  "cancel_url_target" => "1",
			  "dynamic_descriptor" => "Descriptor",
			  "merchant_fields" => "user_id",
			  "user_id" => $user_id,
			  "language" => "EN",
			  "logo_url" => asset("storage/images/".config('app.logo')),
			  "amount" => $total_due,
			  "currency" => $this->currency_code,
			  "amount2_description" => __('Gross amount : '),
			  "amount2" => $gross_amount,
			  "amount3_description" => __('Handling Fees : '),
			  "amount3" => $handling,
			  "amount4_description" => __('VAT :percent% : ', ['percent' => config('payments.vat')]),
			  "amount4" => $tax,
			  "detail1_description" => "ID : ",
			  "detail1_text" => generate_transaction_ref(),
			  "submit_id" => "Submit",
			  "Pay" => "Pay",
			  "payment_methods" => implode(',', $this->payment_methods)
			];

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: en_US']);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			$curl_errno = curl_errno($ch);
			$error_msg = curl_error($ch);

			curl_close($ch);

			if($curl_errno)
			{
				$this->error_msg = ['user_message' => $error_msg];

				return;
			}

			if(json_decode($result))
			{
				$this->error_msg = ['user_message' => $result];

				return;
			}

			return ['sid' => $result, 'order_id' => $order_id];
		}


	}