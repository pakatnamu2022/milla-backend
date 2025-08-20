<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApBrand extends Model
{
  use SoftDeletes;

  protected $table = 'ap_marca_vehiculo';

  protected $fillable = [
    'id',
    'codigo',
    'codigo_dyn',
    'grupo_id',
    'name',
    'descripcion',
    'logo',
    'logo_min',
    'Responsable',
  ];

  const filters = [
    'search' => ['codigo', 'codigo_dyn', 'name', 'descripcion', 'Responsable'],
  ];

  const sorts = [
    'id',
    'codigo',
    'codigo_dyn',
    'name',
    'descripcion',
    'Responsable',
  ];
}
