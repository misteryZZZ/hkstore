<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ License, Product };
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;


class LicensesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        !\Validator::make($request->all(),
            [
              'orderby' => ['regex:/^(name|item_type|updated_at)$/i', 'required_with:order'],
              'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
            ])->fails() || abort(404);


        $base_uri = [];

        $licenses = License::selectRaw('licenses.id, licenses.name, licenses.item_type, licenses.updated_at');

        if($keywords = $request->keywords)
        {
          $base_uri = ['keywords' => $keywords];

          $licenses = $licenses->where('licenses.name', 'like', "%{$keywords}%")
                        ->orderBy('id', 'DESC');
        }
        else
        {
          if($request->orderby)
          {
            $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
          }

          $licenses = $licenses->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
        }

        $licenses = $licenses->paginate(15);

        $items_order = $request->order === 'desc' ? 'asc' : 'desc';

        return View('back.licenses.index', compact('licenses', 'items_order', 'base_uri'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('back.licenses.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        $item_types = implode(',', array_keys(config('app.item_types') ?? []));

        $request->validate([
            'name' => ['bail', 'required', 'max:255', Rule::unique('licenses')->where(function($query) use($request) 
                                                        {
                                                           return $query->where('name', $request->post('name'))
                                                                        ->where('item_type', $request->post('item_type'));
                                                        })],
            'item_type' => "string|nullable|in:{$item_types}",
            'regular' => 'nullable|in:on'
        ]);

        $license = new License;

        $license->name = $request->post('name');
        $license->item_type = $request->post('item_type') ?? '-';
        $license->regular = $request->post('regular') ? '1' : '0';

        if($license->regular === '1')
        {
            License::where('item_type', $license->item_type)->update(['regular' => 0]);
        }

        $license->save();

        return redirect()->route('licenses')->with(['message' => __('Done')]);
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
        $license = License::find($id) ?? abort(404);

        return view('back.licenses.edit', compact('license'));
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
        $license = License::find($id) ?? abort(404);

        $item_types = implode(',', array_keys(config('app.item_types') ?? []));

        $request->validate([
            'name' => ['required', 'max:255', Rule::unique('licenses')->where(function($query) use($request) 
                                                {
                                                   return $query->where('name', $request->post('name'))
                                                                ->where('item_type', $request->post('item_type'));
                                                })->ignore($license->id)],
            'item_type' => "string|nullable|in:{$item_types}",
            'regular' => 'nullable|in:on'
        ]);

        $license->name = $request->post('name');
        $license->item_type = $request->post('item_type') ?? '-';
        $license->regular = $request->post('regular') ? '1' : '0';

        if($license->regular === '1')
        {
            License::where('item_type', $license->item_type)->where('id', '!=', $id)->update(['regular' => 0]);
        }

        $license->save();

        return redirect()->route('licenses')->with(['message' => __('Done')]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
        License::destroy(explode(',', $ids));

        return redirect()->route('licenses');
    }
}
