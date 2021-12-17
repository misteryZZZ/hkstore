<?php

	namespace App\Libraries;


	class Coinpayments 
	{
    public $name = 'coinpayments';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["USD", "CAD", "EUR", "KYD", "AED", "ARS", "AUD", "AZN", "BGN", "BRL", "BYN", "CHF", "CLP", "CNY", "COP", "CUC", "CZK", "DKK", "GBP", "GEL", "GIP", "HKD", "HUF", "IDR", "ILS", "INR", "IRR", "IRT", "ISK", "JPY", "KES", "KRW", "LAK", "MKD", "MUR", "MXN", "MYR", "NGN", "NOK", "NZD", "PEN", "PHP", "PKR", "PLN", "RON", "RUB", "RWF", "SEK", "SGD", "THB", "TND", "TRY", "TWD", "UAH", "VND", "XAG", "XAU", "XOF", "ZAR"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;
    public $api_key;
    public $locale = 'en-US';


    public function __construct()
    {
      $this->return_url = route('home.checkout.save');
      $this->cancel_url = config('checkout_cancel_url');

      exists_or_abort(config('payments.coinpayments.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Coinpayments']));
      
      $this->api_key  = config('payments.coinpayments.api_key');
      $this->currency_code = config('payments.currency_code');
      $this->decimals = config("payments.currencies.{$this->currency_code}.decimals", 2);

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
      extract($params);

      /*IPN Secret         : tKCDOmhrM6okZNn5Bn9N
      IPN URL            : http://localhost/coinpayment/ipn
      Status/Log Email   : foxtinez@gmail.com*/

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


      if(config('payments.pay_what_you_want') && $custom_amount)
      {
        $total_amount = $custom_amount;

        $this->details['custom_amount'] = format_amount($custom_amount * $this->exchange_rate, false, $this->decimals);
      }


      if($coupon->status ?? null && !$custom_amount)
      {
        $total_amount -= $coupon->coupon->discount ?? 0;

        $this->details['items']['discount'] = ['name' => __('Discount'), 'value' => -format_amount($coupon->coupon->discount * $this->exchange_rate, false, $this->decimals)];
      }


      $total_amount = $unit_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if($vat = config('payments.vat', 0))
      {
        $tax = ($unit_amount * $vat) / 100;
        $value = format_amount($tax, false, $this->decimals);

        $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

        $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.coinpayments.fee', 0))
      {
        $value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

        $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

        $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      session(['transaction_details' => $this->details]);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $payload = [
                   'amount'       => $total_amount,
                   'currency1'    => $this->currency_code,
                   'currency2'    => $this->currency_code,
                   'cancel_url'   => $this->cancel_url,
                   'success_url'  => $this->return_url,
                   'buyer_email'  => $user_email,
                   'item_name'    => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
                   'version'      => 1,
                   'cmd'          => 'create_transaction',
                   'key'          => config('payments.coinpayments.public_key'),
                ];

      $payload = http_build_query($payload);

      $header = [
        'HMAC: '.hash_hmac('sha512', $payload, config('payments.coinpayments.public_key')),
        'Content-type: application/x-www-form-urlencoded'
      ];

      $ch      = curl_init();
      $api_url = 'https://www.coinpayments.net/api.php';

      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_URL, $api_url); 
      curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
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

      $response = json_decode($result);

      if(($response->error ?? null) !== 'ok')
      {
        $this->error_msg = ['user_message' => $response->error];

        return;
      }

      return $response->result ?? null;
    }
  }