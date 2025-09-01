<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;

class Sede extends BaseModel
{
  protected $table = 'config_sede';
  public $timestamps = false;

  protected $fillable = [
    'localidad',
    'suc_abrev',
    'abreviatura',
    'empresa_id',
    'ruc',
    'razon_social',
    'direccion',
    'distrito',
    'provincia',
    'departamento',
    'web',
    'email',
    'logo',
    'ciudad',
    'info_labores'
  ];

  const filters = [
    'id' => '=',
    'search' => ['suc_abrev', 'abreviatura', 'razon_social', 'direccion', 'ciudad'],
    'empresa_id' => '=',
  ];

  const sorts = [
    'id',
    'suc_abrev',
    'abreviatura',
    'razon_social',
    'direccion',
    'ciudad',
  ];

  public function areas()
  {
    //return $this->belongsTo(Area::class);
    return $this->hasMany(Area::class, 'sede_id', 'id');
  }

  public function company()
  {
    return $this->belongsTo(Company::class, 'empresa_id', 'id');
  }

  public function asesores()
  {
    return $this->belongsToMany(Person::class, 'ap_assign_sede', 'sede_id', 'asesor_id')
      ->withTimestamps();
  }
}
