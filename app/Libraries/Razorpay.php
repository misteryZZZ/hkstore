<?php

	namespace App\Libraries;

	use App\User;


	class Razorpay 
	{
		public $name = 'razorpay';
		public $callback_url;
		public $cancel_url;
		public $supported_currencies = ["AED","ALL","AMD","ARS","AUD","AWG","BBD","BDT","BMD","BND","BOB","BSD","BWP","BZD","CAD","CHF","CNY","COP","CRC","CUP","CZK","DKK","DOP","DZD","EGP","ETB","EUR","FJD","GBP","GHS","GIP","GMD","GTQ","GYD","HKD","HNL","HRK","HTG","HUF","IDR","ILS","INR","JMD","KES","KGS","KHR","KYD","KZT","LAK","LBP","LKR","LRD","LSL","MAD","MDL","MKD","MMK","MNT","MOP","MUR","MVR","MWK","MXN","MYR","NAD","NGN","NIO","NOK","NPR","NZD","PEN","PGK","PHP","PKR","QAR","RUB","SAR","SCR","SEK","SGD","SLL","SOS","SSP","SVC","SZL","THB","TTD","TZS","USD","UYU","UZS","YER","ZAR"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details = [];
		public $error_msg;

		public function __construct()
		{
			$this->callback_url = route('home.checkout.order_completed');
			$this->cancel_url   = config('checkout_cancel_url');

			exists_or_abort(config('payments.razorpay.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Razorpay']));

			prepare_currency($this);

			$this->details = [
				'items' => [],
				'total_amount' => 0,
				'currency' => $this->currency_code,
				'exchange_rate' => $this->exchange_rate,
				'custom_amount' => null
			];
		}




		public function create_payment_link(array $params, $user = null)
		{			/*
				DOC : https://razorpay.com/docs/payment-links/api/v1/create/#request-parameters
				---------------------------------------------------------------------
				curl -u rzp_test_f8ONjSq19XZhLH:M6NbpABbYBmKz6noDAYOTTxZ \
				-X POST https://api.razorpay.com/v1/invoices/ \
				-H 'Content-type: application/json' \
				-d '{
					"customer": {
						"name": "Acme Enterprises",
						"email": "admin@aenterprises.com",
						"contact": "9999999999"
					},
					"type": "link",
					"view_less": 1,
					"amount": 670042,
					"currency": "INR",
					"description": "Payment Link for this purpose - cvb.",
					"receipt": "TS91",
					"reminder_enable": true,
					"sms_notify": 1,
					"email_notify": 1,
					"expire_by": 1793630556,
					"callback_url": "https://example-callback-url.com/",
					"callback_method": "get"
				}'
			*/

			extract($params);
			
			$total_amount = 0;

			foreach($cart as $item)
			{
				$total_amount += $item->price;

				$this->details['items'][] = [
					'name'    => $item->name, 
					'value'   => format_amount($item->price * $this->exchange_rate, false, $this->decimals),
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

			$total_amount = (int)round(format_amount($total_amount * $this->exchange_rate, false, $this->decimals) * pow(10, $this->decimals));

			if($vat = config('payments.vat', 0))
			{
				$value = (int)round(($total_amount * $vat) / 100);

				$this->details['items']['tax'] = ['name' => __('Tax'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

				$total_amount += $value ?? 0;
			}


			if($handling = config('payments.razorpay.fee', 0))
			{
				$value = (int)round(format_amount($handling * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

				$this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

				$total_amount += $value;
			}


			$this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);


			$payload = [
				"type" => "link",
				"view_less" => 1,
				"amount" => round($total_amount),
				"currency" => $this->currency_code,
				"reminder_enable" => false,
				"description" => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
				"sms_notify" => 0,
				"email_notify" => 0,
				"callback_url" => $this->callback_url,
				"callback_method" => "get"
			];

			if($user instanceof User)
			{
				$payload['customer'] =  [
																	"name" => $user->name,
																	"email" => $user->email
																];
			}

			$ch      = curl_init();
			$api_url = 'https://api.razorpay.com/v1/invoices';

			$key_id      = config('payments.razorpay.client_id');
			$key_secret  = config('payments.razorpay.secret_id');
			
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_USERPWD, "{$key_id}:{$key_secret}");
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
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

			if(property_exists($result, 'error'))
			{
				$this->error_msg = ['user_message' => $result->error->code.' : '.$result->error->description];

				return;
			}

			return $result;
		}



		public function fetch_payment(string $razorpay_payment_id)
		{
			/*
				DOC : https://razorpay.com/docs/api/payments/#fetch-a-payment
				--------------------------------------------------------------
				curl -u rzp_test_f8ONjSq19XZhLH:M6NbpABbYBmKz6noDAYOTTxZ \
				-X GET https://api.razorpay.com/v1/payments/pay_ELaVWiGiOQMkOB
			*/

			$ch 		 = curl_init();
			$api_url = "https://api.razorpay.com/v1/payments/{$razorpay_payment_id}";

			$key_id      = config('payments.razorpay.client_id');
			$key_secret  = config('payments.razorpay.secret_id');

			curl_setopt($ch, CURLOPT_USERPWD, "{$key_id}:{$key_secret}");
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);
			
			if(curl_errno($ch))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $error_msg);
			}
			
			curl_close($ch);

			$result = json_decode($result);

			if(property_exists($result, 'error'))
			{
				exists_or_abort(null, $result->error->code.' : '.$result->error->description);
			}

			return $result;
		}



		public function fetch_invoice(string $razorpay_invoice_id)
		{
			/*
				DOC : https://razorpay.com/docs/api/invoices/#fetch-an-invoice
				--------------------------------------------------------------
				curl -u rzp_test_32hsbEKriO6ai4:SC6d7z4FcgX2wJj49obRRT4M \
				https://api.razorpay.com/v1/invoices/inv_gHQwerty123ggd
			*/

			exists_or_abort(config('payments.razorpay.enabled'), 'Razorpay is not enabled');

			$ch 		 = curl_init();
			$api_url = "https://api.razorpay.com/v1/invoices/{$razorpay_invoice_id}";

			$key_id      = config('payments.razorpay.client_id');
			$key_secret  = config('payments.razorpay.secret_id');

			curl_setopt($ch, CURLOPT_USERPWD, "{$key_id}:{$key_secret}");
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);
			
			if(curl_errno($ch))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $error_msg);
			}
			
			curl_close($ch);

			$result = json_decode($result);

			if(property_exists($result, 'error'))
			{
				exists_or_abort(null, $result->error->code.' : '.$result->error->description);
			}

			return $result;
		}



		public function refund_payment(string $payment_id, int $amount)
		{
			/*
				DOC : https://razorpay.com/docs/api/refunds/#refund-a-payment
				---------------------------------------------------------------
				curl -u <YOUR_KEY_ID>:<YOUR_KEY_SECRET> \
					-X POST \
					https://api.razorpay.com/v1/payments/pay_29QQoUBi66xm2f/refund
					-H 'Content-Type: application/json' \
					-d '{
						"amount": 20000"
				}'
			*/

			exists_or_abort(config('payments.razorpay.enabled'), 'Razorpay is not enabled');

			$ch 		 = curl_init();
			$api_url = "https://api.razorpay.com/v1/payments/{$payment_id}/refund";

			$key_id      = config('payments.razorpay.client_id');
			$key_secret  = config('payments.razorpay.secret_id');

			curl_setopt($ch, CURLOPT_USERPWD, "{$key_id}:{$key_secret}");
			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

			if($amount)
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['amount' => round($amount*100)]));
			}

			$result = curl_exec($ch);

			if(curl_errno($ch))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $error_msg);
			}

			curl_close($ch);

			return json_decode($result);
		}
	}