<?php

namespace App\Enums;

/**
 * Prioridad de un proceso de postulación (`rrhh_proceso_postulacion.prioridad`).
 * Mayor valor = mayor prioridad, por lo que ORDER BY prioridad DESC prioriza.
 */
enum RecruitmentPriority: int
{
  case LOW = 0;
  case MEDIUM = 1;
  case HIGH = 2;

  public function label(): string
  {
    return match ($this) {
      self::LOW => 'Baja',
      self::MEDIUM => 'Media',
      self::HIGH => 'Alta',
    };
  }
}
