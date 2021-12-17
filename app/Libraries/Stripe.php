<?php

	namespace App\Libraries;


	class Stripe 
	{
		public $name = 'stripe';
		public $success_url;
		public $cancel_url;
		public $payment_method_types = [
			      	'card' => [],
			      	'bancontact' => ['EUR'],
			      	'alipay' => ['AUD', 'CAD', 'EUR', 'GBP', 'HKD', 'JPY', 'NZD', 'SGD', 'USD', 'MYR'],
			      	'eps' => ['EUR'],
			      	'fpx' => ['MYR'],
			      	'giropay' => ['EUR'],
			      	'ideal' => ['EUR'],
			      	'p24' => ['EUR', 'PLN']
			      ];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details  = [];
		public $error_msg = [];
		public $webhooks_to_delete = [];


		public function __construct()
		{
			$this->success_url = route('home.checkout.order_completed', ['stripe_sess_id' => 'CHECKOUT_SESSION_ID']);
			$this->cancel_url = config('checkout_cancel_url');

			exists_or_abort(config('payments.stripe.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Stripe']));

			$this->currency_code = config('payments.currency_code');
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			prepare_currency($this);

      if($method_types = array_filter(explode(',', config('payments.stripe.method_types', ''))))
      {
      	$this->payment_method_types = array_intersect_key($this->payment_method_types, array_flip($method_types));
      }

      $this->payment_method_types = array_filter($this->payment_method_types, function($v, $k)
															      {
															      	return !count($v) || in_array($this->currency_code, $v); 
															      }, ARRAY_FILTER_USE_BOTH);

     	if(!cache('stripe_webhook'))
      {
      	if(!$webhook = $this->delete_duplicate_webhooks(true))
      	{
      		$webhook = $this->create_webhook();

      		$webhook_id     = $webhook->id;
      		$webhook_secret = $webhook->secret;

      	}

      	\Cache::forever('stripe_webhook', ['id' => $webhook_id, 'secret' => $webhook_secret]);
      }

      $this->details = [
      	'items' => [],
	      'total_amount' => 0,
	      'currency' => $this->currency_code,
	      'exchange_rate' => $this->exchange_rate,
	      'custom_amount' => null
	    ];
		}


		// Create checkout session
		public function create_checkout_session(array $params, $user = null)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/checkout/sessions/create
				------------------------------------------------------------------
				curl https://api.stripe.com/v1/checkout/sessions \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -d customer=cus_123 \
				  -d payment_method_types[]=card \
				  -d line_items[][name]=T-shirt \
				  -d line_items[][description]="Comfortable cotton t-shirt" \
				  -d line_items[][images][]="https://example.com/t-shirt.png" \
				  -d line_items[][amount]=500 \
				  -d line_items[][currency]=eur \
				  -d line_items[][quantity]=1 \
				  -d success_url="https://example.com/success" \
				  -d cancel_url="https://example.com/cancel"
				------------------------------------------------------------------
			*/

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
        'amount' => $total_amount,
        'currency' => $this->currency_code,
        'quantity' => 1
      ];


      if($vat = config('payments.vat', 0))
      {
      	$value = (int)ceil(($total_amount * $vat) / 100);

      	$line_items[] = [
      		'name' => __('Tax'),
          'description' => config('payments.vat').'%',
          'amount' => $value,
          'currency' => $this->currency_code,
          'quantity' => 1
	      ];

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	      $total_amount += $value ?? 0;
      }


      if($handling = config('payments.stripe.fee', 0))
      {
      	$value = (int)ceil(format_amount($handling * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

      	$line_items[] = [
      		'name' => __('Fee'),
          'description' => __('Handling fee'),
          'amount' => $value,
          'currency' => $this->currency_code,
          'quantity' => 1
	      ];

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

	      $total_amount += $value;
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);


		  $payload = [
		  	'payment_method_types' => array_keys($this->payment_method_types),
		  	'success_url' => $this->success_url,
		  	'cancel_url' => $this->cancel_url,
		  	'mode' => 'payment',
		  	'payment_intent_data' => [
		  		'capture_method' => 'automatic'
		  	],
		  	'submit_type' => 'pay',
		  	'line_items' => $line_items
		  ];

		  $headers = [ 
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id'),
			];

			$ch = curl_init();

			$post_query = str_replace('CHECKOUT_SESSION_ID', '{CHECKOUT_SESSION_ID}', http_build_query($payload));

			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
			curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);

			$result = json_decode($result);

			if(property_exists($result, "error"))
      {
        $this->error_msg = ['user_message' => json_encode($result->error)];

        return;
      }

      return $result;
		}




		// Retrieve checkout session
		public function get_checkout_session(string $cs = '')
		{
			/*
				API DOC URL : https://stripe.com/docs/api/checkout/sessions/retrieve
				--------------------------------------------------------------------
				curl https://api.stripe.com/v1/checkout/sessions/cs_test_hnVLmJSlnLAeHlPXDMAh0gGbDEfjYEucRfIbMlKdeaHSZGHnE2mrCY4O \
					-u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r:
				--------------------------------------------------------------------
			*/

			$cs OR die();

			$ch = curl_init();

			 $headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id'),
			];

			$expand = http_build_query(['expand' => ['payment_intent']]);

			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/checkout/sessions/{$cs}?{$expand}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			
			curl_close($ch);

			return $result;
		}



		// Retrieve paymeny intents
		public function get_payment_intents(string $pi_id = '')
		{
			/*
				API DOC URL : https://stripe.com/docs/api/payment_intents/retrieve
				--------------------------------------------------------------------
				curl https://api.stripe.com/v1/payment_intents/pi_1FE4IjHCAt5CXcX72ncX42q9 \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r:
				--------------------------------------------------------------------
			*/

			$pi_id OR die();

			$ch = curl_init();

			 $headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id'),
			];

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/payment_intents/{$pi_id}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);

			return $result;
		}



		// Retrieve customer
		public function get_customer($cus)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/customers/retrieve
				--------------------------------------------------------------------
				curl https://api.stripe.com/v1/customers/cus_Flz46Wq3HGZFTJ \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r:
				--------------------------------------------------------------------
			*/

			$cus OR die();

			$ch = curl_init();

			 $headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id'),
			];

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/customers/{$cus}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);

			curl_close($ch);

			return $result;
		}




		// Create a charge 
		public function create_charge($stripeToken)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/charges/create
				-----------------------------------------------------
				curl https://api.stripe.com/v1/charges \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -d amount=2000 \
				  -d currency=eur \
				  -d source=tok_amex \
				  -d description="Charge for example@example.com"
				-----------------------------------------------------
			*/

			$coupon 	= json_decode($this->create_coupon(null, 9, 'once'));
			$customer = json_decode($this->create_customer($stripeToken, null, $coupon->id));

			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$payload = [
				'amount' => 42.99*100,
				'currency' => 'USD',
				'description' => 'Charge for mr X',
				'customer' => $customer->id
			];


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/charges');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}
	


		// Create a customer
		public function create_customer($source = null, 
																		$description = '', 
																		$coupon = null, 
																		$tax_id_data = [])
		{
			/*
				API DOC URL : https://stripe.com/docs/api/customers/create
				---------------------------------------------------------
				curl https://api.stripe.com/v1/customers \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -d description="Customer for jenny.rosen@example.com" \
				  -d source=tok_amex
				  -d coupn=qsfsfqsf
				---------------------------------------------------------
			*/

			$payload = [];

			if($description)
				$payload['description'] = $description;

			if($source)
				$payload['source'] = $source;

			if($coupon)
				$payload['coupon'] = $coupon;

			if($tax_id_data)
				$payload['tax_id_data'] = $tax_id_data;

			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/customers');

			if($payload)
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		// Create a Tax
		public function create_tax(	$display_name, 
																$description = '', 
																$percentage, 
																$jurisdiction = '', 
																$inclusive = false)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/tax_rates/create
				------------------------------------------------------
				curl https://api.stripe.com/v1/tax_rates \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -d display_name=VAT \
				  -d description="VAT Germany" \
				  -d jurisdiction=DE \
				  -d percentage="19.0" \
				  -d inclusive=false
				------------------------------------------------------
			*/

			$payload = [
				'display_name' => $display_name,
				'description' => $description,
				'percentage' => $percentage,
				'jurisdiction' => $jurisdiction,
				'inclusive' => $inclusive
			];

			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/customers');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		// Create a coupon
		public function create_coupon($amount_off = null, 
																	$percent_off = null,
																	$duration = 'once'
																)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/coupons/create
				----------------------------------------------------
				curl https://api.stripe.com/v1/coupons \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -d percent_off=5 \
				  -d duration=once
				----------------------------------------------------
			*/

			$payload = ['duration' => $duration];

			if($amount_off)
				$payload['amount_off'] = $amount_off;
			elseif($percent_off)
				$payload['percent_off'] = $percent_off;

			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/coupons');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		// Create a card token
		public function create_card_token(array $card)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/tokens/create_card
				------------------------------------------------------------
				curl https://api.stripe.com/v1/tokens \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -d card[number]=4000002500003155 \
				  -d card[exp_month]=12 \
				  -d card[exp_year]=2050 \
				  -d card[cvc]=123
				------------------------------------------------------------
			*/

			$payload = [
				'number' => $card['number'] ?? null,
				'exp_month' => $card['exp_month'] ?? null,
				'exp_year' => $card['exp_year'] ?? null,
				'cvc' => $card['cvc'] ?? null
			];

			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/tokens');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return json_decode($result)->id ?? null;
		}




		// Create payment intents
		public function create_payment_intents($stripeToken)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/payment_intents/create
				----------------------------------------------------------------
				curl https://api.stripe.com/v1/payment_intents \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -X POST \
				  -d amount=33.58 \
				  -d currency=usd \
				  -d payment_method_types[]=card
				----------------------------------------------------------------
			*/

			$coupon 	= json_decode($this->create_coupon(null, 5, 'once'));
			$customer = json_decode($this->create_customer($stripeToken, null, $coupon->id));

			$payload = [
				'amount' => 33.58*100,
				'currency' => 'usd',
				'confirm' => 'true',
				'return_url' => 'https://imarket.co/checkout',
				'customer' => $customer->id
			];

			$headers = [
				'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/payment_intents');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			
			$result = curl_exec($ch);

			curl_close($ch);

			$this->delete_customer($customer->id);
			
			return $result;
		}





		// Delete a customer
		public function delete_customer($customer_id)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/customers/delete
				----------------------------------------------------------
				curl https://api.stripe.com/v1/customers/cus_FkJ3U2SRoptyhl \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -X DELETE
				----------------------------------------------------------
			*/

			$headers = [
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/{$customer_id}");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		public function get_balance_transaction(string $txn)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/balance/balance_retrieve
				------------------------------------------------------------------
				curl https://api.stripe.com/v1/balance_transactions/txn_1FJCmcHCAt5CXcX7aUqGNqx2 \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r:
  			------------------------------------------------------------------
			*/
  		
  		$headers = [
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/balance_transactions/{$txn}");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}



		public function refund_transaction(string $charge, float $amount = 0)
		{
			/*
				API DOC URL : https://stripe.com/docs/api/refunds/create
				------------------------------------------------------------------
				curl https://api.stripe.com/v1/refunds \
				  -u sk_test_WlDtXCea4H8cKRFk7bgzW3xq00hcfmF73r: \
				  -d charge=ch_1FDZiGHCAt5CXcX7YYow7zS4
  			------------------------------------------------------------------
			*/
  		
  		$headers = [
  			'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$payload = ['charge' => $charge];

			if($amount)
				$payload['amount'] = ceil($amount*100);


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/refunds");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			return $result;
		}




		public function create_webhook()
		{
			/*
			Doc : https://stripe.com/docs/api/webhook_endpoints/create
			----------------------------------------------------------
			curl https://api.stripe.com/v1/webhook_endpoints \
			  -u sk_test_4eC39HqLyjWDarjtT1zdp7dc: \
			  -d url="https://example.com/my/webhook/endpoint" \
			  -d "enabled_events[]"="charge.failed" \
			  -d "enabled_events[]"="charge.succeeded"

			*/
		
  		$headers = [
  			'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$payload = [
				'url' => route('home.checkout.webhook'),
				'enabled_events' => ['charge.succeeded', 'charge.failed']
			];


			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints");
			
			$result = curl_exec($ch);

			curl_close($ch);
			
			if(!json_decode($result))
			{
				$this->error_msg = ['user_message' => $result];
				
				return;
			}

			return json_decode($result);
		}




		public function get_webhook(string $webhook_id)
		{
			/*
				Docc : https://stripe.com/docs/api/webhook_endpoints/retrieve
				--------------------------------------------------------------
				curl https://api.stripe.com/v1/webhook_endpoints/we_1IWw0Q2eZvKYlo2C2nYid3Rw \
				-u sk_test_4eC39HqLyjWDarjtT1zdp7dc:
			*/

			$headers = [
  			'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints/{$webhook_id}");
			
			$result = curl_exec($ch);

			curl_close($ch);

			if(!json_decode($result))
			{
				$this->error_msg = ['user_message' => $result];
				
				return;
			}

			return json_decode($result);
		}



		public function delete_webhook($webhook)
		{
			/*
				Docc : https://stripe.com/docs/api/webhook_endpoints/delete
				--------------------------------------------------------------
				curl https://api.stripe.com/v1/webhook_endpoints/we_1IWw0Q2eZvKYlo2C2nYid3Rw \
			  -u sk_test_4eC39HqLyjWDarjtT1zdp7dc: \
			  -X DELETE
			*/

			$headers = [
  			'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints/{$webhook->id}");
			
			$result = curl_exec($ch);

			curl_close($ch);

			if(!json_decode($result))
			{
				$this->error_msg = ['user_message' => $result];
				
				return;
			}

			return json_decode($result)->deleted ?? null;

		}



		public function list_webhooks($limit = 100, $starting_after = null)
		{
			/*
				Docc : https://stripe.com/docs/api/webhook_endpoints/delete
				--------------------------------------------------------------
				curl https://api.stripe.com/v1/webhook_endpoints \
			  -u sk_test_4eC39HqLyjWDarjtT1zdp7dc: \
			  -d limit=3 \
			  -G
			*/



			$headers = [
  			'Content-Type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . config('payments.stripe.secret_id')
			];

			$ch = curl_init();

			$query_params = ['limit' => $limit, 'starting_after' => $starting_after];
			$query_params = array_filter($query_params);
			$query_params = http_build_query($query_params);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/webhook_endpoints?{$query_params}");
			
			$result = curl_exec($ch);

			curl_close($ch);

			if(!json_decode($result))
			{
				$this->error_msg = ['user_message' => $result];
				
				return;
			}

			return json_decode($result);
		}



		public function delete_duplicate_webhooks($all = false, $limit = 100, $starting_after = null)
		{
      $webhooks = $this->list_webhooks($limit, $starting_after);

      foreach($webhooks->data as $webhook)
      {
        if($webhook->url === route('home.checkout.webhook'))
        {
          $this->webhooks_to_delete[] = $webhook;
        }
      }

      if($webhooks->has_more ?? null)
      {
        $this->delete_duplicate_webhooks($all, $limit, array_pop($webhooks->data)->id);
      }
      else
      {
      	foreach(array_slice($this->webhooks_to_delete, $all ? 0 : 1) as $webhook)
      	{
      		$this->delete_webhook($webhook);
      	}
      
      	return $all ? null : ($this->webhooks_to_delete[0] ?? null);
      }
		}
	}

	