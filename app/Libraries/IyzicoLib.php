<?php

	namespace App\Libraries;

  use Illuminate\Http\Request;
	use Illuminate\Support\Facades\{DB, Cache, Session};
  use Iyzipay;
  use Iyzipay\Model\{ PaymentGroup, Currency, Locale, Buyer, Address, BasketItem, BasketItemType, CheckoutFormInitialize };

	class IyzicoLib
	{
    public $name = 'iyzico';
    public $callback_url;
    public $cancel_url;
    public $supported_currencies = ["TRY", "USD", "EUR", "GBP", "RUB", "CHF", "NOK"];
    public $currency_code;
    public $exchange_rate = 1;
    public $decimals;
    public $details  = [];
    public $error_msg;


    public function __construct()
    {
      $this->callback_url = route('home.checkout.order_completed');
      $this->cancel_url = config('checkout_cancel_url');

      exists_or_abort(config('payments.iyzico.enabled'), __(':payment_proc is not enabled', ['payment_proc' =>  'Iyzico']));

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



    public function getOptions()
    {
      $options = new Iyzipay\Options();

      $options->setApiKey(config('payments.iyzico.client_id'));
      $options->setSecretKey(config('payments.iyzico.secret_id'));
      
      if(config('payments.iyzico.mode') === 'sandbox')
      {
        $options->setBaseUrl("https://sandbox-api.iyzipay.com");
      }
      else
      {
        $options->setBaseUrl("https://api.iyzipay.com");
      }

      return $options;
    }




    public function init_payment(array $params, object $buyerInf)
    {
      extract($params);

      $basketItems = [];

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


      $basketItems[] =  $this->basketItem([
                            'id'        => 'PURCHASE',
                            'name'      => __('Purchase from :app_name', ['app_name' => mb_ucfirst(config('app.name'))]),
                            'category1' => 'Default',
                            'itemType'  => BasketItemType::VIRTUAL,
                            'price'     => $total_amount,
                          ]);



      if($vat = config('payments.vat', 0))
      {
        $tax = ($total_amount * $vat) / 100;
        $value = format_amount($tax, false, $this->decimals);

        $basketItems[] =  $this->basketItem([
                            'id'        => 'TAX',
                            'name'      => __('VAT :percent%', ['percent' => $vat]),
                            'category1' => __('Tax'),
                            'itemType'  => BasketItemType::VIRTUAL,
                            'price'     => $value,
                          ]);

        $this->details['items']['tax'] = ['name' => __('Tax'), 'value' => $value];

        $total_amount += format_amount($tax ?? 0, false, $this->decimals);
      }


      if($handling = config('payments.iyzico.fee', 0))
      {
        $value = format_amount($handling * $this->exchange_rate, false, $this->decimals);

        $basketItems[] =  $this->basketItem([
                            'id'        => 'FEE',
                            'name'      => __('Handling fee'),
                            'category1' => __('Fee'),
                            'itemType'  => BasketItemType::VIRTUAL,
                            'price'     => $value,
                          ]);

        $this->details['items']['fee'] = ['name' => __('Handling fee'), 'value' => $value];

        $total_amount += $value;
      }


      $this->details['total_amount'] = format_amount(array_sum(array_column($this->details['items'], 'value')), false, $this->decimals);

      $total_amount = format_amount($total_amount, false, $this->decimals);


      $request = new Iyzipay\Request\CreateCheckoutFormInitializeRequest();
      $request->setLocale(Locale::EN);
      $request->setPrice($total_amount);
      $request->setPaidPrice($total_amount);
      $request->setCurrency($this->currency_code);
      $request->setPaymentGroup(PaymentGroup::PRODUCT);
      $request->setCallbackUrl($this->callback_url);
      $request->setEnabledInstallments([2, 3, 6, 9]);

      $buyer = new Buyer();
      $buyer->setId($buyerInf->email);
      $buyer->setName($buyerInf->firstname);
      $buyer->setSurname($buyerInf->lastname);
      $buyer->setEmail($buyerInf->email);
      $buyer->setIdentityNumber($buyerInf->id_number);
      $buyer->setRegistrationAddress($buyerInf->address);
      $buyer->setIp($buyerInf->ip_address ?? request()->ip());
      $buyer->setCity($buyerInf->city);
      $buyer->setCountry($buyerInf->country);

      $request->setBuyer($buyer);

      $billingAddress = new Address();
      $billingAddress->setContactName("{$buyerInf->firstname} {$buyerInf->lastname}");
      $billingAddress->setCity($buyerInf->city);
      $billingAddress->setAddress($buyerInf->address);
      $billingAddress->setCountry($buyerInf->country);

      $request->setBillingAddress($billingAddress);

      $request->setBasketItems($basketItems);

      $form = CheckoutFormInitialize::create($request, $this->getOptions());

      if($form->getErrorCode())
      {
        $this->error_msg = ['user_message' => $form->getErrorMessage()];
        
        return;
      }

      return $form;
    }





    public function validate_payment(string $token)
    {
      $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();

      $request->setLocale(Locale::EN);

      $request->setToken($token);

      $checkoutForm = Iyzipay\Model\CheckoutForm::retrieve($request, $this->getOptions());

      return strtoupper($checkoutForm->getPaymentStatus()) == 'SUCCESS';
    }


    

    public function basketItem($attributes)
    {
      extract($attributes);

      $basketItem = new BasketItem();

      $basketItem->setId($id ?? null);
      $basketItem->setPrice($price ?? null);
      $basketItem->setName($name ?? null);
      $basketItem->setCategory1($category1 ?? null);
      $basketItem->setCategory2($category2 ?? null);
      $basketItem->setItemType($itemType ?? null);
      $basketItem->setSubMerchantKey($merchantKey ?? null);
      $basketItem->setSubMerchantPrice($MerchantPrice ?? null);

      return $basketItem;
    }



    public function getPaymentRequest($paymentId)
    {
      $request = new \Iyzipay\Request\RetrievePaymentRequest();

      $request->setLocale(Locale::EN);
      $request->setPaymentId($paymentId);

      return \Iyzipay\Model\Payment::retrieve($request, $this->getOptions());
    }

  }

