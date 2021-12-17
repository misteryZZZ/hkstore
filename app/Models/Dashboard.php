<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Dashboard extends Model
{
    protected static function counts()
    {
    	return DB::select('SELECT 
												(SELECT COUNT(id) FROM products USE INDEX(active)) as products,
						 						(SELECT COUNT(id) FROM posts USE INDEX(active)) as posts,
						 						(SELECT COUNT(id) FROM users USE INDEX(primary)) as users,
						 						(SELECT COUNT(id) FROM categories USE INDEX(primary) WHERE parent IS NULL) as categories,
						 						(SELECT COUNT(id) FROM newsletter_subscribers USE INDEX(primary)) as newsletter_subscribers,
						 						(SELECT COUNT(id) FROM transactions USE INDEX(primary)) as orders,
						 						(SELECT ROUND(SUM(amount)-SUM(refund), 2) FROM transactions USE INDEX(primary)) as earnings,
						 						(SELECT COUNT(id) FROM comments USE INDEX(primary)) as comments')[0] ?? [];
    }


    protected static function traffic($filter = [])
    {
    	return DB::select('SELECT iso_codes, EXTRACT(DAY from created_at) as `day` FROM traffic 
    										 WHERE created_at BETWEEN ? AND ?', 
    										 [date('Y-m-01'), date('Y-m-'.date('t'))]);
    }



    protected static function transactions()
    {
    	return DB::select("SELECT transactions.id, transactions.is_subscription, GROUP_CONCAT(IF(transactions.is_subscription = 0, products.name, subscriptions.name) SEPARATOR '|---|') as products, transactions.processor,
    										 users.name as buyer_name, users.email as buyer_email, transactions.amount, transactions.created_at as `date`
    										 FROM transactions USE INDEX(products_ids, user_id)
    										 LEFT JOIN products USE INDEX(primary) ON transactions.products_ids REGEXP CONCAT('\'', products.id, '\'')
                                             LEFT JOIN subscriptions USE INDEX(primary) ON transactions.products_ids REGEXP CONCAT('\'', subscriptions.id, '\'')
    										 JOIN users USE INDEX(primary) ON transactions.user_id = users.id 
    										 GROUP BY transactions.processor, transactions.id, transactions.is_subscription, buyer_name, buyer_email, amount, `date` 
                                             ORDER BY transactions.created_at DESC 
                                             LIMIT 5");
    }



    protected static function sales($month = [])
    {
    	$month = empty($month) ? [date('Y-m-01'), date('Y-m-'.date('t'))] : $month;

    	return DB::select("SELECT COUNT(transactions.id) AS `count`, EXTRACT(DAY from transactions.created_at) as `day`
    										 FROM transactions USE INDEX(primary, created_at) WHERE created_at BETWEEN ? AND ? GROUP BY `day`",
    										 $month);
    }


    protected static function newsletter_subscribers()
    {
    	return DB::select('SELECT email, created_at FROM newsletter_subscribers USE INDEX(created_at) 
    										 ORDER BY created_at DESC LIMIT 5');
    }


    protected static function reviews()
    {
    	return DB::select('SELECT reviews.rating, reviews.created_at, products.name AS product_name, products.id as product_id, products.slug AS product_slug FROM reviews USE INDEX(product_id) JOIN products ON products.id = reviews.product_id ORDER BY reviews.updated_at LIMIT 5');
    }

}
