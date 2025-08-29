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
    'codigo',
    'nombre',
    'simbolo',
    'status',
  ];

  const filters = [
    'search' => ['codigo', 'nombre'],
    'status' => '=',
  ];

  const sorts = [
    'codigo',
    'nombre',
  ];

  public function setCodigoAttribute($value)
  {
    $this->attributes['codigo'] = Str::upper(Str::ascii($value));
  }

  public function setNombreAttribute($value)
  {
    $this->attributes['nombre'] = Str::upper(Str::ascii($value));
  }

  public function setSimboloAttribute($value)
  {
    $this->attributes['simbolo'] = Str::upper(Str::ascii($value));
  }
}
