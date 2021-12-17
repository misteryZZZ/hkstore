<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Product extends Model
{
    protected $guarded = [];


    public static function by_id(int $id, string $user_id = 'null')
    {
      DB::statement(DB::raw("SET @purchased = 0, @reviewed = 0"));

      $item = DB::select("SELECT products.*, product_price.price, licenses.name as license_name, licenses.id as license_id, 
                    products.additional_fields, 
                    CASE
                      WHEN product_price.promo_price IS NOT NULL AND (promotional_price_time IS NULL OR (promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN STR_TO_DATE(SUBSTR(products.promotional_price_time, 10, 10), '%d-%m-%Y') and STR_TO_DATE(SUBSTR(products.promotional_price_time, 28, 10), '%d-%m-%Y')))
                        THEN product_price.promo_price
                      ELSE
                        NULL
                    END AS promotional_price,
                    (product_price.price = 0 || free IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN SUBSTR(products.free, 10, 10) and SUBSTR(products.free, 28, 10)) AS free,
                    products.free as free_time,
                    (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id AND key_s.user_id IS NULL) as `remaining_keys`,
                    (SELECT COUNT(key_s.id) FROM key_s WHERE key_s.product_id = products.id) as has_keys,
                    key_s.code as key_code,
                    IF(promotional_price_time IS NOT NULL AND DATE_FORMAT(CURRENT_TIMESTAMP, '%Y-%m-%d') BETWEEN STR_TO_DATE(SUBSTR(products.promotional_price_time, 10, 10), '%d-%m-%Y') and STR_TO_DATE(SUBSTR(products.promotional_price_time, 28, 10), '%d-%m-%Y'), promotional_price_time, null) AS promotional_price_time,
                    IF(product_price.price = 0 || (free IS NOT NULL AND CURRENT_DATE BETWEEN SUBSTR(free, 10, 10) AND SUBSTR(free, 28, 10)) = 1, 0, product_price.price) AS price,
                    products.hidden_content,
                    categories.name as category, categories.slug as category_slug , categories.id as category_id, 
                    (SELECT COUNT(transactions.id) FROM transactions WHERE transactions.products_ids REGEXP CONCAT(\"'\", products.id, \"'\")) AS sales,
                    (SELECT COUNT(comments.id) FROM comments WHERE comments.product_id = products.id AND comments.approved = 1) AS comments_count,
                    (SELECT COUNT(reviews.id) FROM reviews WHERE product_id = products.id) AS reviews_count,
                    IFNULL((SELECT ROUND(AVG(rating)) FROM reviews WHERE product_id = products.id AND approved = 1), 0) AS rating,
                    (CASE WHEN ? IS NOT NULL THEN @purchased := (SELECT COUNT(*) FROM transactions WHERE (transactions.user_id = ? OR transactions.guest_token = ?) AND products_ids REGEXP CONCAT(\"'\", products.id, \"'\") AND transactions.is_subscription = 0 AND transactions.confirmed = 1 AND transactions.status = 'paid' AND transactions.refunded = 0) END) AS purchased,
                    (CASE WHEN ? IS NOT NULL THEN @reviewed := (SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = products.id) END) AS reviewed
                    FROM products USE INDEX(slug, active)
                    LEFT JOIN categories ON categories.id = products.category
                    LEFT JOIN licenses ON licenses.item_type = products.`type` AND licenses.regular = '1'
                    LEFT JOIN product_price ON product_price.license_id = licenses.id AND product_price.product_id = products.id
                    LEFT JOIN key_s ON key_s.product_id = products.id AND key_s.user_id = ?
                    WHERE products.id = ? AND products.active = 1 AND products.is_dir = ?
                    GROUP BY products.id, products.name, products.slug, products.short_description, products.overview, products.preview_url, products.direct_download_link, products.bpm, products.bit_rate, products.table_of_contents, products.label, products.country_city, products.pages, product_price.promo_price, product_price.price, products.notes, products.active, products.category, products.subcategories, licenses.name, licenses.id, products.cover, products.screenshots, products.version, products.release_date, products.last_update, products.hidden_content, products.included_files, products.tags, products.preview, products.preview_type, products.software, products.db, products.compatible_browsers, products.compatible_os, products.high_resolution, products.documentation, products.file_name, products.file_size, products.file_host, products.created_at, products.deleted_at, products.updated_at, products.free, products.featured, products.trending, products.views, products.faq, category_id, comments_count, reviews_count, products.stock, rating, purchased, reviewed, categories.name, categories.slug, products.is_dir, promotional_price_time, products.enable_license, products.is_dir, products.for_subscriptions, products.type, products.authors, products.language, products.words, products.formats, products.additional_fields, products.newest, key_s.code", 
                    [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $id, (isFolderProcess() ? 1 : 0)]);

      $product = new Self(obj2arr(array_shift($item)));

      return $product;
    }



    public function type_is($type_name):bool
    {
        return $this->type == $type_name;
    }


    public function type_matches($regexp):bool
    {
        return preg_match($regexp, $this->type);
    }



    public function preview_is($name):bool
    {
        return $this->preview_type == $name;
    }



    public function preview_matches($regexp):bool
    {
        return preg_match($regexp, $this->preview_type);
    }


    public function has_preview(string $type = null):bool
    {
        return $type ? (!empty($this->preview) && $this->preview_is($type)) : !empty($this->preview); 
    }
}
