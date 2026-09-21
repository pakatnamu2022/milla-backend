<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\BaseModel;

/**
 * Catálogo legacy `rrhh_tipo_vacaciones` (ASIGNADA, VENDIDA, ADELANTADA, A CUENTA, PROGRAMADA).
 */
class VacationType extends BaseModel
{
  protected $table = 'rrhh_tipo_vacaciones';

  protected $fillable = ['name'];
}
