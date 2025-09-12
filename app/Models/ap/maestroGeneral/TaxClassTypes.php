<?php

namespace App\Models\ap\maestroGeneral;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaxClassTypes extends Model
{
  protected $table = 'tax_class_types';

  protected $fillable = [
    'dyn_code',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['dyn_code', 'description', 'type'],
    'type' => '=',
    'status' => '='
  ];

  const sorts = [
    'dyn_code',
    'description',
    'type',
  ];

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }
}
