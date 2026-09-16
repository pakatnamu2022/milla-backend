<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;

/**
 * Mensaje automático parametrizable por estado de postulante
 * (`rrhh_mensaje_estado`), editable por Gestión Humana. Uno por cada valor
 * de `Applicant::TIPO_*`. Placeholders soportados en `contenido`, igual que
 * `OfferLetterTemplate`: {$postulante} {$cargo} {$area} {$sede} {$proceso}.
 */
class ApplicantStatusMessageTemplate extends BaseModel
{
  protected $table = 'rrhh_mensaje_estado';

  protected $fillable = [
    'tipo_trabajador_id',
    'asunto',
    'contenido',
    'activo',
  ];

  protected $casts = [
    'activo' => 'boolean',
  ];
}
