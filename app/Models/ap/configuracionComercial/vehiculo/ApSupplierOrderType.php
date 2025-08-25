<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApSupplierOrderType extends Model
{
  use SoftDeletes;

  protected $table = 'tipo_pedido';

  protected $fillable = [
    'id',
    'codigo',
    'descripcion',
    'status',
  ];

  const filters = [
    'search' => ['codigo', 'descripcion'],
  ];

  const sorts = [
    'codigo',
    'descripcion',
  ];

  public function setCodigoAttribute($value)
  {
    $this->attributes['codigo'] = Str::upper(Str::ascii($value));
  }

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper(Str::ascii($value));
  }
}
