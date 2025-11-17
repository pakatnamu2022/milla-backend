<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use Illuminate\Support\Str;

class TypeOnboarding extends BaseModel
{
  protected $table = 'rrhh_tipo_onboarding';

  public $timestamps = false;

  protected $fillable = [
    'name',
    'status_deleted', // 1 activo, 0 eliminado
    'created_at',
    'updated_at',
  ];

  const filters = [
    'search' => ['name'],
    'name' => 'like',
    'status_deleted' => '=',
  ];

  const sorts = [
    'name' => 'asc',
    'status_deleted' => 'asc',
    'created_at' => 'asc',
  ];

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper($value);
  }

  public function positions()
  {
    return $this->hasMany(Position::class, 'tipo_onboarding_id');
  }
}
