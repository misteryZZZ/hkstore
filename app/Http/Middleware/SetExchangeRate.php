<?php

namespace App\Http\Middleware;

use Closure;


class SetExchangeRate
{
    /**
     * Refresh exchange rates after X minutes
    **/
    public $refresh_after = 120;


    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try
        {
          $exchange_rate = 1;

          if(config('payments.currency_by_country'))
          {
            session(['currency' => session('currency') ?? country_currency()]);
          }

          if($currency = strtoupper(session('currency')))
          {
            if(config("payments.currencies.{$currency}.exchange_rate"))
            {
              $exchange_rate = config("payments.currencies.{$currency}.exchange_rate");
            }
            elseif(config('payments.currency_exchange_api'))
            {
              $base = strtoupper(config('payments.currency_code'));

              $client = new \GuzzleHttp\Client();

              if(config('payments.currency_exchange_api') === 'api.exchangeratesapi.io')
              {
                if(cache("exchangeratesapi_exchange_rate_{$base}_{$currency}"))
                {
                  $exchange_rate = cache("exchangeratesapi_exchange_rate_{$base}_{$currency}");
                  //config(['payments.exchange_rate' => $exchange_rate]);
                }
                else
                {
                  if($diff = array_diff([$base, $currency], config('payments.api_supported_currencies.exchangeratesapi_io')))
                  {
                    session(["currency" => strtoupper(config('payments.currency_code'))]);

                    \Session::flash('user_message', __("The selected currency (:currency) is not supported by :api_host API.", ['currency' => implode(',', $diff), 'api_host' => 'exchangeratesapi.io']));

                    return $next($request);
                  }
                  
                  if(!$access_key = config('payments.exchangeratesapi_io_key'))
                  {
                    session(['currency' => strtoupper(config('payments.currency_code'))]);
      
                    return back()->with(['user_message' => __("The access_key is missing for :api_host.", ['api_host' => 'exchangeratesapi.io'])]);
                  }
    
                  $response = $client->get("https://api.exchangeratesapi.io/latest?access_key={$access_key}&base={$base}&symbols={$currency}");

                  if($response->getStatusCode() === 200)
                  {
                    $json = json_decode($response->getBody());

                    $exchange_rate = $json->rates->$currency;

                    \Cache::put("exchangeratesapi_exchange_rate_{$base}_{$currency}", $json->rates->$currency, now()->addMinutes($this->refresh_after));
                  }
                }
              }
              elseif(config('payments.currency_exchange_api') === 'api.currencyscoop.com')
              {
                if($exchange_rates = cache("currencyscoop_exchange_rates_{$base}"))
                {
                  if(isset($exchange_rates->$currency))
                  {
                    $exchange_rate = $exchange_rates->$currency;
                  }
                }
                else
                {
                  if(!$api_key = config('payments.currencyscoop_api_key'))
                  {
                      \Session::flash('user_message', __("The API key for api.currencyscoop.com is missing."));

                      return $next($request);
                  }
                  
                  $response = $client->get("https://api.currencyscoop.com/v1/latest?api_key={$api_key}&base={$base}");

                  if($response->getStatusCode() === 200)
                  {
                    $body = $response->getBody();

                    $json = json_decode($body);

                    if($json->meta->code === 200)
                    {
                      if(!isset($json->response->rates->$currency))
                      {
                        \Session::flash('user_message', __("Currency :currency not supported by currencyscoop API.", ['currency' => $currency]));

                        return $next($request);
                      }

                      $exchange_rate = $json->response->rates->$currency;

                      \Cache::put("currencyscoop_exchange_rates_{$base}", $json->response->rates, now()->addMinutes($this->refresh_after));
                    }
                  }
                }
              }
              elseif(config('payments.currency_exchange_api') === 'api.exchangerate.host')
              {
                if($exchange_rates = cache("exchangerate_exchange_rates_{$base}"))
                {
                  if(isset($exchange_rates->$currency))
                  {
                    $exchange_rate = $exchange_rates->$currency;
                  }
                }
                else
                {
                  if($diff = array_diff([$base, $currency], config('payments.api_supported_currencies.exchangerate_host')))
                  {
                    session(["currency" => strtoupper(config('payments.currency_code'))]);

                    \Session::flash('user_message', __("The selected currency (:currency) is not supported by :api_host API.", ['currency' => implode(',', $diff), 'api_host' => 'exchangeratesapi.io']));

                    return $next($request);
                  }

                  $response = $client->get("https://api.exchangerate.host/latest?base={$base}");

                  if($response->getStatusCode() === 200)
                  {
                    $body = $response->getBody();

                    $json = json_decode($body);

                    $exchange_rate = $json->rates->$currency;

                    \Cache::put("exchangerate_exchange_rates_{$base}", $json->rates, now()->addMinutes($this->refresh_after));
                  }
                }
              }
              elseif(config('payments.currency_exchange_api') === 'api.coingate.com')
              {                        
                if($exchange_rate = cache("coingateapi_exchange_rate_{$base}_{$currency}"))
                {
                  config(['payments.exchange_rate' => $exchange_rate]);
                }
                else
                {
                  if($diff = array_diff([$base, $currency], config('payments.api_supported_currencies.coingate_com')))
                  {
                    session(["currency" => strtoupper(config('payments.currency_code'))]);

                    \Session::flash('user_message', __("The selected currency (:currency) is not supported by :api_host API.", ['currency' => implode(',', $diff), 'api_host' => 'coingate.com']));

                    return $next($request);
                  }

                  $response = $client->get("https://api.coingate.com/v2/rates/merchant/{$base}/{$currency}");

                  if($response->getStatusCode() === 200)
                  {
                    $rate = $response->getBody();
                    $rate = (string)$rate;

                    $exchange_rate = (float)$rate;

                    \Cache::put("coingateapi_exchange_rate_{$base}_{$currency}", $rate, now()->addMinutes($this->refresh_after));
                  }
                }
              }
            }
          }

          config(['payments.exchange_rate' => $exchange_rate]);

          $fees = config('fees', []);

          foreach($fees as &$fee)
          {
              $fee = $fee * config('payments.exchange_rate');
          }

          config(['fees' => $fees]);   
        } 
        catch(\Exception $e)
        {

        }

        return $next($request);
    }
}
