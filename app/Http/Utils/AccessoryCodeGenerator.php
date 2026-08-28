<?php

namespace App\Http\Utils;

use App\Models\ap\postventa\repuestos\ApprovedAccessoryPrice;
use Illuminate\Support\Str;

/**
 * Genera el código de un accesorio homologado a partir de su descripción.
 *
 * Regla: iniciales de las palabras significativas (se ignoran preposiciones y
 * artículos). Si la descripción tiene una sola palabra significativa, se toman
 * sus primeras 3 letras. El código resultante se valida contra las carrocerías
 * destino: si ya está en uso por otro accesorio en alguna de ellas, se agrega
 * un sufijo numérico (LS, LS2, LS3...) hasta encontrar uno libre para todas.
 */
class AccessoryCodeGenerator
{
  private const STOPWORDS = [
    'DE', 'DEL', 'LA', 'LAS', 'EL', 'LOS', 'Y', 'O', 'U', 'A', 'EN',
    'PARA', 'CON', 'SIN', 'POR', 'SOBRE', 'ANTE', 'TRAS', 'AL',
  ];

  /**
   * @param  string      $description
   * @param  array<int>  $bodyTypeIds  Carrocerías a las que aplicará el accesorio.
   * @param  int|null     $ignoreId     Accesorio a excluir de la verificación (update).
   */
  public static function generate(string $description, array $bodyTypeIds, ?int $ignoreId = null): string
  {
    $base = self::baseCode($description);

    $suffix = 0;
    do {
      $candidate = $suffix === 0 ? $base : $base . ($suffix + 1);
      $suffix++;
    } while (self::isTaken($candidate, $bodyTypeIds, $ignoreId));

    return $candidate;
  }

  private static function baseCode(string $description): string
  {
    $normalized = Str::upper(Str::ascii(trim($description)));
    $words = preg_split('/[^A-Z0-9]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

    $significant = array_values(array_filter(
      $words,
      fn ($word) => !in_array($word, self::STOPWORDS, true)
    ));

    if (count($significant) >= 2) {
      $code = implode('', array_map(fn ($word) => substr($word, 0, 1), $significant));
    } elseif (count($significant) === 1) {
      $code = substr($significant[0], 0, 3);
    } elseif (count($words) > 0) {
      $code = substr($words[0], 0, 3);
    } else {
      $code = 'ACC';
    }

    return substr($code, 0, 18);
  }

  /**
   * @param  array<int>  $bodyTypeIds
   */
  private static function isTaken(string $code, array $bodyTypeIds, ?int $ignoreId): bool
  {
    if (empty($bodyTypeIds)) {
      return false;
    }

    return ApprovedAccessoryPrice::query()
      ->whereIn('body_type_id', $bodyTypeIds)
      ->whereHas('approvedAccessory', function ($q) use ($code, $ignoreId) {
        $q->where('code', $code);
        if ($ignoreId) {
          $q->where('id', '!=', $ignoreId);
        }
      })
      ->exists();
  }
}
