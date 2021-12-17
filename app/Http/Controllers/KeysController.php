<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{ Key, Product };
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{ File, DB };
use App\Http\Controllers\Controller;


class KeysController extends Controller
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
              'orderby' => ['regex:/^(code|user_email|product_name|purchased_at|updated_at)$/i', 'required_with:order'],
              'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
            ])->fails() || abort(404);


        $base_uri = [];

        $keys = Key::select('key_s.id', 'key_s.code', 'key_s.user_id', 'key_s.product_id', 'key_s.purchased_at', 'key_s.updated_at', 
                            'products.name as product_name', 'products.slug as product_slug', 'users.email as user_email')
                        ->join('products USE INDEX(primary)', 'products.id', '=', 'key_s.product_id')
                        ->leftJoin('users USE INDEX(primary)', 'users.id', '=', 'key_s.user_id');

        if($keywords = $request->keywords)
        {
          $base_uri = ['keywords' => $keywords];

          $keys = $keys->where('key_s.code', 'like', "%{$keywords}%")
                        ->orWhere('products.name', 'like', "%{$keywords}%")
                        ->orWhere('users.email', 'like', "%{$keywords}%")
                        ->orderBy('id', 'DESC');
        }
        else
        {
          if($request->orderby)
          {
            $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
          }

          $keys = $keys->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
        }

        $keys = $keys->paginate(15);

        $items_order = $request->order === 'desc' ? 'asc' : 'desc';

        return View('back.keys.index', compact('keys', 'items_order', 'base_uri'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $products = Product::select('id', 'name')->where('active', 1)->get();

        return view('back.keys.create', compact('products'));
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
            'code' => 'nullable|string|bail',
            'codes' => 'nullable|file|mimes:txt',
            'separator' => 'nullable|string|required_with:codes',
            'product_id' => ['required', 'numeric', Rule::unique('key_s')->where(function ($query) use($request)
                            {
                                return $query->where('code', $request->post('code'))
                                             ->where('product_id', $request->post('product_id'));
                            })]
        ]);

        
        if($request->code)
        {
            $key = new Key;

            $key->code = $request->post('code');
            $key->product_id = $request->post('product_id');

            $key->save();
        }
        elseif($codes = $request->file('codes'))
        {
            $content = File::get($codes->getRealPath());

            $patern = "/{$request->separator}/i";

            $codes =    array_reduce(preg_split($patern, $content), function($ac, $code) use ($request)
                        {
                            if(mb_strlen(trim($code)))
                            {
                                $ac[] = ['product_id' => $request->post('product_id'), 'code' => trim($code)];
                            }

                            return $ac;
                        }, []);

            if($codes)
            {
                Key::insert($codes);
            }
        }

        
        return redirect()->route('keys')->with(['message' => __('Done!')]);
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
        $key      = Key::find($id);
        $products = Product::select('id', 'name')->where('active', 1)->get();

        return view('back.keys.edit', compact('products', 'key'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, $redirect = true)
    {
        $key = Key::find($id) ?? abort(404);

        $request->validate([
            'code' => ['required', 'string', 'max:255', Rule::unique('key_s')->where(function ($query) use($request)
                            {
                                return $query->where('code', $request->post('code'))
                                             ->where('product_id', $request->post('product_id'))
                                             ->where('id', '!=', $request->id);
                            })],
            'product_id' => ['required', 'numeric']
        ]);

        $key->code       = $request->post('code');
        $key->product_id = $request->post('product_id');

        $key->save();
        
        if($redirect)
        {
            return redirect()->route('keys')->with(['message' => __('Done!')]);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
        Key::destroy(explode(',', $ids));

        return redirect()->route('keys');
    }


    public function update_async(Request $request)
    {        
        $this->update($request, $request->id, false);
    }
    
    public function void_purchase(Request $request)
    {
        $request->post('id') || abort(404);
        
        DB::update("UPDATE key_s SET user_id = null where id = ?", [$request->post('id')]);
    }
}
