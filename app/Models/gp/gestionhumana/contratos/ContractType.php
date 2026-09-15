<?php

namespace App\Models\gp\gestionhumana\contratos;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Catalogo de tipos de contrato, tabla legacy `rrhh_tipo_contrato`.
 * Equivale a Configuraciones/TipoContratoController del legacy web_millagp_2 (F5).
 */
class ContractType extends BaseModel
{
  use Reportable;

  protected $table = 'rrhh_tipo_contrato';

  protected $fillable = [
    'descripcion',
    'anios',
    'dias_vacaciones',
    'status_id',
    'write_id',
    'status_deleted',
  ];

  protected $casts = [
    'anios'           => 'integer',
    'dias_vacaciones' => 'decimal:2',
  ];

  const filters = [
    'search'      => ['descripcion'],
    'descripcion' => 'like',
  ];

  const sorts = ['id', 'descripcion', 'anios'];

  protected $reportColumns = [
    'id'              => ['label' => 'ID', 'width' => 8],
    'descripcion'     => ['label' => 'DESCRIPCIÓN', 'width' => 35],
    'anios'           => ['label' => 'AÑOS', 'width' => 10, 'formatter' => 'number'],
    'dias_vacaciones' => ['label' => 'DÍAS VACACIONES', 'width' => 15, 'formatter' => 'number'],
  ];

  protected static function booted(): void
  {
    static::addGlobalScope('active', fn(Builder $b) => $b->where('status_deleted', 1));
  }
}
