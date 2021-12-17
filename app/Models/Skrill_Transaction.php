<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Skrill_Transaction extends Model
{
  protected $guarded = [];
  protected $table = 'skrill_transactions';
  public $timestamps = false;
}
