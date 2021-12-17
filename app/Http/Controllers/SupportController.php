<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ DB, Validator };
use App\Models\{ Support_Email, Support };
use App\Events\NewMail;


class SupportController extends Controller
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
                      'orderby' => ['regex:/^(subject|email|read|created_at)$/i', 'required_with:order'],
                      'order'   => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      !$validator->fails() || abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $support_messages = Support::useIndex('search', 'email_id')
                                    ->select('support.id', 'support.subject', 'support.message AS content', 
                                             'support.created_at', 'support.read', 'support_email.email AS email')
                                    ->join('support_email', 'support_email.id', '=', 'support.email_id')
                                    ->where('email', 'like', "%{$keywords}%")
                                    ->where('parent', '=', null)
                                    ->orWhere('subject', 'like', "%{$keywords}%")
                                    ->orWhere('support.message', 'like', "%{$keywords}%")
                                    ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $support_messages = Support::useIndex($request->orderby ?? 'primary')
                                    ->select('support.id', 'support.subject', 'support.message AS content', 'support.created_at', 
                                             'support.read', 'support_email.email AS email')
                                    ->leftJoin('support_email', 'support_email.id', '=', 'support.email_id')
                                    ->where('parent', '=', null)
                                    ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $support_messages = $support_messages->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.support', ['title' => __('Support messages'),
                                   'support_messages' => $support_messages,
                                   'items_order' => $items_order,
                                   'base_uri' => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {      
      $request->validate([
        'message' => 'required|string',
        'email' => 'required|email',
        'subject' => 'required|string'
      ]);

      $mail_props = [
        'data' => [
          'text' => $request->message,
          'subject' => __('You have received a message from :app_name', ['app_name' => config('app.name')])
        ],
        'view' => 'mail.message',
        'to' => $request->email,
        'subject' => $request->subject,
        'action' => 'send'
      ];

      NewMail::dispatch($mail_props, false);

      return response()->json(['status' => true]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        Support::destroy(explode(',', $request->ids));

        $to_route = $request->to_route ?? ['support'];

        return redirect()->route(...$to_route);
    }


    public function status(Request $request)
    {
      $res = DB::update("UPDATE support USE INDEX(primary) SET `read` = 1 WHERE id = ?", 
                        [(int)$request->id]);

      return response()->json(['success' => (bool)$res ?? false]);
    }




    public function load_unseen(Request $request)
    {
      
    }

}
