<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\AttendanceRule;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\AttendanceRuleSeeder"
 */
class AttendanceRuleSeeder extends Seeder
{
  public function run(): void
  {
    $rules = [
      // D - Turno Día
      ['code' => 'D', 'description' => 'TURNO DIA', 'hour_type' => 'DIURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => true],

      // N - Turno Noche
      ['code' => 'N', 'description' => 'TURNO NOCHE', 'hour_type' => 'NOCTURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => true],

      // DDT - Descanso Trabajado Día
      ['code' => 'DDT', 'description' => 'DESCANSO TRABAJADO DIA', 'hour_type' => 'DIURNO', 'hours' => 12, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],
      ['code' => 'DDT', 'description' => 'DESCANSO TRABAJADO DIA', 'hour_type' => 'REFRIGERIO', 'hours' => 0.75, 'multiplier' => 1, 'pay' => false, 'use_shift' => false],

      // DNT - Descanso Trabajado Noche
      ['code' => 'DNT', 'description' => 'DESCANSO TRABAJADO NOCHE', 'hour_type' => 'NOCTURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],
      ['code' => 'DNT', 'description' => 'DESCANSO TRABAJADO NOCHE', 'hour_type' => 'DIURNO', 'hours' => 3, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],

      // DD - Descanso
      ['code' => 'DD', 'description' => 'DESCANSO', 'hour_type' => 'DIURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],

      // FEN - Feriado Noche
      ['code' => 'FEN', 'description' => 'FERIADO NOCHE', 'hour_type' => 'NOCTURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],
      ['code' => 'FEN', 'description' => 'FERIADO NOCHE', 'hour_type' => 'DIURNO', 'hours' => 3, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],

      // FED - Feriado Diurno
      ['code' => 'FED', 'description' => 'FERIADO DIURNO', 'hour_type' => 'DIURNO', 'hours' => 12, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],
      ['code' => 'FED', 'description' => 'FERIADO DIURNO', 'hour_type' => 'REFRIGERIO', 'hours' => 0.75, 'multiplier' => 1, 'pay' => false, 'use_shift' => false],

      // LSGH - Licencia Sin Goce
      ['code' => 'LSGH', 'description' => 'LICENCIA S/GOCE', 'hour_type' => 'DIURNO', 'hours' => 8, 'multiplier' => 0, 'pay' => false, 'use_shift' => false],

      // LCGH - Licencia Con Goce
      ['code' => 'LCGH', 'description' => 'LICENCIA C/GOCE', 'hour_type' => 'DIURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],

      // VC - Vacaciones
      ['code' => 'VC', 'description' => 'VACACIONES', 'hour_type' => 'DIURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],

      // DM - Descanso Médico
      ['code' => 'DM', 'description' => 'DESCANSO MEDICO', 'hour_type' => 'DIURNO', 'hours' => 8, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],

      // DT - Diurno con horas extras
      ['code' => 'DT', 'description' => 'TURNO DIA CON HORAS EXTRAS', 'hour_type' => 'DIURNO', 'hours' => 2, 'multiplier' => 1.25, 'pay' => true, 'use_shift' => false],
      ['code' => 'DT', 'description' => 'TURNO DIA CON HORAS EXTRAS', 'hour_type' => 'DIURNO', 'hours' => 2, 'multiplier' => 1.35, 'pay' => true, 'use_shift' => false],
      ['code' => 'DT', 'description' => 'TURNO DIA CON HORAS EXTRAS', 'hour_type' => 'REFRIGERIO', 'hours' => 0.75, 'multiplier' => 1.25, 'pay' => false, 'use_shift' => false],

      // NT - Nocturno con horas extras
      ['code' => 'NT', 'description' => 'TURNO NOCHE CON HORAS EXTRAS', 'hour_type' => 'DIURNO', 'hours' => 2, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],
      ['code' => 'NT', 'description' => 'TURNO NOCHE CON HORAS EXTRAS', 'hour_type' => 'NOCTURNO', 'hours' => 6, 'multiplier' => 1, 'pay' => true, 'use_shift' => false],
      ['code' => 'NT', 'description' => 'TURNO NOCHE CON HORAS EXTRAS', 'hour_type' => 'NOCTURNO', 'hours' => 2, 'multiplier' => 1.25, 'pay' => true, 'use_shift' => false],
      ['code' => 'NT', 'description' => 'TURNO NOCHE CON HORAS EXTRAS', 'hour_type' => 'NOCTURNO', 'hours' => 1, 'multiplier' => 1.35, 'pay' => true, 'use_shift' => false],
    ];

    foreach ($rules as $rule) {
      AttendanceRule::withTrashed()->updateOrCreate(
        [
          'code' => $rule['code'],
          'hour_type' => $rule['hour_type'],
          'multiplier' => $rule['multiplier'],
          'hours' => $rule['hours'],
        ],
        [
          'description' => $rule['description'],
          'pay' => $rule['pay'],
          'use_shift' => $rule['use_shift'],
          'deleted_at' => null,
        ]
      );
    }
  }
}
