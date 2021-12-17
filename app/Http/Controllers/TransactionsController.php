<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{ Transaction, Product, User, Subscription, User_Subscription, License, Key };
use Illuminate\Support\Facades\{ Validator, DB };
use Iyzipay;


class TransactionsController extends Controller
{
    /**
     * Display a listing of transactions.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(product|buyer|amount|processor|refunded|refund|status|confirmed|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      !$validator->fails() || abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $transactions = Transaction::useIndex('search')
                        ->select('transactions.id', 'transactions.reference_id', 'transactions.transaction_id', 
                          'transactions.cs_token', 'users.email as buyer', 'transactions.amount', 'transactions.refunded', 'transactions.refund', 'transactions.updated_at', 'transactions.processor', 'transactions.status')
                        ->leftjoin('users use index(primary)', 'transactions.user_id', '=', 'users.id')
                        ->where('users.email', 'like', "%{$keywords}%")
                        ->orWhere('reference_id', 'like', "%{$keywords}%")
                        ->orWhere('processor', 'like', "%{$keywords}%")
                        ->orWhere('order_id', 'like', "%{$keywords}%")
                        ->orWhere('transaction_id', 'like', "%{$keywords}%")
                        ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $index = ($request->orderby === 'buyer') ? 'user_id' : ($request->orderby ?? 'primary');

        $transactions = Transaction::useIndex($index)
                                    ->select('transactions.id', 'transactions.reference_id', 'transactions.transaction_id', 
                                      'transactions.cs_token', 'users.email as buyer', 'transactions.amount', 'transactions.refunded', 'transactions.refund', 'transactions.updated_at', 'transactions.processor', 
                                      'transactions.status')
                                    ->leftjoin('users use index(primary)', 'transactions.user_id', '=', 'users.id')
                                    ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $transactions = $transactions->paginate(15);

      foreach($transactions as &$transaction)
      {
        if($transaction->processor === 'stripe')
        {
          $transaction->transaction_id = $transaction->cs_token;
        }
      }

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.transactions.index', ['transactions'  => $transactions,
                                              'items_order'   => $items_order,
                                              'base_uri'      => $base_uri]);
    }




    /**
     * Display the specified transaction details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {      
      $transaction = Transaction::where('transactions.id', $id)->first() ?? abort(404);

      if($transaction->details)
      {
        $transaction->details = json_decode($transaction->details);

        $licenses = object_property($transaction->details->items, 'license', true);

        $licenses = License::select('id', 'name')->whereIn('id', $licenses)->get()->pluck('name', 'id')->toArray();

        foreach($transaction->details->items ?? [] as &$item)
        {
          if($item->license ?? null)
          {
            $item->license = $licenses[$item->license] ?? null;
          }
        }
      }
      else
      {
        $products_ids  = array_filter(explode(',', str_replace("'", '', $transaction->products_ids)));

        if($transaction->processor != 'manual')
        {
            $products = Product::useIndex('primary')->select('name', 'price as value')->whereIn('id', $products_ids)->get()->toArray();   
        }
        else
        {
            $productsController = new \App\Http\Controllers\ProductsController();
        
            $request = request();
            $request->merge(['limit' => 9999999, 'whereIn' => ['products.id', $products_ids]]);
            
            $products = json_decode($productsController->api($request)->getContent(), true)['products'] ?? [];
            
            foreach($products as &$product)
            {
                $product['value'] = $product['price'];
            }    
        }
        
        $currency      = config('payments.currency_code');

        $transaction->details = [
          'items' => $products,
          'currency' => $currency,
          'exchange_rate' => 1,
          'decimals' => config("payments.{$currency}.decimals", 2),
          'discount' => $transaction->discount
        ];

        $transaction->details = json_decode(json_encode($transaction->details, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
      }

      $buyer = $transaction->user_id ? User::useIndex('primary')->where('id', $transaction->user_id)->first() : null;

      return view('back.transactions.show', compact('transaction', 'buyer'));
    }



    public function create(Request $request)
    {
      $productsController = new \App\Http\Controllers\ProductsController;
      $request->merge(['limit' => 9999999]);
      
      $products = $productsController->api($request)->original['products'] ?? [];

      return view('back.transactions.create', compact('products'));
    }



    public function store(Request $request)
    {
        $request->validate([
          'email'        => 'email|required',
          'products_ids' => ['required', 'regex:/^([\d,?]+)$/i'],
          'amount'       => 'required|numeric|gte:0',
          'discount'     => 'nullable|numeric|gte:0',
          'is_subscription' => 'in:true,false|required'
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user)
        {
          return back()->with(['user_not_found' => __("User doesn't exists.")]);
        }

        $products_ids = array_filter(explode(',', $request->post('products_ids')));
        $items = array_map('wrap_str', $products_ids);

        $transaction = new Transaction;

        $transaction->transaction_id  = "OFFPAY{$user->id}-".time();
        $transaction->user_id         = $user->id;
        $transaction->products_ids    = implode(',', $items);
        $transaction->processor       = 'manual';
        $transaction->discount        = $request->post('discount', null);
        $transaction->amount          = $request->post('amount', null);
        $transaction->items_count     = count($items);
        $transaction->is_subscription = $request->post('is_subscription') === 'true' ? '1' : '0';
        $transaction->status          = 'paid';
        $transaction->confirmed       = 1;

        \DB::transaction(function() use($transaction, $request, $user, $products_ids)
        {
          $transaction->save();
          
          if($transaction->is_subscription === '1')
          {
            $subscription = Subscription::find($request->post('products_ids')) ?? abort(404);

            User_Subscription::insert([
              'user_id'         => $user->id,
              'subscription_id' => $subscription->id,
              'transaction_id'  => $transaction->id,
              'ends_at'         => is_numeric($subscription->days) && $subscription->days > 0
                                   ? date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . " + {$subscription->days} days"))
                                   : null,
              'daily_downloads' => 0,
              'daily_downloads_date' => $subscription->limit_downloads_per_day ? date('Y-m-d') : null
            ]);
          }
          else
          {
            $licensed_products_ids = Product::useIndex('primary', 'enable_license')->select('id')
                                 ->whereIn('id', $products_ids)->where('enable_license', 1)
                                 ->get()->pluck('id')->toArray();

            if($licensed_products_ids)
            {
              $licenses = [];

              foreach(array_intersect($products_ids, $licensed_products_ids) as $licensed_product_id)
              {
                $licenses[$licensed_product_id] = uuid6();
              }

              $transaction->licenses = json_encode($licenses);
            }

            $CheckoutController = new \App\Http\Controllers\CheckoutController;
            $CheckoutController->update_keys($products_ids, $transaction);
          }
        });

        return redirect()->route('transactions')->with('response', __('Transaction created successfully'));
    }


    public function edit($id)
    {
      $transaction = Transaction::select('transactions.*', 'users.email')
                     ->where(['transactions.id' => $id, 'transactions.processor' => 'manual'])
                     ->join('users', 'users.id', '=', 'transactions.user_id')
                     ->first();
      
      if(!$transaction)
        return redirect()->route('transactions');
      
      $request = request();
      $productsController = new \App\Http\Controllers\ProductsController;
      $request->merge(['limit' => 9999999]);
      
      $products = $productsController->api($request)->original['products'] ?? [];
      
      return view('back.transactions.edit', compact('transaction', 'products'));
    }


    /**
     * Proceed to the refund of the specified transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
      $transaction = Transaction::where(['transactions.id' => $id, 'transactions.processor' => 'manual'])->first() ?? abort(404);

      $request->validate([
          'email'        => 'email|required',
          'products_ids' => ['required', 'regex:/^([\d,?]+)$/i'],
          'amount'       => 'required|numeric|gte:0',
          'discount'     => 'nullable|numeric|gte:0',
          'is_subscription' => 'in:true,false|required'
        ]);

      $user = User::where('email', $request->email)->first();

      if(!$user)
        return back()->with(['user_not_found' => __("User doesn't exists.")]);

      $items = array_map('wrap_str', array_filter(explode(',', $request->post('products_ids'))));

      $transaction->user_id        = $user->id;
      $transaction->products_ids   = implode(',', $items);
      $transaction->discount       = $request->post('discount', null);
      $transaction->amount         = $request->post('amount', null);
      $transaction->items_count    = count($items);
      $transaction->refunded       = $request->post('refunded');
      $transaction->is_subscription = $request->post('is_subscription') ? '1' : '0';

      $transaction->save();

      return redirect()->route('transactions')->with('response', __('Changes saved successfully'));
    }



    public function mark_as_refunded(Request $request)
    {
      $id = $request->id ?? abort(404);

      \DB::update('UPDATE transactions USE INDEX(primary) SET refunded = 1 WHERE id = ? AND processor = ?', [$id, $request->processor]);

      return back()->with(['response' => __('The selected transaction has been marked as refunded.')]);
    }



    /**
     * Remove the specified transaction.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      $ids = array_filter(explode(',', $ids));

      Transaction::destroy($ids);

      User_Subscription::whereIn('user_subscription.id', $ids)->delete();

      return redirect()->route('transactions');
    }



    public function update_prop(Request $request)
    {
      $request->validate(['id' => 'required|numeric', 'prop' => 'required|in:status,refunded']);

      $new_value = $request->prop === 'status' ? "IF(status = 'paid', 'pending', 'paid')" : "IF($request->prop = 1, 0, 1)";

      $res = \DB::update("UPDATE transactions USE INDEX(primary) SET {$request->prop} = {$new_value} WHERE id = {$request->id}");

      $transaction = Transaction::find($request->id);

      if($transaction->processor == 'offline' && $transaction->status == 'paid' && $transaction->refunded == '0')
      {
        call_user_func([new \App\Http\Controllers\CheckoutController, 'payment_confirmed_mail_notif'], $transaction);
      }

      return response()->json(['response' => $res]);
    }
}
