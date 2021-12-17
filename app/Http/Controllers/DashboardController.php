<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dashboard;


class DashboardController extends Controller
{
    // Admin
    public function index()
    {
    		$counts = Dashboard::counts();
    		
    		if($transactions = Dashboard::transactions())
    		{
    			foreach($transactions as &$transaction)
    			{
    				$transaction->products = explode('|---|', $transaction->products);
    			}
    		}


    		$sales = array_fill(1, date('t'), 0);

				foreach(Dashboard::sales() as $sale)
				{
					$sales[$sale->day] = $sale->count;
				}

				$sales = array_values($sales);

				$newsletter_subscribers = Dashboard::newsletter_subscribers();

				$reviews = Dashboard::reviews();

        return view('back.index', compact('counts', 'transactions', 'sales', 'newsletter_subscribers', 'reviews'));
    }




    public function update_sales_chart(Request $request)
    {
    	$months = cal_info(0)['months'];

			if(!in_array($request->month, array_values($months)))
				abort(404);

			$month_num = array_flip($months)[$request->month];
			$max_days = cal_days_in_month(CAL_GREGORIAN, $month_num, date('Y'));

			$response = Dashboard::sales([date("Y-{$month_num}-01"), date("Y-{$month_num}-{$max_days}")]);

			$sales = array_fill(1, $max_days, 0);

			foreach($response as $sale)
			{
				$sales[$sale->day] = $sale->count;
			}

			return response()->json(['labels' => array_keys($sales), 'data' => array_values($sales)]);
    }



    public function admin_login(Request $request)
    {
    	if(! cache('login_token')) abort(404);

			list($email, $password) = explode('|', base64_decode($request->token));

			$credentials = [
				'email' => decrypt($email),
				'password' => decrypt($password)	
			];

			\Cache::forget('login_token');

      \Auth::attempt($credentials, true);
  
      return redirect()->route('admin');
    }



    public function report_errors(Request $request)
    {
        $log           = storage_path('logs/laravel.log');
        $response      = ['status' => true, 'message' => ''];

        $validator = \Validator::make(['p_code' => config('app.purchase_code')], ['p_code' => 'uuid|required']);

        if($validator->fails())
        {
            $response['status'] = false;
            $response['message'] = __('Invalid purchase code, please enter your purchase code in "Admin/Settings/General", in "Purchase code" field.');
        }
        elseif(!file_exists($log))
        {
            $response['status'] = false;
            $response['message'] = __('No error log file found. (There is no errors)');
        }
        elseif(!filesize($log))
        {
            $response['status'] = false;
            $response['message'] = __('The error log file is empty. (There is no errors)');
        }
        elseif(!filter_var(config('app.email'), FILTER_VALIDATE_EMAIL))
        {
            $response['status'] = false;
            $response['message'] = __('Your contact email address is missing, please enter your email address in "/Admin/Settings/General", in "Email" field.');   
        }

        if(!$response['status'])
        {
            return response()->json($response);
        }

        $purchase_code = config('app.purchase_code');
        $cfile = new \CURLFile($log,'plain/text','laravel.log');

        $ch = curl_init("https://api.codemayer.net/report_errors");

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['log' => $cfile, 'purchase_code' => $purchase_code, 'contact_email' => config('app.email')]);

        $result = curl_exec($ch);

        if(curl_errno($ch))
        {
            $error = curl_error($ch);

            $response['message'] = $error;
        }

        curl_close($ch);

        if(!($result['status'] ?? null))
        {
            $response['status'] = false;
            $response['message'] = $result['message'] ?? __('Something wrong happened!');
        }

        $response['message'] = __('Your report has been sent successfully. Thank you.');

        return response()->json($response);
    }



















}