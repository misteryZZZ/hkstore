<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{ Comment, Review, Support, Transaction };
use Illuminate\Support\Facades\{ Validator, DB };


class AdminNotifsController extends Controller
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
                        'orderby' => ['regex:/^(item_id|item_created_at|user|table)$/i', 'required_with:order'],
                        'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                      ]);

        if($validator->fails()) abort(404);

        $base_uri = [];

        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $comments = Comment::selectRaw("comments.id as item_id, comments.body as item_content, comments.created_at as item_created_at, 
                                        users.email as user, 'comments' as `table`")
                    ->where('comments.read_by_admin', '0')
                    ->leftJoin('users', 'users.id', '=', 'comments.user_id');

        $reviews =  Review::selectRaw("reviews.id as item_id, reviews.content as item_content, reviews.created_at as item_created_at, 
                                     users.email as user, 'reviews' as `table`")
                    ->leftJoin('users', 'users.id', '=', 'reviews.user_id')
                    ->where('reviews.read_by_admin', '0');

        $tranactions =  Transaction::selectRaw("transactions.id as item_id, 'A new sale has been completed.' as content, transactions.created_at as created_at, users.email as user, 'transactions' as `table`")
                    ->leftJoin('users', 'users.id', '=', 'transactions.user_id')
                    ->where('transactions.read_by_admin', '0');

        $admin_notifs  = Support::selectRaw("support.id as item_id, support.message as item_content, support.created_at as item_created_at
                                             , support_email.email as user, 'support' as `table`")
                        ->where('support.read_by_admin', '0')
                        ->leftJoin('support_email', 'support_email.id', '=', 'support.email_id')
                        ->union($tranactions)
                        ->union($comments)
                        ->union($reviews)
                        ->orderBy($request->orderby ?? 'item_id', $request->order ?? 'DESC')
                        ->paginate(15);

        
        $items_order = $request->order === 'desc' ? 'asc' : 'desc';

        return View('back.admin_notifs', compact('admin_notifs', 'items_order', 'base_uri'));
    }



    public function mark_as_read(Request $request)
    {        
        $items = collect($request->post('items'))->groupBy('table');
        $models = [
            'reviews' => 'Review',
            'comments' => 'Comment',
            'support' => 'Support',
            'transactions' => 'Transaction'
        ];

        foreach($items as $table => $list)
        {
            $ids = array_column($list->toArray(), 'id');

            $model = "App\Models\\".$models[$table];

            $model::whereIn('id', $ids)->update(['read_by_admin' => 1]);
        }
    }


    public static function latest($limit = 5)
    {
        $comments = Comment::selectRaw("comments.id as item_id, 'A comment has been posted.' as content, comments.created_at as created_at, 
                                        users.email as user, 'comments' as `table`") 
                    ->where('comments.read_by_admin', '0')
                    ->leftJoin('users', 'users.id', '=', 'comments.user_id');

        $reviews =  Review::selectRaw("reviews.id as item_id, 'A review has been submitted for one of your items.' as content, reviews.created_at as created_at, 
                                     users.email as user, 'reviews' as `table`")
                    ->leftJoin('users', 'users.id', '=', 'reviews.user_id')
                    ->where('reviews.read_by_admin', '0');

        $tranactions =  Transaction::selectRaw("transactions.id as item_id, 'A new sale has been completed.' as content, transactions.created_at as created_at, users.email as user, 'transactions' as `table`")
                    ->leftJoin('users', 'users.id', '=', 'transactions.user_id')
                    ->where('transactions.read_by_admin', '0');

        return Support::selectRaw("support.id as item_id, 'You received a new support message' as content, support.created_at as created_at
                                             , support_email.email as user, 'support' as `table`")
                        ->where('support.read_by_admin', '0')
                        ->leftJoin('support_email', 'support_email.id', '=', 'support.email_id')
                        ->union($tranactions)
                        ->union($comments)
                        ->union($reviews)
                        ->orderBy('item_id', 'DESC')
                        ->paginate($limit);
    }
}
