<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_Subscription extends Model
{
		public $timestamps = false;
    protected $table = "user_subscription";
    protected $guarded = [];
}
