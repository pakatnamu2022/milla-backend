<?php

namespace App\Http\Services\ap\marketing\Concerns;

/**
 * Los campos de texto "libre" (digitados a mano) del módulo de Marketing se
 * guardan en MAYÚSCULAS y sin tildes, para evitar duplicados por capitalización
 * o acentuación inconsistente (ej. "Evento" vs "evento", "Difusión" vs "difusion").
 */
trait NormalizesUppercaseText
{
  protected function normalizeUpper(?string $value): ?string
  {
    if ($value === null) {
      return null;
    }

    $value = trim($value);
    if ($value === '') {
      return $value;
    }

    $accents = [
      'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
      'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
    ];

    return mb_strtoupper(strtr($value, $accents), 'UTF-8');
  }

  /**
   * Aplica normalizeUpper() a los campos indicados de $data, solo si están
   * presentes y son strings (no pisa valores nulos/ausentes).
   */
  protected function normalizeUpperFields(array $data, array $fields): array
  {
    foreach ($fields as $field) {
      if (array_key_exists($field, $data) && is_string($data[$field])) {
        $data[$field] = $this->normalizeUpper($data[$field]);
      }
    }

    return $data;
  }
}
