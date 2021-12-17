<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Notification extends Model
{
  protected static function notifyUsers($product_id, $user_id, $for)
  {
    if($for === 0)
    {
      \DB::insert("INSERT INTO notifications (`product_id`, `users_ids`, `for`) 
                  SELECT ?, CONCAT('|', GROUP_CONCAT(CONCAT(user_id, ':0') SEPARATOR '|'), '|'), 0
                  FROM transactions USE INDEX (products_ids) WHERE products_ids LIKE CONCAT('\'%', ?, '%\'')", [$product_id, $product_id]);
    }
    else
    {
      \DB::insert("INSERT INTO notifications (`product_id`, `users_ids`, `for`) VALUES (?, ?, ?)", 
                  [$product_id, "|{$user_id}:0|", $for]);
    }
  }
}