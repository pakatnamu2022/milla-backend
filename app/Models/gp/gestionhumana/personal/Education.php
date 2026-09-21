<?php

namespace App\Models\gp\gestionhumana\personal;

use App\Models\BaseModel;

/**
 * Catálogo legacy `rrhh_estudio` (nivel de estudios: PRIMARIA COMPLETA, SECUNDARIA INCOMPLETA, etc.).
 * `rrhh_persona.estudios_id` apunta aquí. Lleva `status_deleted` legacy (1 = activo).
 */
class Education extends BaseModel
{
  protected $table = 'rrhh_estudio';

  protected $fillable = ['nombre'];
}
