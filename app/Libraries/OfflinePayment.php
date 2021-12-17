<?php

	namespace App\Libraries;


	class OfflinePayment 
	{
    public $name = 'offline';
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details = [];


		public function create_payment(array $params)
		{
			exists_or_abort(config('payments.offline.enabled'), __('Offline payment is not enabled'));

			$this->currency_code = config('payments.currency_code');
			$this->decimals = config("payments.currencies.{$this->currency_code}.decimals");

			prepare_currency($this);

      extract($params);

      $this->details = [
      	'items' => [], 
	      'total_amount' => 0,
	      'discount' => 0,
	      'currency' => $this->currency_code, 
	      'exchange_rate' => $this->exchange_rate,
	      'reference_id' => uuid6(),
	      'custom_amount' => null
	    ];

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


      if($handling = config('payments.offline.fee', 0))
      {
        $value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

        $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

        $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), $this->decimals, false);

      return $this;
		}


		public function getDetails()
		{
			return $this->details;
		}
	}