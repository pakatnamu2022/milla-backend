<?php

namespace App\Models\tp;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Builder;

class Customer extends BaseModel
{
  protected $table = "rrhh_persona";

  protected $fillable = [
    'id',
    'vat',
    'nombre_completo',
    'sede_id',
    'jefe_id',
    'email',
    'email2',
    'email3',
  ];

  const filters = [
    'search' => ['nombre_completo', 'vat'],
    'vat' => 'like',
    'sede.empresa_id' => '=',
    'nombre_completo' => 'like',
    'cargo_id' => 'in',
    'status_id' => '=',
    'sede_id' => '=',
    'sede.departamento' => '=',
  ];

  const sorts = [
    'nombre_completo',
  ];

  protected static function booted()
  {
    static::addGlobalScope('working', function (Builder $builder) {
      $builder->where('status_deleted', 1)
        ->where('b_cliente', 1);
    });
  }

  public function scopeFromEmpresa($query, int $empresaId)
  {
    return $query->whereHas('sede', fn($q) => $q->where('empresa_id', $empresaId));
  }

  public function sede()
  {
    return $this->hasOne(Sede::class, 'id', 'sede_id');
  }
}
