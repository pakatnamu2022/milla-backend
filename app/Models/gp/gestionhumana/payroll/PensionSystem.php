<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;

/**
 * Sistema de pensiones (ONP / AFP) al que puede estar afiliado un trabajador.
 * Tabla legacy `rrhh_sist_pensiones`, referenciada por `rrhh_persona.sis_pensiones_id`.
 * Trae las tasas vigentes: obl (aporte obligatorio), prima_seg (prima de seguro),
 * com_var (comisión variable) y sueldo_max (tope, cuando aplica).
 */
class PensionSystem extends BaseModel
{
  protected $table = 'rrhh_sist_pensiones';

  protected $fillable = [
    'name',
    'tipo',
    'obl',
    'prima_seg',
    'com_var',
    'sueldo_max',
    'status_deleted',
  ];

  protected $casts = [
    'obl' => 'decimal:4',
    'prima_seg' => 'decimal:4',
    'com_var' => 'decimal:4',
    'sueldo_max' => 'decimal:2',
  ];

  const string TYPE_ONP = 'ONP';

  public function isOnp(): bool
  {
    return strtoupper($this->tipo ?? $this->name ?? '') === self::TYPE_ONP;
  }
}
