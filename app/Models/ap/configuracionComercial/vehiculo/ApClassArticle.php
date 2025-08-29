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
    'codigo_dyn',
    'descripcion',
    'cuenta',
    'tipo',
    'status',
  ];

  const filters = [
    'search' => ['codigo_dyn', 'descripcion', 'cuenta', 'tipo'],
    'tipo' => '='
  ];

  const sorts = [
    'codigo_dyn',
    'descripcion',
  ];

  public function setCodigoDynAttribute($value)
  {
    $this->attributes['codigo_dyn'] = Str::upper(Str::ascii($value));
  }

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper(Str::ascii($value));
  }

  public function setCuentaAttribute($value)
  {
    $this->attributes['cuenta'] = Str::upper(Str::ascii($value));
  }
}
