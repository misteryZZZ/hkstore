<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{ User_Subscription, Transaction };
use Illuminate\Support\Facades\{ Validator, DB };
use App\Http\Controllers\PaymentLinksController;


class UserSubscriptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(username|ends_at|starts_at|downloads|name|expired|remaining_days)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($request->orderby)
      {
        $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
      }

      $subscriptions = User_Subscription::useIndex('primary')
                        ->selectRaw("user_subscription.id, users.name AS username, user_subscription.ends_at, user_subscription.starts_at, user_subscription.downloads, subscriptions.name, 
                          ((user_subscription.ends_at IS NOT NULL AND CURRENT_TIMESTAMP > user_subscription.ends_at) OR
                            (subscriptions.limit_downloads > 0 AND user_subscription.downloads >= subscriptions.limit_downloads) OR 
                            (subscriptions.limit_downloads_per_day > 0 AND user_subscription.daily_downloads >= subscriptions.limit_downloads_per_day AND user_subscription.daily_downloads_date = CURDATE())) AS expired,
                          if(DATEDIFF(user_subscription.ends_at, CURRENT_TIMESTAMP) > 0, DATEDIFF(user_subscription.ends_at, CURRENT_TIMESTAMP), 0) as remaining_days,
                          transactions.is_subscription, transactions.refunded, transactions.status")
                        ->join('transactions', 'transactions.id', '=', 'user_subscription.transaction_id')
                        ->join('users', 'users.id', '=', 'user_subscription.user_id')
                        ->join('subscriptions', 'subscriptions.id', '=', 'user_subscription.subscription_id')
                        ->orderBy($request->orderby ?? 'user_subscription.id', $request->order ?? 'desc')
                        ->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.subscriptions', ['title' => __('Subscriptions'),
                                         'subscriptions' => $subscriptions,
                                         'items_order' => $items_order,
                                         'base_uri' => $base_uri]);
    }



    public function create_send_renewal_payment_link(Request $request)
    {
      $subscriptions = User_Subscription::selectRaw("user_subscription.id, transactions.processor, users.email, users.id as user_id,
                       user_subscription.subscription_id, subscriptions.name as subscription_name, subscriptions.price as subscription_price, transactions.exchange_rate, SUBSTR(transactions.details, LOCATE('\",\"exchange_rate\"', transactions.details)-3, 3) as currency, transactions.custom_amount, coupons.code as coupon_code")
                       ->join('transactions USE INDEX(primary)', 'transactions.id', '=', 'user_subscription.transaction_id')
                       ->join('subscriptions USE INDEX(primary)', 'subscriptions.id', '=', 'user_subscription.subscription_id')
                       ->join('users USE INDEX(primary)', 'users.id', '=', 'user_subscription.user_id')
                       ->leftJoin('coupons USE INDEX(primary)', 'coupons.id', '=', 'transactions.coupon_id')
                       ->whereIn('user_subscription.id', $request->ids ?? [])
                       ->get();

      $payment_links = [];
      $errors = [];
      $success = [];

      foreach($subscriptions as $user_subscription)
      {
        $request_params = [
          'payment_service' => $user_subscription->processor,
          'coupon' => null,
          'subscription' => ['id' => $user_subscription->subscription_id, 'price' => $user_subscription->subscription_price],
          'custom_amount' => $request->apply_custom_amount ? $user_subscription->custom_amount : null,
          'is_subscription' => '1',
          'cart' => [(object)[
            'id'        => $user_subscription->subscription_id,
            'quantity'  => 1,
            'name'      => $user_subscription->subscription_name,
            'category'  => __('Subscription'),
            'price'     => $user_subscription->subscription_price,
          ]],
          'currency'      => $user_subscription->currency,
          'exchange_rate' => $request->apply_old_exchange_rate ? $user_subscription->exchange_rate : exchange_rate($user_subscription->currency),
          'coupon_code'   => $request->apply_old_coupon ? $user_subscription->coupon_code : null,
          'user_id'       => $user_subscription->user_id,
          'from_user_subscriptions' => 1
        ];

        $myRequest = new Request();
        $myRequest->setMethod('POST');

        $myRequest->request->add($request_params);

        $paymentLinksController = new PaymentLinksController;

        $response = $paymentLinksController->store($myRequest);

        if(isset($response['error']))
        {
          $errors[] = [$user_subscription->email => $response['error']];
        }
        elseif(isset($response['success']))
        {          
          extract($response['success']);

          $myRequest2 = new Request();
          $myRequest2->setMethod('POST');

          $myRequest2_params = [
            'id' => $response['success']['id'],
            'text' => __('Your subscription is about to expire, please renew your subscription to avoid any interruption.'),
            'subject' => __('Subscription renewal - Payment request from :app_name', ['app_name' => config('app.name')]),
            'action' => 'send',
            'from_user_subscriptions' => 1
          ];

          $myRequest2->request->add($myRequest2_params);

          $paymentLinksController = new PaymentLinksController;

          $response = $paymentLinksController->send($myRequest2);

          if(isset($response['success']))
          {
            $success[] = $response['success'];
          }
        }

        return json(compact('errors', 'success'));
      }
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(String $ids)
    {
      $ids = array_filter(explode(',', $ids));
      $transactions_ids = User_Subscription::select('transaction_id')->whereIn('id', $ids)->get()->pluck('transaction_id')->toArray();

      $response = DB::transaction(function() use($ids, $transactions_ids)
      {
        User_Subscription::destroy($ids);
        Transaction::destroy($transactions_ids);
      });

      return back()->with(['message' => $response ? __('Done.') : __('Something wrong happened.')]);
    }
}
