<?php

	namespace App\Libraries;

	use Illuminate\Support\Facades\{ DB, Cache, Session, Auth };
	use Paymentwall_Config;
	use Paymentwall_Product;
	use Paymentwall_Widget;
	use Paymentwall_Base;
	use Paymentwall_Pingback;


	class Paymentwall 
	{
		public $name = 'paymentwall';
		public $status_url;
		public $return_url;
		public $cancel_url;
		public $currency_code;
		public $exchange_rate = 1;
		public $default_currency;
		public $decimals;
		public $details  = [];
		public $error_msg;



		public function __construct()
		{
			$this->status_url = route('home.checkout.webhook');
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

      exists_or_abort(config('payments.paymentwall.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Paymentwall']));

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

      
      Paymentwall_Config::getInstance()->set([
			  'api_type' 		=> Paymentwall_Config::API_GOODS,
			  'public_key'  => config('payments.paymentwall.project_key'),
			  'private_key' => config('payments.paymentwall.secret_key')
			]);


      $total_amount = format_amount($total_amount, false, $this->decimals);
      $order_id 		= generate_transaction_ref();
      $user_id      = Auth::check() ? Auth::id() : uuid6();
      $extra_params = [
      	'order_id'  		=> $order_id,
      	'success_url' 	=> $this->return_url,
      	'failed_url' 		=> $this->return_url,
      	'evaluation' 		=> config('payments.paymentwall.mode') === 'live' ? '0' : '1'
      ];

      $product      = [        
				new Paymentwall_Product(
					__('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
					$total_amount,
					$this->currency_code,
					__('Order :num', ['num' => generate_transaction_ref()]),
					Paymentwall_Product::TYPE_FIXED
				)
		  ];

			$widget = new Paymentwall_Widget($user_id, 'pw', $product, $extra_params);

			return $widget->getUrl();
		}



		public static function validate_webhook($request)
		{
			Paymentwall_Base::setApiType(Paymentwall_Base::API_GOODS);
			Paymentwall_Base::setAppKey(config('payments.paymentwall.project_key')); 
			Paymentwall_Base::setSecretKey(config('payments.paymentwall.secret_key'));

			$pingback = new Paymentwall_Pingback($request->query(), $request->server('REMOTE_ADDR'));

			if($pingback->validate()) 
			{
				if(!$pingback->isCancelable() && !$pingback->isUnderReview())
				{
					return $pingback->getReferenceId();
				}
			}

			return false;
		}
	}