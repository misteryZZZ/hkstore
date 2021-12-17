<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Newsletter_Subscriber extends Model
{
		protected $table = 'newsletter_subscribers';
    protected $fillable = ['email'];
}
