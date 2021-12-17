<?php

	namespace App\Libraries;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{ DB, Cache, Session, Auth };
	use CoinGate\{ Merchant\Order, CoinGate as CoinGateBase };


	class CoinGate
	{
		public $name = 'coingate';
		public $return_url;
		public $cancel_url;
		public $supported_currencies = ["BTC", "EUR", "GBP", "USD"];
		public $currency_code;
		public $exchange_rate = 1;
		public $decimals;
		public $details = [];
		public $error_msg;


		public function __construct()
		{
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

			exists_or_abort(config('payments.coingate.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'CoinGate']));

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

	    CoinGateBase::config(array(
				  'environment'               => config('payments.coingate.mode'), // sandbox OR live
				  'auth_token'                => config('payments.coingate.auth_token'),
				  'curlopt_ssl_verifypeer'    => false
			));
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
				$gross_amount = $custom_amount;

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


      if($handling = config('payments.coingate.fee', 0))
      {
      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

	      $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      session(['transaction_details' => $this->details]);

      $total_amount = format_amount($total_amount, false, $this->decimals);


			$payload = [
                   'order_id'          => generate_transaction_ref(),
                   'price_amount'      => $total_amount,
                   'price_currency'    => $this->currency_code,
                   'receive_currency'  => config('payments.coingate.receive_currency'),
                   'cancel_url'        => $this->cancel_url,
                   'success_url'       => $this->return_url,
                   'callback_url'      => route('home.checkout.webhook'),
                   'title'             => 'Order',
                   'description'       => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))])
               	];

			return Order::create($payload);
		}




		public function get_order(string $order_id)
		{
			return Order::find($order_id);
		}



	}