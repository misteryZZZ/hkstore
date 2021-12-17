<?php

	namespace App\Libraries;


	class Adyen 
	{
    public $name = 'adyen';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["AED", "AUD", "BGN", "BHD", "BRL", "CAD", "CHF", "CNY", "CZK", "DKK", "EUR", "GBP", "HKD", "HRK", "HUF", "ISK", "ILS", "INR", "JOD", "JPY", "KRW", "KWD", "MYR", "NOK", "NZD", "OMR", "PLN", "QAR", "RON", "RUB", "SAR", "SEK", "SGD", "THB", "TWD", "USD", "ZAR"];
    public $supported_locales = ["zh-CN", "zh-TW", "da-DK", "nl-NL", "en-US", "fi-FI", "fr-FR", "de-DE", "it-IT", "ja-JP", "ko-KR", "no-NO", "pl-PL", "pt-BR", "ru-RU", "es-ES", "sv-SE"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;
    public $api_key;
    public $locale = 'en-US';


    public function __construct(string $locale = null)
    {
      $this->return_url = route('home.checkout.order_completed');
      $this->cancel_url = config('checkout_cancel_url');

      exists_or_abort(config('payments.adyen.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Adyen']));
      
      $this->api_key  = config('payments.adyen.api_key');
      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

      prepare_currency($this);

      if($locale && in_array(str_replace('_', '-', $locale), $this->supported_locales))
      {
        $this->locale = str_replace('_', '-', $locale);
      }

      $this->details = [
        'items' => [],
        'total_amount' => 0,
        'currency' => $this->currency_code,
        'exchange_rate' => $this->exchange_rate,
        'custom_amount' => null
      ];
    }



		public function create_payment_link(array $params)
		{
			/* DOC : https://docs.adyen.com/checkout/pay-by-link#create-a-payment-link
			--------------------------------------------------------------
        curl https://checkout-test.adyen.com/v53/paymentLinks \
        -H "x-API-key: YOUR_X-API-KEY" \
        -H "content-type: application/json" \
        -d '{
          "reference": "YOUR_PAYMENT_REFERENCE",
          "amount": {
            "value": 4200,
            "currency": "EUR"
          },
          "shopperReference": "UNIQUE_SHOPPER_ID_6728",
          "description": "Blue Bag - ModelM671",
          "countryCode": "NL",
          "merchantAccount": "YOUR_MERCHANT_ACCOUNT",
          "shopperLocale": "nl-NL"
        }'
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

      if($vat = config('payments.vat', 0))
      {
        $value = (int)ceil(($total_amount * $vat) / 100);

        $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

        $total_amount += $value ?? 0;
      }


      if($handling = config('payments.adyen.fee', 0))
      {
        $value = (int)ceil(format_amount($handling * $this->exchange_rate, false, $this->decimals)* pow(10, $this->decimals));

        $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => format_amount($value / pow(10, $this->decimals), false, $this->decimals)];

        $total_amount += $value;
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);


			$payload = [
				"reference" => generate_transaction_ref(),
        "amount" => [
          "value" => $total_amount,
          "currency" => $this->currency_code
        ],
        "returnUrl" => $this->return_url,
        "merchantAccount" => config('payments.adyen.merchant_account'),
        "shopperLocale" => $this->locale
      ];



      $ch      = curl_init();
      $api_url = 'https://checkout-test.adyen.com/v53/paymentLinks';

      if(config('payments.adyen.mode') === 'live')
      {
        $api_url = 'https://checkout.adyen.com/v53/paymentLinks';
      }

      curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_URL, $api_url); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "x-API-key: {$this->api_key}"]);
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

      if(stripos(($result->status ?? null), '40') !== false)
      {
        $this->error_msg = ['user_message' => "{$result->errorCode} - {$result->message}"];

        return;
      }

			return $result;
		}



    public function verify_payment($payment_id)
    {      
      $api_url = "https://checkout-test.adyen.com/v53/paymentLinks/{$payment_id}";

      if(config('payments.adyen.mode') === 'live')
      {
        $api_url = "https://checkout.adyen.com/v53/paymentLinks/{$payment_id}";
      }

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_HTTPGET, 1);
      curl_setopt($ch, CURLOPT_URL, $api_url); 
      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-type: application/json", "x-API-key: {$this->api_key}"]);
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

      if(isset($result->errorCode))
      {
        $this->error_msg = ['user_message' => "{$result->errorCode} - {$result->message}"];

        return;
      }

      return $result;
    }




  }