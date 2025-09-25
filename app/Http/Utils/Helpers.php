<?php

namespace App\Http\Utils;

use Carbon\Carbon;

class Helpers
{
  /**
   * Obtiene el año actual
   */
  public static function getCurrentYear(): int
  {
    return Carbon::now()->year;
  }

  /**
   * Valida si una persona es mayor de edad
   */
  public static function isAdult(string $birthDate, int $adultAge = 18): bool
  {
    $birth = Carbon::parse($birthDate);
    $age = $birth->diffInYears(Carbon::now());

    return $age >= $adultAge;
  }
}
