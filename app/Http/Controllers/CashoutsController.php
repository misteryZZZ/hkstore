<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ Validator, DB };
use App\Models\{ Affiliate_Earning, Cashout };
use Illuminate\Validation\Rule;
use App\Libraries\PayPalCheckout;
use App\Events\NewMail;


class CashoutsController extends Controller
{

    public function cashouts(Request $request)
    {
	      $validator =  Validator::make($request->all(),
					            [
					              'orderby' => ['regex:/^(email|method|amount|updated_at)$/i', 'required_with:order'],
					              'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
					            ]);

	      if($validator->fails()) abort(404);

	      $base_uri = [];

	      if($request->orderby)
	      {
	        $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
	      }

	      $cashouts = Cashout::useIndex('primary')
	      					  ->selectRaw('cashouts.id, cashouts.amount, cashouts.method, cashouts.details, cashouts.updated_at, users.email')
	      					  ->join('users USE INDEX(primary)', 'users.id', '=', 'cashouts.user_id')
	      					  ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc')
	      					  ->paginate(15);

	      $items = $cashouts->getCollection();

        foreach($items as &$cashout)
        {
        	$cashout->details = $cashout->method == 'paypal_account' 
        											? [__('PayPal Email') => $cashout->details]
        											: json_decode($cashout->details, true);
        	$cashout->details = json_encode($cashout->details);
        }

        $cashouts->setCollection($items);

	      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

	      return View('back.affiliate.cashouts', compact('cashouts', 'items_order', 'base_uri'));
    }



