<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\{ DB, Validator };
use App\Libraries\Sitemap;


class PagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      if(!is_file(public_path('pages.xml')))
      {
        Sitemap::create('pages');
      }


      $validator =  Validator::make($request->all(),
                    [
                      'orderby' => ['regex:/^(name|active|views|updated_at)$/i', 'required_with:order'],
                      'order' => ['regex:/^(asc|desc)$/i', 'required_with:orderby']
                    ]);

      if($validator->fails()) abort(404);

      $base_uri = [];

      if($keywords = $request->keywords)
      {
        $base_uri = ['keywords' => $keywords];

        $pages = Page::useIndex('description')
                      ->select('pages.id', 'pages.name', 'pages.slug', 'pages.updated_at', 'pages.active', 'pages.views')
                      ->where('pages.name', 'like', "%{$keywords}%")
                      ->orWhere('pages.slug', 'like', "%{$keywords}%")
                      ->orWhere('pages.short_description', 'like', "%{$keywords}%")
                      ->orWhere('pages.content', 'like', "%{$keywords}%")
                      ->orWhere('pages.tags', 'like', "%{$keywords}%")
                      ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $pages = Page::useIndex($request->orderby ?? 'primary')
                      ->select('pages.id', 'pages.name', 'pages.slug', 'pages.updated_at', 'pages.active', 'pages.views')
                      ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $pages = $pages->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.pages.index', ['title' => __('Pages'),
                                       'pages' => $pages,
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
      return view('back.pages.create', ['title' => __('Create page')]);
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
          'name' => 'bail|required|max:255|unique:pages',
          'content' => 'bail|required'
        ]);

        $page = new Page;

        $page->name = $request->name;
        $page->slug = Str::slug($request->name, '-');
        $page->short_description = $request->short_description;
        $page->content = $request->content;
        $page->tags = $request->tags;

        $page->save();

        $sitemap_url = '<url><loc>'.route('home.page', $page->slug).'</loc></url>';

        Sitemap::append($sitemap_url, 'pages');

        return redirect()->route('pages');
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
        $page = Page::find($id) ?? abort(404);

        return view('back.pages.edit', ['title' => $page->name,
                                        'page'  => $page]);
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
          'name'    => "bail|required|max:255|unique:pages,name,{$id}",
          'content' => 'bail|required'
        ]);

        $page = Page::find($id) ?? abort(404);
        $copy = clone $page;

        $sitemap_old_url = '<url><loc>'.route('home.page', $page->slug).'</loc></url>';

        if($page->deletable)
        {
          $page->name               = $request->name;
          $page->slug               = Str::slug($request->name, '-');
        }
        
        $page->short_description  = $request->short_description;
        $page->content            = $request->content;
        $page->tags               = $request->tags;
        $page->updated_at         = date('Y-m-d H:i:s');

        $page->save();

        $sitemap_old_url = '<url><loc>'.route('home.page', $copy->slug).'</loc></url>';
        $sitemap_new_url = '<url><loc>'.route('home.page', $page->slug).'</loc></url>';

        Sitemap::update($sitemap_old_url, $sitemap_new_url, 'pages');

        return redirect()->route('pages');
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  string $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      $ids = array_filter(explode(',', $ids));
      $sitemap_urls = Page::useIndex('primary')
                      ->selectRaw("CONCAT('<url>', CONCAT('<loc>', CONCAT(?, slug), '</loc>'), '</url>') AS sitemap_url", [url('/page').'/'])
                      ->whereIn('id', $ids)->where('deletable', 1)->pluck('sitemap_url')->toArray();

      if(Page::whereIn('id', $ids)->where('deletable', 1)->delete())
      {
        Sitemap::delete($sitemap_urls, 'pages');
      }

      return redirect()->route('pages');
    }



    // Toggle "Active" status
    public function status(Request $request)
    {
      $res = DB::update("UPDATE pages USE INDEX(primary) SET active = IF(active = 1, 0, 1) WHERE id = ?", [$request->id]);
      
      $page =   Page::useIndex('primary')
                ->selectRaw("active, CONCAT('<url>', CONCAT('<loc>', CONCAT(?, slug), '</loc>'), '</url>') AS sitemap_url", [url('/page').'/'])
                ->where('id', $request->id)->get()->first();

      if(! $page->active)
      {
        Sitemap::delete($page->sitemap_url, 'pages');
      }
      else
      {
        Sitemap::append($page->sitemap_url, 'pages');
      }

      return response()->json(['success' => (bool)$res ?? false]);
    }
}
