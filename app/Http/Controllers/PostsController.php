<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Post, Category};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\{ DB, Validator, File };
use App\Libraries\Sitemap;


class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {      
      if(!is_file(public_path('posts.xml')))
      {
        Sitemap::create('posts');
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

        $posts = Post::useIndex('search')
                      ->select('posts.id', 'posts.name', 'posts.slug', 'posts.updated_at', 'posts.active', 'posts.views')
                      ->where('posts.name', 'like', "%{$keywords}%")
                      ->orWhere('posts.slug', 'like', "%{$keywords}%")
                      ->orWhere('posts.short_description', 'like', "%{$keywords}%")
                      ->orWhere('posts.content', 'like', "%{$keywords}%")
                      ->orWhere('posts.tags', 'like', "%{$keywords}%")
                      ->orderBy('id', 'DESC');
      }
      else
      {
        if($request->orderby)
        {
          $base_uri = ['orderby' => $request->orderby, 'order' => $request->order];
        }

        $posts = Post::useIndex($request->orderby ?? 'primary')
                      ->select('posts.id', 'posts.name', 'posts.slug', 'posts.updated_at', 'posts.active', 'posts.views')
                      ->orderBy($request->orderby ?? 'id', $request->order ?? 'desc');
      }

      $posts = $posts->paginate(15);

      $items_order = $request->order === 'desc' ? 'asc' : 'desc';

      return View('back.posts.index', compact('posts', 'items_order', 'base_uri'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::useIndex('`for`')->select('id', 'name')->where('categories.for', 0)->get();

        return view('back.posts.create', ['title' => 'Create post', 'categories' => $categories]);
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
          'name' => 'bail|required|max:255|unique:posts',
          'content' => 'required',
          'short_description' => 'required',
          'cover' => 'required|image'
        ]);

        $category_id = null;

        if($request->input('new_category'))
        {
          $category_id = $this->add_new_category($request);
        }
        else
        {
          $request->validate([
            'category' => ['numeric', 'required', 
                            function ($attribute, $value, $fail) 
                            {                              
                              if(!Category::where(['id' => $value, 'for' => 0])->exists())
                                  $fail($attribute.' does not exist.');
                            }
                          ]
          ]);

          $category_id = $request->input('category');
        }

        $post  = new Post;

        $post_id = Post::get_auto_increment();

        $post->name = $request->name;
        $post->slug = Str::slug($request->name, '-');
        $post->short_description = $request->short_description;
        $post->content = $request->content;
        $post->tags = $request->tags;
        $post->category = $category_id;

        $ext    = $request->file('cover')->extension();
        $cover  = $request->file('cover')
                          ->storeAs('posts', "{$post_id}.{$ext}", ['disk' => 'public']);

        $post->cover = pathinfo($cover, PATHINFO_BASENAME);

        $post->save();

        $sitemap_url = '<url><loc>'.route('home.post', $post->slug).'</loc></url>';

        Sitemap::append($sitemap_url, 'posts');

        return redirect()->route('posts');
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
        $post = Post::find($id) ?? abort(404);
        
        $categories = Category::useIndex('`for`')->select('id', 'name')->where('categories.for', 0)->get();

        return view('back.posts.edit', ['title' => $post->name,
                                        'post' => $post,
                                        'categories' => $categories]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
          'name' => ['bail', 'required', 'max:255', Rule::unique('posts')->ignore($id)],
          'content' => 'required',
          'short_description' => 'required'
        ]);

        $category_id = null;

        if($request->input('new_category'))
        {
          $category_id = $this->add_new_category($request);
        }
        else
        {
          $request->validate([
            'category' => ['numeric', 'required', 
                            function ($attribute, $value, $fail) 
                            {                              
                              if(!Category::where(['id' => $value, 'for' => 0])->exists())
                                  $fail($attribute.' '.__('does not exist'));
                            }
                          ]
          ]);

          $category_id = $request->input('category');
        }

        $post = Post::find($id);
        $copy = clone $post;

        $post->name = $request->name;
        $post->slug = Str::slug($request->name, '-');
        $post->category = $category_id;
        $post->short_description = $request->short_description;
        $post->content = $request->content;
        $post->tags = $request->tags;

        if($cover = $request->file('cover'))
        {
          $ext    = $cover->extension();
          $cover  = $cover->storeAs('posts', "{$id}.{$ext}", ['disk' => 'public']);

          $post->cover = pathinfo($cover, PATHINFO_BASENAME);
        }

        $post->updated_at = date('Y-m-d H:i:s');
        
        $post->save();

        $sitemap_old_url = '<url><loc>'.route('home.post', $copy->slug).'</loc></url>';
        $sitemap_new_url = '<url><loc>'.route('home.post', $post->slug).'</loc></url>';

        Sitemap::update($sitemap_old_url, $sitemap_new_url, 'posts');

        return redirect()->route('posts');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $ids
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $ids)
    {
      $ids = array_filter(explode(',', $ids));
      
      $sitemap_urls = Post::useIndex('primary')
                      ->selectRaw("CONCAT('<url>', CONCAT('<loc>', CONCAT(?, slug), '</loc>'), '</url>') AS sitemap_url", [url('/blog').'/'])
                      ->whereIn('id', $ids)->pluck('sitemap_url')->toArray();

      if(Post::destroy($ids))
      {
        foreach($ids as $id)
        {
          @File::delete(glob(storage_path("app/downloads/{$id}.*")));
        }

        Sitemap::delete($sitemap_urls, 'posts');
      }


      return redirect()->route('posts');
    }



    public function status(Request $request)
    {      
      $res = DB::update("UPDATE posts USE INDEX(primary) SET {$request->status} = IF({$request->status} = 1, 0, 1) WHERE id = ?", 
                      [$request->id]);

      $post =   Post::useIndex('primary')
                ->selectRaw("active, CONCAT('<url>', CONCAT('<loc>', CONCAT(?, slug), '</loc>'), '</url>') AS sitemap_url", [url('/blog').'/'])
                ->where('id', $request->id)->get()->first();

      if($request->status === 'active')
      {
        if(!$post->active)
        {
          Sitemap::delete($post->sitemap_url, 'posts');
        }
        else
        {
          Sitemap::append($post->sitemap_url, 'posts');
        }
      }

      return response()->json(['success' => (bool)$res ?? false]);
    }



    private function add_new_category($request)
    {
      $request->validate([
        'new_category' => ['required', 'max:255', 
                            function ($attribute, $value, $fail) 
                            {                              
                              if(Category::where(['name' => $value, 'for' => 0])->exists())
                                  $fail($attribute.' '.__('already exists'));
                            }
                          ]
      ]);

      $category = new Category;

      $category->name = $request->input('new_category');
      $category->slug = Str::slug($category->name, '-');
      $category->for  = 0;

      $category->save();

      return $category->id;
    }
}
