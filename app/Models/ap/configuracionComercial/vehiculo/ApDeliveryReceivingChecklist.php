<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApDeliveryReceivingChecklist extends Model
{
  use SoftDeletes;

  protected $table = 'ap_delivery_receiving_checklist';

  protected $fillable = [
    'id',
    'descripcion',
    'tipo',
    'categoria',
  ];

  const filters = [
    'search' => ['descripcion', 'tipo', 'categoria'],
  ];

  const sorts = [
    'descripcion',
    'tipo',
    'categoria',
  ];

  public function setDescripcionAttribute($value)
  {
    $this->attributes['descripcion'] = Str::upper(Str::ascii($value));
  }

  public function setTipoAttribute($value)
  {
    $this->attributes['tipo'] = Str::upper(Str::ascii($value));
  }

  public function setCategoriaAttribute($value)
  {
    $this->attributes['categoria'] = Str::upper(Str::ascii($value));
  }
}
