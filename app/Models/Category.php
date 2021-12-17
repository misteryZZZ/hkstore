<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Category extends Model
{
    protected static function parents(string $columns = '*')
    {
    	return DB::select("SELECT $columns 
                         FROM categories USE INDEX(parent, `for`) 
                         WHERE parent IS NULL AND `for` = 1 ORDER BY `range` ASC");
    }


    protected static function children(string $columns = '*')
    {
    	return DB::select("SELECT $columns
                         FROM categories USE INDEX(parent, `for`) 
                         WHERE parent IS NOT NULL AND `for` = 1 ORDER BY `range` ASC");
    }


    // Products categories
    protected static function products()
    {
      $category_children = $category_parents = [];

      if($_category_parents = Self::parents("id, name, slug, parent, `range`"))
      {
        $category_parents_ids = array_column($_category_parents, 'id');
        $category_parents     = array_combine($category_parents_ids, array_values($_category_parents));

        if($_category_children = Self::children("id, name, slug, parent, `range`"))
        {
          foreach($_category_children as $_category_child)
          {
            foreach($category_parents as $id => $category_parent)
            {
              if($id == $_category_child->parent)
              {
                $category_children[$id][] = $_category_child;
              }
            }
          }
        }
      }
      
      return compact('category_children', 'category_parents');
    }



    protected static function popular(int $limit = 5)
    {
      return Self::useIndex('primary')
                  ->selectRaw('categories.name, categories.slug, SUM(products.views) as views')
                  ->leftJoin('products', 'categories.id', '=', 'products.category')
                  ->where(['products.active' => 1, 'categories.parent' => null])
                  ->groupBy('name', 'slug')
                  ->orderBy('views', 'desc')
                  ->limit($limit)
                  ->get();
    }
}
