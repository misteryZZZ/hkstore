<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\{Validator, DB};
use \App\Models\Comment;


class CommentsController extends Controller
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
                        'orderby' => ['regex:/^(user_name|user_email|item_name|approved|created_at)$/i', 'required_with:order'],
                        'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                      ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $request->keywords];

        $comments = Comment::useIndex('primary')
                                ->select('comments.*', 'products.name as item_name', 'products.slug as item_slug', 'products.id as item_id', 
                                         'users.name as user_name', 'users.email as user_email', 'users.id as user_id')
                                ->leftJoin('products USE INDEX(primary)', 'products.id', '=', 'comments.product_id')
                                ->leftJoin('users USE INDEX(primary)', 'users.id', '=', 'comments.user_id')
                                ->where('products.name', 'like', "%{$keywords}%")
                                ->orWhere('users.name', 'like', "%{$keywords}%")
                                ->orWhere('users.email', 'like', "%{$keywords}%")
                                ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $comments = Comment::useIndex('primary')
                                ->select('comments.*', 'products.name as item_name', 'products.slug as item_slug', 'products.id as item_id', 
                                         'users.name as user_name', 'users.email as user_email', 'users.id as user_id')
                                ->leftJoin('products USE INDEX(primary)', 'products.id', '=', 'comments.product_id')
                                ->leftJoin('users USE INDEX(primary)', 'users.id', '=', 'comments.user_id')
                                ->orderBy($request->orderby ?? 'id', $request->order ?? 'DESC');
      }

      $comments = $comments->paginate(15);



      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.comments', ['title'       => __('Comments'),
                                    'comments'    => $comments,
                                    'items_order' => $items_order,
                                    'base_uri'    => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      Comment::destroy(explode(',', $ids));

      return redirect()->route('comments');
    }


    public function status(Request $request)
    {
      $comment = Comment::find($request->id);

      $comment->approved = $comment->approved == 1 ? 0 : 1;

      $res = $comment->save();

      if($res && $comment->approved == 1)
      {
        \App\Models\Notification::notifyUsers($request->item_id, $request->user_id, 1);
      }

      return response()->json(['success' => (bool)$res ?? false]);
    }
}
