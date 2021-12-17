<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ Search };
use Illuminate\Support\Facades\{ Validator, DB };
use App\Http\Controllers\Controller;


class SearchesController extends Controller
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
                        'orderby' => ['regex:/^(user|keywords|created_at|occurrences)$/i', 'required_with:order'],
                        'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                      ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $request->keywords];

        $searches = Search::useIndex('primary')
                                ->select('searches.id', 'searches.keywords', 'searches.created_at', 'users.email as user', DB::raw('(SELECT COUNT(searches2.id) FROM searches as searches2 WHERE searches2.keywords = searches.keywords) as occurrences'))
                                ->leftJoin('users USE INDEX(primary)', 'users.id', '=', 'searches.user_id')
                                ->where('searches.keywords', 'like', "%{$keywords}%")
                                ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $searches = Search::useIndex('primary')
                                ->select('searches.id', 'searches.keywords', 'searches.created_at', 'users.email as user', DB::raw('(SELECT COUNT(searches2.id) FROM searches as searches2 WHERE searches2.keywords = searches.keywords) as occurrences'))
                                ->leftJoin('users USE INDEX(primary)', 'users.id', '=', 'searches.user_id')
                                ->orderBy($request->orderby ?? 'id', $request->order ?? 'DESC');
      }

      $searches = $searches->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.searches', compact('searches', 'items_order', 'base_uri'));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      Search::destroy(explode(',', $ids));

      return redirect()->route('searches');
    }
}
