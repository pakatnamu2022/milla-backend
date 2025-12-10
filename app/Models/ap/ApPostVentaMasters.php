<?php

namespace App\Models\ap;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApPostVentaMasters extends Model
{
  use SoftDeletes;

  protected $table = 'ap_post_venta_masters';

  protected $fillable = [
    'code',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['code', 'description'],
    'type' => 'in',
    'status' => '=',
  ];

  const sorts = [
    'code',
    'description',
    'type',
  ];

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }

  // Add status Order Work
  const OPENING_WORK_ORDER_ID = 19;
}
