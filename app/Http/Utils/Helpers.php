<?php

namespace App\Http\Utils;

use App\Models\gp\gestionsistema\District;
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

  public static function getDistrictFullLocation(int $districtId): ?string
  {
    $district = District::with('province.department')
      ->find($districtId);

    if (!$district) {
      return null;
    }

    return $district->name . ' - ' .
      $district->province->name . ' - ' .
      $district->province->department->name;
  }
}
