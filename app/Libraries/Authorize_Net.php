<?php

namespace App\Libraries;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use net\authorize\api\constants\ANetEnvironment;

class Authorize_Net 
{
    public $name = 'authorize_net';
    public $return_url;
    public $cancel_url;
    public $supported_currencies = ["USD", "CAD", "CHF", "DKK", "EUR", "GBP", "NOK", "PLN", "SEK", "AUD", "NZD"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;


		public function __construct()
		{
			$this->return_url = route('home.checkout.order_completed');
			$this->cancel_url = config('checkout_cancel_url');

			if(!config('payments.authorize_net.enabled'))
			{
				return response()->json(['user_message' => __(':payment_proc is not enabled', ['payment_proc' =>  'Authorize_Net'])]);
			}
            
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

			$items = [];

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

      $items[] = [
      	'item_name_1' => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
      	'amount_1' 	  => $unit_amount,
      	'quantity_1' 	  => 1
      ];


      if($vat = config('payments.vat', 0))
      {
      	$tax = ($unit_amount * $vat) / 100;
      	$value = format_amount($tax, false, $this->decimals);

      	$items[] = [
	      	'item_name_2' => __('Tax'),
	      	'amount_2' 	  => $value,
	      	'quantity_2' 	  => 1
	      ];

	      $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

	      $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.authorize_net.fee', 0))
      {
      	$value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

      	$items[] = [
	      	'item_name_3' => __('Fee'),
	      	'amount_3' 	  => $value,
	      	'quantity_3' 	  => 1
	      ];

	      $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

	      $total_amount += format_amount($value, false, $this->decimals);
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      $total_amount = format_amount($total_amount, false, $this->decimals);

      $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
      $merchantAuthentication->setName(config('payments.authorize_net.api_login_id'));
      $merchantAuthentication->setTransactionKey(config('payments.authorize_net.transaction_key'));

      $api_url = config('payments.authorize_net.mode') == 'sandbox' ? ANetEnvironment::SANDBOX : ANetEnvironment::PRODUCTION;

      // Set the transaction's refId
      $refId = 'ref' . generate_transaction_ref();

      $OpaqueDataType = new AnetAPI\OpaqueDataType();
      $OpaqueDataType->setDataDescriptor($dataDescriptor);
      $OpaqueDataType->setDataValue($dataValue);

      $paymentOne = new AnetAPI\PaymentType();

      $paymentOne->setOpaqueData($OpaqueDataType);
      

      $transactionRequestType = new AnetAPI\TransactionRequestType();
      $transactionRequestType->setTransactionType("authCaptureTransaction"); 
      
      $lineItem = new AnetAPI\LineItemType();
      $lineItem->setItemId(time());
      $lineItem->setName(__('Purchase_from_:app_name', ['app_name' => mb_ucfirst(config('app.name'))]));
      $lineItem->setQuantity(1);
      $lineItem->setUnitPrice($total_amount);
      $lineItem->setTotalAmount($total_amount);

      $transactionRequestType->addToLineItems($lineItem);

      $transactionRequestType->setAmount($total_amount);
      $transactionRequestType->setCurrencyCode($this->currency_code);
      $transactionRequestType->setPayment($paymentOne);


      $request = new AnetAPI\CreateTransactionRequest();
      $request->setMerchantAuthentication($merchantAuthentication);
      $request->setRefId($refId);
      $request->setTransactionRequest($transactionRequestType);

      $controller = new AnetController\CreateTransactionController($request);

      return $controller->executeWithApiResponse($api_url)->getTransactionResponse();
		}



}