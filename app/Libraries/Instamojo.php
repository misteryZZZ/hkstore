<?php

	namespace App\Libraries;


	class Instamojo 
	{
    public $name = 'instamojo';
    public $supported_currencies = ["INR"];
    public $return_url;
    public $cancel_url;
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals = 2;
    public $details = [];
    public $error_msg;


    public function __construct()
    {
      $this->return_url = route('home.checkout.order_completed');
      $this->cancel_url = config('checkout_cancel_url');

      exists_or_abort(config('payments.instamojo.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Instamojo']));

      $this->currency_code = config('payments.currency_code');
      $this->decimals      = config("payments.currencies.{$this->currency_code}.decimals");

      prepare_currency($this);

      $this->details = [
        'items' => [],
        'total_amount' => 0,
        'currency' => $this->currency_code,
        'exchange_rate' => $this->exchange_rate,
        'custom_amount' => null
      ];
    }


		public function create_request(array $params, $user = null)
		{
			/* DOC : https://docs.instamojo.com/docs/get-details-of-request
			---------------------------------------------------------------
				curl -X POST https://test.instamojo.com/api/1.1/payment-requests/ \
				-H "X-Api-Key: test_7b1fe0042114fc5c5bf2853a41d" \
				-H "X-Auth-Token: test_ea154b5381c63d13473dc6dc5a9" \
				-H "Content-Type: application/x-www-form-urlencoded" \
				-d "allow_repeated_payments=false&amount=99999&buyer_name=Foxti+Nez&purpose=Multiple+items+sale&redirect_url=https%3A%2F%2Foma.co%2Fcheckout%2Fsave&send_email=false&send_sms=false&email=Foxtinez%40gmail.com"
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


      $total_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if($vat = config('payments.vat', 0))
      {
        $tax = ($total_amount * $vat) / 100;
        $value = format_amount($tax, false, $this->decimals);

        $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

        $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.instamojo.fee', 0))
      {
        $value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

        $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

        $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);


      $total_amount = format_amount($total_amount, false, $this->decimals);


			$api_url = 'https://test.instamojo.com/api/1.1/payment-requests/';

      if(config('payments.instamojo.mode') === 'live')
      {
        $api_url = 'https://www.instamojo.com/api/1.1/payment-requests/';
      }

      $api_key    = config('payments.instamojo.public_api_key');
      $auth_token = config('payments.instamojo.public_auth_token');


			$payload = [
		    'purpose' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
		    'amount' => $total_amount,
		    'redirect_url' => $this->return_url,
		    'send_email' => false,
		    'send_sms' => false,
		    'allow_repeated_payments' => false,
        'webhook' => route('home.checkout.webhook')
      ];

      if(\Auth::check())
      {
        $user = $user ?? auth()->user();

        if(!$user->phone)
        {
          $this->error_msg = ['user_message' => __('Missing phone number, please enter your phone number in your profile page.')];

          return;
        }
        elseif(!$user->lastname || !$user->firstname)
        {
          $this->error_msg = ['user_message' => __('Buyer firstname or lastname is missing.')];

          return;
        }

        $user_info = [
          'phone'      => $user->phone,
          'buyer_name' => "{$user->lastname} {$user->firstname}",
          'email'      => $user->email
        ];

        $payload = array_merge($payload, array_filter($user_info));
      }

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Api-key: {$api_key}", "X-Auth-Token: {$auth_token}"]);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));

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

      if(!($result->success ?? null))
      {
        settype($result->message, 'array');

        $user_message = [];

        foreach($result->message as $k => $err)
        {
          $user_message[] = "{$k} : ".implode(',', $err);
        }

        $this->error_msg = ['user_message' =>  implode(',', $user_message)];

        return;
      }

			return $result->payment_request ?? null;
		}



		public function get_payment_details($payment_request_id, $payment_id)
		{
			/* DOC : https://docs.instamojo.com/docs/get-details-of-payment
			---------------------------------------------------------------
			curl https://www.instamojo.com/api/1.1/payment-requests/d66cb29dd059482e8072999f995c4eef/MOJO5a06005J21512197/ \
			--header "X-Api-Key: d82016f839e13cd0a79afc0ef5b288b3" \
			--header "X-Auth-Token: 3827881f669c11e8dad8a023fd1108c2"
			*/


			$api_url = "https://test.instamojo.com/api/1.1/payment-requests/{$payment_request_id}/{$payment_id}/";

      if(config('payments.instamojo.mode') === 'live')
      {
        $api_url = "https://www.instamojo.com/api/1.1/payment-requests/{$payment_request_id}/{$payment_id}/";
      }

      $api_key    = config('payments.instamojo.public_api_key');
      $auth_token = config('payments.instamojo.public_auth_token');

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_HTTPGET, 1);
      curl_setopt($ch, CURLOPT_URL, $api_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-Api-key: {$api_key}", "X-Auth-Token: {$auth_token}"]);

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


      if(!($result->success ?? null))
      {
        settype($result->message, 'array');

        $user_message = [];

        foreach($result->message as $k => $err)
        {
          $user_message[] = "{$k} : ".implode(',', $err);
        }

        $this->error_msg = ['user_message' =>  implode(',', $user_message)];

        return;
      }

			return $result->payment_request ?? null;
		}




	}



