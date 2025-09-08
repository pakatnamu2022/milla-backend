<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApClassArticle extends Model
{
  use SoftDeletes;

  protected $table = 'ap_class_article';

  protected $fillable = [
    'dyn_code',
    'description',
    'account',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['dyn_code', 'description', 'account', 'type'],
    'type' => '='
  ];

  const sorts = [
    'dyn_code',
    'description',
  ];

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setAccountAttribute($value)
  {
    $this->attributes['account'] = Str::upper(Str::ascii($value));
  }
}