    public function balances(Request $request)
    {
    		$validator =  Validator::make($request->all(),
					            [
					              'orderby' => ['regex:/^(email|method|earnings|updated_at)$/i', 'required_with:order'],
					              'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
					            ]);

	      if($validator->fails()) abort(404);

	      $base_uri = [];

	      if($request->orderby)
	      {
	        $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
	      }

	      $balances = Affiliate_Earning::useIndex('primary') 
	      					  ->selectRaw('GROUP_CONCAT(affiliate_earnings.id) as ids, IFNULL(SUM(affiliate_earnings.commission_value), 0) as earnings, 
	      					  	users.email, users.cashout_method as method, users.paypal_account, users.bank_account, affiliate_earnings.updated_at')
	      					  ->join('users USE INDEX(primary)', 'users.id', '=', 'affiliate_earnings.referrer_id')
	      					  ->orderBy($request->orderby ?? 'affiliate_earnings.id', $request->order ?? 'desc')
	      						->groupBy('affiliate_earnings.referrer_id')
	      						->where('affiliate_earnings.paid', '=', '0')
	      						->where('users.cashout_method', '!=', null)
	      					  ->paginate(15);
        
        $methods = [
        	'paypal_account' => 'paypal',
        	'bank_account' => 'bank_transfer'
        ];

        $items = $balances->getCollection();

        foreach($items as &$balance)
        {
        	$balance->bank_account = json_decode($balance->bank_account);
        	$balance->has_minimum = $balance->earnings >= (float)config("affiliate.minimum_cashout.{$methods[$balance->method]}", 0);
        	$balance->details = $balance->method == 'paypal_account' 
        											? [__('PayPal Email') => $balance->paypal_account]
        											: (array)$balance->bank_account;
        	$balance->details = json_encode($balance->details);
        }

        $balances->setCollection($items);

	      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

	      return View('back.affiliate.balances', compact('balances', 'items_order', 'base_uri'));
    }



    public function destroy_cashouts($ids)
    {
    	$ids = array_filter(explode(',', $ids));

    	Cashout::destroy($ids);

      return redirect()->route('affiliate.cashouts');
    }


    public function destroy_balances($ids)
    {
    	$ids = array_filter(explode(',', $ids));

    	Affiliate_Earning::destroy($ids);

      return redirect()->route('affiliate.balances');
    }


    public function mark_as_paid(Request $request)
    {
    	$ids = $request->input('ids', []);
    	$ids = is_array($ids) ? array_filter($ids) : abort(404);

    	$balance = Affiliate_Earning::useIndex('primary') 
    					  ->selectRaw('IFNULL(SUM(affiliate_earnings.commission_value), 0) as earnings, users.email, users.bank_account, users.id as user_id')
    					  ->join('users USE INDEX(primary)', 'users.id', '=', 'affiliate_earnings.referrer_id')
    						->where('affiliate_earnings.paid', '=', '0')
    						->whereIn('affiliate_earnings.id', $ids)
    						->first();

    	$bank_account = json_decode($balance->bank_account);

    	if(!$bank_account)
    	{
    		return response()->json(['status' => false, 'message' => __('The user has not yet entered his bank account details.')]);
    	}

    	DB::transaction(function() use($ids, $balance)
    	{
    		Affiliate_Earning::whereIn('affiliate_earnings.id', $ids)->update(['paid' => 1]);

				Cashout::insert([
					'earning_ids' 		=> implode(',', $ids),
					'user_id' 				=> $balance->user_id,
					'amount' 					=> format_amount($balance->earnings),
					'method' 					=> 'bank_account',
					'details' 				=> $balance->bank_account
				]);
    	});

    	$amount = price($balance->earnings, false, true, 2, 'code', false, config('payments.currency_code'));

			$text = __('Your :app_name affiliate earnings of :amount have been transferred to your bank account [:account_number]',
							['app_name' => config('app.name'), 'amount' => $amount, 'account_number' => $bank_account->account_number]);
			$email = $balance->email;

			$this->cashout_notif($text, $email);

			return response()->json(['status' => true, 'message' => __('Payment done successfully.')]);
    }



    public function transfer_to_paypal(Request $request)
    {
    	$ids = $request->input('ids', []);
    	$ids = is_array($ids) ? array_filter($ids) : abort(404);

			$balance = Affiliate_Earning::useIndex('primary') 
    					  ->selectRaw('IFNULL(SUM(affiliate_earnings.commission_value), 0) as earnings, users.email, users.paypal_account, users.id as user_id')
    					  ->join('users USE INDEX(primary)', 'users.id', '=', 'affiliate_earnings.referrer_id')
    						->where('affiliate_earnings.paid', '=', '0')
    						->whereIn('affiliate_earnings.id', $ids)
    						->first();

    	if(!$balance->paypal_account)
    	{
    		return response()->json(['status' => false, 'message' => __('The user has not yet entered his PayPal email address.')]);
    	}

    	if($balance && $balance->earnings > 0)
    	{
    		$paypal = new PayPalCheckout;
    		
    		$result = $paypal->payout(['paypal_account' => $balance->paypal_account, 'earnings' => $balance->earnings]);
    		
    		if($result['status'])
    		{
    			DB::transaction(function() use($ids, $balance, $result)
    			{
	    			Affiliate_Earning::whereIn('affiliate_earnings.id', $ids)->update(['paid' => 1]);

	    			Cashout::insert([
	    				'earning_ids' 		=> implode(',', $ids),
	    				'user_id' 				=> $balance->user_id,
	    				'amount' 					=> format_amount($balance->earnings),
	    				'method' 					=> 'paypal_account',
	    				'payout_batch_id' => $result['payout_batch_id'] ?? null,
	    				'details' 				=> $balance->paypal_account
	    			]);
    			});

    			$amount = price($balance->earnings, false, true, 2, 'code', false, config('payments.currency_code'));

    			$text = __('Your :app_name affiliate earnings of :amount have been transferred to your PayPal account [:email]',
    							['app_name' => config('app.name'), 'amount' => $amount, 'email' => $balance->paypal_account]);
    			$email = $balance->email;

    			$this->cashout_notif($text, $email);
    		}

    		return response()->json($result);
    	}
    }


    private function cashout_notif($text, $email)
    {
    		$mail_props = [
	        'data'   => ['text' => $text,
	                     'subject' => __('Your received a payment from :app_name.', ['app_name' => config('app.name')])],
	        'action' => 'send',
	        'view'   => 'mail.message',
	        'to'     => $email,
	        'subject' => __('Your received a payment from :app_name.', ['app_name' => config('app.name')])
	      ];

	      NewMail::dispatch($mail_props, config('mail.mailers.smtp.use_queue'));
    }
}
