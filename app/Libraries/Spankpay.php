<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Cache, Session, Auth };


	class Spankpay
	{
		public $name = 'spankpay';
		public $return_url;
		public $cancel_url;
		private $supported_currencies = ["BTC", "ETH", "LTC", "ZEC", "XMR", "TEST-ETH"];
		public $currency_code;
		public $default_currency = 'BTC';
		public $exchange_rate = 1;
		public $decimals;
		public $details = [];
		public $error_msg;


		public function __construct()
		{
			$this->return_url = route('home.checkout.save');
			$this->cancel_url = config('checkout_cancel_url');

			if(!config('payments.spankpay.enabled'))
			{
				return response()->json(['user_message' => __(':payment_proc is not enabled', ['payment_proc' =>  'Spankpay'])]);
			}

			$this->currency_code = session('currency', config('payments.currency_code'));
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


      $total_amount = $unit_amount = format_amount($total_amount * $this->exchange_rate, false, $this->decimals);


      if($vat = config('payments.vat', 0))
      {
      	$tax = ($unit_amount * $vat) / 100;
      	$value = format_amount($tax, false, $this->decimals);

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

	      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.spankpay.fee', 0))
      {
      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

	      $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);


      $total_amount = format_amount($total_amount, false, $this->decimals);

			return [
								 'status'       => true,
                 'order_id'		  => generate_transaction_ref(),
                 'amount'       => $total_amount,
                 'currency'     => $this->currency_code,
                 'cancel_url'		=> $this->cancel_url,
                 'success_url'  => $this->return_url,
                 'title'				=> 'Order',
                 'description'	=> __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
                 'public_key'		=> config('payments.spankpay.public_key')
             	];
		}




		public function get_order(string $order_id)
		{

		}



	}