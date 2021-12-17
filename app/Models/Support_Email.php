<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Support_Email extends Model
{
    protected $table 		= 'support_email';
    public $timestamps 	= false;
    protected $fillable = ['email'];
}
