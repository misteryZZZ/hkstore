<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;


class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    public function __construct()
    {
    	
    }


       /**
     * Export resources.
     *
     * @return null
     */
    public function export(Request $request)
    {
    		$models = [
    			'products' 		  => 'Product',
    			'categories' 	  => 'Category',
    			'users' 			  => 'User',
    			'posts' 			  => 'Post',
    			'pages' 			  => 'Page',
    			'comments' 		  => 'Comment',
    			'coupons' 		  => 'Coupon',
    			'reviews' 		  => 'Review',
    			'subscribers'   => 'Newsletter_Subscriber',
    			'faqs' 				  => 'Faq',
    			'transactions'  => 'Transaction',
          'support'       => 'Support',
          'subscriptions' => 'Subscription',
          'keys'          => 'Key',
          'licenses'      => 'License',
    		];

    		$model 	 = $request->model;
        $columns = collect($request->post('columns'));
        $columns = $columns->where('active', 'on')->toArray();

        $new_columns = [];

        foreach($columns as &$column)
        {
          $new_columns[] = $column['new_name'] ? "`{$column['name']}` as `{$column['new_name']}`" : "`{$column['name']}`";

          $column = wrap_str($column['new_name'] ?? $column['name'], '"');
        }
        
        $columns = implode(',', $columns);

        $records = call_user_func_array(["\App\Models\\$models[$model]", 'useIndex'], ['primary']);

        $records = $records->selectRaw(implode(',', $new_columns));

        if($ids = $request->post('ids'))
        {
          $records = $records->whereIn('id', explode(',', $ids));
        }

        $records = $records->get();

        if($records->count())
        {
          $values = [];

          foreach($records as $product)
          {
            $values[] = implode(',', array_reduce(array_values($product->toArray()), function($c, $val)
                        {
                          $c[] = wrap_str(addcslashes($val, '"'), '"');
                          return $c;
                        }, []));
          }

          $values = implode("\n", $values);

          return  response()->streamDownload(function() use($columns, $values, $model)
                              {
                                echo "{$columns}\n{$values}";
                              },"{$model}.csv");
        }
    }
}
