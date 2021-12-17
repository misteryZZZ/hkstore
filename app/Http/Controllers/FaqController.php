<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;
use Illuminate\Support\Facades\{Validator, DB};


class FaqController extends Controller
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
                      'orderby' => ['regex:/^(active|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($request->orderby)
      {
        $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
      }

      $faqs = Faq::useIndex($request->orderby ?? 'primary')
                    ->select('faqs.id', 'faqs.question', 'faqs.updated_at', 'faqs.active')
                    ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc')->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.faq.index', ['title' => 'FAQs',
                                      'faqs' => $faqs,
                                      'items_order' => $items_order,
                                      'base_uri' => $base_uri]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.faq.create', ['title' => __('Create FAQ')]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
          'question' => 'bail|required|unique:faqs',
          'answer' => 'bail|required'
        ]);

        $faq = new Faq;

        $faq->question = $request->question;
        $faq->answer = $request->answer;;
        $faq->active = 1;

        $faq->save();

        return redirect()->route('faq');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if(!$faq = Faq::find($id))
          abort(404);

        return view('back.faq.edit', ['title' => __("Edit"),
                                      'faq' => $faq]);
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
        $request->validate([ 
          'question' => "bail|required|unique:faqs,question,{$id}",
          'answer' => 'bail|required'
        ]);

        $faq = Faq::find($id);;

        $faq->question = $request->question;
        $faq->answer = $request->answer;;

        $faq->save();

        return redirect()->route('faq');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
        Faq::destroy(explode(',', $ids));

        return redirect()->route('faq');
    }


    // Toggle "Active" status
    public function status(Request $request)
    {
      $res = DB::update("UPDATE faqs USE INDEX(primary) SET active = IF(active = 1, 0, 1) WHERE id = ?", 
                      [$request->id]);

      return response()->json(['success' => (bool)$res ?? false]);
    }
}
