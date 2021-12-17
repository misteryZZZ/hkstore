<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\{ Schema };
use App\Events\NewMail;


class UsersController extends Controller
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
                      'orderby' => ['regex:/^(name|email|verified|created_at|purchases|total_purchases)$/i', 
                                    'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $users = User::useIndex('user')
                      ->selectRaw("users.id, IFNULL(users.name, '-') as `name`, users.email, 
                        users.created_at, users.blocked, 
                        IF(users.email_verified_at IS NOT NULL, 1, 0) as verified,
                        (select ifnull(sum(items_count), 0) from transactions where user_id = users.id) as purchases,
                        (select ifnull(round(sum(amount), 2), 0) from transactions where user_id = users.id) as total_purchases
                      ")
                      ->where('users.name', 'like', "%{$keywords}%")
                      ->orWhere('users.email', 'like', "%{$keywords}%")
                      ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $index  = preg_match('/^(purchases|total_purchases|)$/i', $request->orderby) ? 'primary' : $request->orderby;

        $users = User::useIndex($index)
                      ->selectRaw("users.id, IFNULL(users.name, '-') as `name`, users.email, 
                        users.created_at, users.blocked, 
                        IF(users.email_verified_at IS NOT NULL, 1, 0) as verified,
                        (select ifnull(sum(items_count), 0) from transactions where user_id = users.id) as purchases,
                        (select ifnull(round(sum(amount), 2), 0) from transactions where user_id = users.id) as total_purchases
                      ")
                      ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $users = $users->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.users', compact('users', 'items_order', 'base_uri'));
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        $request->validate(['status' => "required|in:verified,blocked", 
                            'id' => 'required|numeric',
                            'val' => 'required|numeric|in:0,1']);

        if($request->status === 'blocked')
        {
            /*if($user = User::find($request->id))
            {
                $user->blocked = $request->val;
                $user->save();
            }*/

          \DB::update("UPDATE users USE INDEX(primary) SET blocked = ? WHERE id = ?", [(string)$request->val, $request->id]);

          $blocked_users = array_unique(\Cache::get('blocked_users', []));
    
          if($request->val)
          {
            $blocked_users[] = $request->id;
          }
          else
          {
            unset($blocked_users[array_search($request->id, $blocked_users)]);
          }
          
          \Cache::forever('blocked_users', $blocked_users);
        }
        else
        {
          \DB::update("UPDATE users USE INDEX(primary) SET email_verified_at = IF(? = 1, CURRENT_TIMESTAMP, NULL) WHERE id = ?", [$request->val, $request->id]);
        }
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      $ids = explode(',', $ids);

      if(User::whereIn('id', $ids)->Where('role', '!=', 'admin')->delete())
      {
        foreach($ids as $id)
        {
          $avatar = glob(storage_path("app/public/avatars/{$id}.*"))[0] ?? NULL;

          if($avatar) unlink($avatar);
        }
      }

      return redirect()->route('users');
    }



    public function notify(Request $request)
    {
      $request->validate([
        'emails' => 'nullable|array',
        'emails.*' => 'nullable|email',
        'notification' => 'required|string'
      ]);

      if(!$emails = $request->post('emails'))
      {
        $emails = User::select('email')->where('blocked', 0)->get()->pluck('email')->toArray();
      }

      $emails = array_filter($emails);

      $mail_props = [
        'data' => [
          'html' => $request->post('notification'),
        ],
        'view' => 'mail.html',
        'to' => $emails,
        'subject' => __('Notification from :app_name', ['app_name' => config('app.name')]),
        'action' => 'send'
      ];

      NewMail::dispatch($mail_props, false);
    }
}
