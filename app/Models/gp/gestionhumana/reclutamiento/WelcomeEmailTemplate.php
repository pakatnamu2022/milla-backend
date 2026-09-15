<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;

/**
 * Plantilla de email de bienvenida (`config_onboarding`). Equivale a `TemplateOnboarding` del legacy.
 * Placeholders soportados en `contenido`: {$cargo} {$area} {$sede} {$postulante}.
 */
class WelcomeEmailTemplate extends BaseModel
{
  protected $table = 'config_onboarding';

  protected $fillable = [
    'asunto',
    'contenido',
    'status_deleted',
    'write_id',
  ];
}
