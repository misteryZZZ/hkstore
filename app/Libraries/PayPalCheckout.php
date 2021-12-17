<?php

	namespace App\Libraries;


	class PayPalCheckout 
	{
		public $name = 'paypal';
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ["AUD", "BRL", "CAD", "CNY", "CZK", "DKK", "EUR", "HKD", "HUF", "INR", "ILS", "JPY", "MYR", "MXN", "TWD", "NZD", "NOK", "PHP", "PLN", "GBP", "RUB", "SGD", "SEK", "CHF", "THB", "USD"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $error_msg;


		public function __construct()
		{
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

			exists_or_abort(config('payments.paypal.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'PayPal']));
            
			if(!cache('paypal_access_token'))
			{				
				\Cache::put('paypal_access_token', $this->access_token(), now()->addMinutes(55));
			}
			

			/*if(!cache('paypal_webhook'))
			{
				$response = $this->create_webhook();

				if($response->name === 'WEBHOOK_URL_ALREADY_EXISTS')
				{
					$webhooks = $this->get_webhooks();
					
					$webhook = collect($webhooks)->where('url', route('home.checkout.webhook'))->first();

					$this->delete_webhook($webhook['id']);

					$response = $this->create_webhook();
				}

				cache(['paypal_webhook' => $response->id ?? null]);
			}*/


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




		public function access_token()
		{
			$ch 		 = curl_init();
			$api_url = 'https://api.sandbox.paypal.com/v1/oauth2/token';

			if(config('payments.paypal.mode') === 'live')
				$api_url = 'https://api.paypal.com/v1/oauth2/token';

			$header = ['Accept: application/json',
								 'Accept-Language: en_US'];

			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_USERPWD, config('payments.paypal.client_id') . ':' . config('payments.paypal.secret_id'));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);
			
			if(curl_errno($ch))
			{
				$error_msg = curl_error($ch);

				curl_close($ch);

				exists_or_abort(null, $error_msg);
			}

			return json_decode($result)->access_token;
		}





		public function create_order(array $params)
		{			
			extract($params);

			$ch 			= curl_init();
			$api_url 	= 'https://api.sandbox.paypal.com/v2/checkout/orders';

			if(config('payments.paypal.mode') === 'live')
				$api_url = 'https://api.paypal.com/v2/checkout/orders';

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


      $total_amount = $unit_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);

			$breakdown = [];

      $breakdown['item_total'] = [
      	'currency_code' => $this->currency_code,
      	'value' 				=> $unit_amount
      ];


      if($vat = config('payments.vat', 0))
      {
      	$tax = ($unit_amount * $vat) / 100;
      	$value = format_amount($tax, false, $this->decimals);

      	$breakdown['tax_total'] = ['currency_code' => $this->currency_code,'value' => $value];

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

	      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.paypal.fee', 0))
      {
      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

      	$breakdown['handling'] = ['currency_code' => $this->currency_code,'value' => $value];

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

	      $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      $total_amount = format_amount($total_amount, false, $this->decimals);

			$payload = [
          'intent' => 'CAPTURE',
          'application_context' => [
          	'return_url' => $this->return_url,
          	'cancel_url' => $this->cancel_url,
          	//'webhook_url' => route('home.checkout.webhook'),
          	'shipping_preference' => 'NO_SHIPPING'
          ],
          'purchase_units' => [
            [
              'reference_id' => generate_transaction_ref(),
              'amount' => [
                'currency_code' => $this->currency_code,
                'value' => $total_amount,
                'breakdown' => $breakdown
              ],
              'items' => [
              	[
                  'name' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
                  'unit_amount' => [
                    'currency_code' => $this->currency_code,
                    'value' => $unit_amount,
                  ],
                  'quantity' => 1,
                  'category' => 'DIGITAL_GOODS'
                ]
              ]
            ]
          ]
      ];

      $headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}







		public function capture_order(string $order_id = NULL)
		{
			exists_or_abort($order_id, 'Order id is missing');

			$ch 		 = curl_init();
			$api_url = "https://api.sandbox.paypal.com/v1/checkout/orders/{$order_id}/capture";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api.paypal.com/v1/checkout/orders/{$order_id}/capture";

			$headers = [
				'Content-Type: application/json',
				'Content-Length: 0',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		public function order_details(string $order_id = null)
		{
			exists_or_abort($order_id, 'Order id is missing');

			$ch 		 = curl_init();
			$api_url = "https://api.sandbox.paypal.com/v2/checkout/orders/{$order_id}";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api.paypal.com/v2/checkout/orders/{$order_id}";

			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		public function refund_order(string $order_id = null, array $payload = [])
		{
			exists_or_abort($order_id, 'Order id is missing');

			$ch 		 	 = curl_init();
			$api_url   = "https://api.sandbox.paypal.com/v2/payments/captures/{$order_id}/refund";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api.paypal.com/v2/payments/captures/{$order_id}/refund";


			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			if($payload)
			{
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
			}

			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}



		public function create_webhook()
		{
			/* https://developer.paypal.com/docs/api/webhooks/v1/#webhooks_post
			---------------------------------------------------------------------
				curl -v -X POST https://api-m.sandbox.paypal.com/v1/notifications/webhooks \
				-H "Content-Type: application/json" \
				-H "Authorization: Bearer Access-Token" \
				-d '{
				  "url": "https://example.com/example_webhook",
				  "event_types": [
				    {
				      "name": "PAYMENT.AUTHORIZATION.CREATED"
				    },
				    {
				      "name": "PAYMENT.AUTHORIZATION.VOIDED"
				    }
				  ]
				}'
			*/

			$ch 		 	 = curl_init();
			$api_url   = "https://api-m.sandbox.paypal.com/v1/notifications/webhooks";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api-m.paypal.com/v1/notifications/webhooks";


			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			$payload = [
				'url' => route('home.checkout.webhook'),
				'event_types' => [
					['name' => 'PAYMENT.CAPTURE.COMPLETED']
				]
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

			$result = curl_exec($ch);

			curl_close($ch);

			return json_decode($result);
		}



		public function get_webhooks()
		{
			$ch 		 	 = curl_init();
			$api_url   = "https://api-m.sandbox.paypal.com/v1/notifications/webhooks";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api-m.paypal.com/v1/notifications/webhooks";


			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);

			$result = json_decode($result, true);

			return $result['webhooks'] ?? [];
		}



		public function delete_webhook($webhook_id)
		{
			$ch 		 	 = curl_init();
			$api_url   = "https://api-m.sandbox.paypal.com/v1/notifications/webhooks/{$webhook_id}";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api-m.paypal.com/v1/notifications/webhooks/{$webhook_id}";


			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);
		}




		public function verify_webhook_signature(array $payload)
		{
			/*
				https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature-post-request-body
				---------------------------------------------------------------------------------------------
				curl -v -X POST https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature \
				-H "Content-Type: application/json" \
				-H "Authorization: Bearer Access-Token" \
				-d '{
				  "transmission_id": "69cd13f0-d67a-11e5-baa3-778b53f4ae55",
				  "transmission_time": "2016-02-18T20:01:35Z",
				  "cert_url": "cert_url",
				  "auth_algo": "SHA256withRSA",
				  "transmission_sig": "lmI95Jx3Y9nhR5SJWlHVIWpg4AgFk7n9bCHSRxbrd8A9zrhdu2rMyFrmz+Zjh3s3boXB07VXCXUZy/UFzUlnGJn0wDugt7FlSvdKeIJenLRemUxYCPVoEZzg9VFNqOa48gMkvF+XTpxBeUx/kWy6B5cp7GkT2+pOowfRK7OaynuxUoKW3JcMWw272VKjLTtTAShncla7tGF+55rxyt2KNZIIqxNMJ48RDZheGU5w1npu9dZHnPgTXB9iomeVRoD8O/jhRpnKsGrDschyNdkeh81BJJMH4Ctc6lnCCquoP/GzCzz33MMsNdid7vL/NIWaCsekQpW26FpWPi/tfj8nLA==",
				  "webhook_id": "1JE4291016473214C",
				  "webhook_event": {
				    "id": "8PT597110X687430LKGECATA",
				    "create_time": "2013-06-25T21:41:28Z",
				    "resource_type": "authorization",
				    "event_type": "PAYMENT.AUTHORIZATION.CREATED",
				    "summary": "A payment authorization was created",
				    "resource": {
				      "id": "2DC87612EK520411B",
				      "create_time": "2013-06-25T21:39:15Z",
				      "update_time": "2013-06-25T21:39:17Z",
				      "state": "authorized",
				      "amount": {
				        "total": "7.47",
				        "currency": "USD",
				        "details": {
				          "subtotal": "7.47"
				        }
				      },
				      "parent_payment": "PAY-36246664YD343335CKHFA4AY",
				      "valid_until": "2013-07-24T21:39:15Z",
				      "links": [
				        {
				          "href": "https://api-m.paypal.com/v1/payments/authorization/2DC87612EK520411B",
				          "rel": "self",
				          "method": "GET"
				        },
				        {
				          "href": "https://api-m.paypal.com/v1/payments/authorization/2DC87612EK520411B/capture",
				          "rel": "capture",
				          "method": "POST"
				        },
				        {
				          "href": "https://api-m.paypal.com/v1/payments/authorization/2DC87612EK520411B/void",
				          "rel": "void",
				          "method": "POST"
				        },
				        {
				          "href": "https://api-m.paypal.com/v1/payments/payment/PAY-36246664YD343335CKHFA4AY",
				          "rel": "parent_payment",
				          "method": "GET"
				        }
				      ]
				    }
				  }
				}'
			*/


			$ch 		 	 = curl_init();
			$api_url   = "https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api-m.paypal.com/v1/notifications/verify-webhook-signature";


			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			$payload = [
        'transmission_sig'   => request()->header('paypal-transmission-sig'),
        'transmission_id'    => request()->header('paypal-transmission-id'),
        'transmission_time'  => request()->header('paypal-transmission-time'),
        'auth_algo'          => request()->header('paypal-auth-algo'),
        'cert_url'           => request()->header('paypal-cert-url'),
        'webhook_id'         => request()->header('webhook_id'),
        'webhook_event'      => request()->post()
      ];

			curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

			$result = curl_exec($ch);

			curl_close($ch);

			return $result;
		}




		public function payout($data)
		{
			/*
				DOC: https://developer.paypal.com/docs/api/payments.payouts-batch/v1/#payouts
				-----------------------------------------------------------------------------
				curl -v -X POST https://api.sandbox.paypal.com/v1/payments/payouts \
				-H "Content-Type: application/json" \
				-H "Authorization: Bearer Access-Token" \
				-d '{
				  "sender_batch_header": {
				    "sender_batch_id": "Payouts_2018_100007",
				    "email_subject": "You have a payout!",
				    "email_message": "You have received a payout! Thanks for using our service!"
				  },
				  "items": [
				    {
				      "recipient_type": "EMAIL",
				      "amount": {
				        "value": "9.87",
				        "currency": "USD"
				      },
				      "note": "Thanks for your patronage!",
				      "sender_item_id": "201403140001",
				      "receiver": "receiver@example.com",
				      "alternate_notification_method": {
				        "phone": {
				          "country_code": "91",
				          "national_number": "9999988888"
				        }
				      },
				      "notification_language": "fr-FR"
				    },
				    {
				      "recipient_type": "PHONE",
				      "amount": {
				        "value": "112.34",
				        "currency": "USD"
				      },
				      "note": "Thanks for your support!",
				      "sender_item_id": "201403140002",
				      "receiver": "91-734-234-1234"
				    },
				    {
				      "recipient_type": "PAYPAL_ID",
				      "amount": {
				        "value": "5.32",
				        "currency": "USD"
				      },
				      "note": "Thanks for your patronage!",
				      "sender_item_id": "201403140003",
				      "receiver": "G83JXTJ5EHCQ2"
				    }
				  ]
				}'
			*/

			extract($data);
			
			$payload = [
				"sender_batch_header" => [
					"sender_batch_id" => "Payouts_".now()->format('Y_m_d_H_i_s'),
					"email_subject" 	=> __("Payment from :app_name", ['app_name' => mb_ucfirst(config('app.name'))]),
					"email_message" 	=> __("You have received your affiliate earnings, thanks for being part of :app_name.", ['app_name' => mb_ucfirst(config('app.name'))])
				],
				"items" => [
					[
						"recipient_type" => "EMAIL",
						"amount" => [
							"value" => format_amount($earnings),
							'currency' => config('payments.currency_code')
						],
						"note" => __('Thanks for being part of :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
						"sender_item_id" => str_ireplace('_', '', now()->format('Y_m_d_H_i_s')),
				    "receiver" => $paypal_account
					]
				]
			];

			$api_url   = "https://api.sandbox.paypal.com/v1/payments/payouts";

			if(config('payments.paypal.mode') === 'live')
			{
				$api_url = "https://api.paypal.com/v1/payments/payouts";				
			}

			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			$ch = curl_init($api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

			$result = curl_exec($ch);

			curl_close($ch);

			if($res = json_decode($result))
			{
				if(property_exists($res, 'batch_header'))
				{
					$status = json_decode($this->get_payout_status($res->batch_header->payout_batch_id));

					while($status->batch_header->batch_status === 'PENDING')
					{
						sleep(10);
						$status = json_decode($this->get_payout_status($res->batch_header->payout_batch_id));
					}

					$response = ['status' 					=> true, 
											 'message' 					=> __('Payment done successfully.'), 
											 'payout_batch_id' 	=> $res->batch_header->payout_batch_id];
				}
				else
				{
					$response = ['status' => false, 'message' => $result];
				}

				return $response;
			}

			return ['status' => false, 'message' => $result];
		}





		private function get_payout_status(string $payout_batch_id)
		{
			/*
				DOC: https://developer.paypal.com/docs/api/payments.payouts-batch/v1/#payouts_get
				---------------------------------------------------------------------------------
				curl -v -X GET https://api.sandbox.paypal.com/v1/payments/payouts/FYXMPQTX4JC9N \
				-H "Content-Type: application/json" \
				-H "Authorization: Bearer Access-Token"
			*/

			if(!$payout_batch_id) 
				return response()->json(['status' => false, 'message' => __('Missing payout_batch_id param.')]);

			$api_url   = "https://api.sandbox.paypal.com/v1/payments/payouts/{$payout_batch_id}";

			if(config('payments.paypal.mode') === 'live')
				$api_url = "https://api.paypal.com/v1/payments/payouts/{$payout_batch_id}";


			$headers = [
				'Content-Type: application/json',
				'Authorization: Bearer ' . cache('paypal_access_token'),
			];

			$ch = curl_init($api_url);

			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}

	}