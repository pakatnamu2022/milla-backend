<?php

namespace App\Models\ap\maestroGeneral;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TypeCurrency extends Model
{
  use SoftDeletes;

  protected $table = 'type_currency';

  protected $fillable = [
    'id',
    'code',
    'name',
    'symbol',
    'status',
  ];

  const filters = [
    'search' => ['code', 'name'],
    'status' => '=',
  ];

  const sorts = [
    'code',
    'name',
  ];

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper(Str::ascii($value));
  }

  public function setSymbolAttribute($value)
  {
    $this->attributes['symbol'] = Str::upper(Str::ascii($value));
  }
}
