<?php

namespace App\Models\gp\gestionhumana\reclutamiento;

use App\Models\BaseModel;

/**
 * Plantilla de carta oferta (`config_mail_carta`). Equivale a `TemplateCartaOferta` del legacy.
 * Placeholders soportados en `contenido`: {$cargo} {$area} {$sede} {$postulante}.
 */
class OfferLetterTemplate extends BaseModel
{
  protected $table = 'config_mail_carta';

  protected $fillable = [
    'asunto',
    'contenido',
  ];
}
