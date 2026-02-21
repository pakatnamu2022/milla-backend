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
      // DDT - Día Diurno Total
      ['code' => 'DDT', 'hour_type' => 'DIURNO',    'hours' => 12,   'multiplier' => 1,    'pay' => true,  'use_shift' => false],
      ['code' => 'DDT', 'hour_type' => 'REFRIGERIO', 'hours' => 0.75, 'multiplier' => 1,    'pay' => false, 'use_shift' => false],

      // DNT - Día Nocturno Total
      ['code' => 'DNT', 'hour_type' => 'NOCTURNO',  'hours' => 8,    'multiplier' => 1,    'pay' => true,  'use_shift' => false],
      ['code' => 'DNT', 'hour_type' => 'DIURNO',    'hours' => 3,    'multiplier' => 1,    'pay' => true,  'use_shift' => false],

      // FDT - Feriado Diurno Total
      ['code' => 'FDT', 'hour_type' => 'DIURNO',    'hours' => 12,   'multiplier' => 1,    'pay' => true,  'use_shift' => false],
      ['code' => 'FDT', 'hour_type' => 'REFRIGERIO', 'hours' => 0.75, 'multiplier' => 1,    'pay' => false, 'use_shift' => false],

      // FNT - Feriado Nocturno Total
      ['code' => 'FNT', 'hour_type' => 'NOCTURNO',  'hours' => 8,    'multiplier' => 1,    'pay' => true,  'use_shift' => false],
      ['code' => 'FNT', 'hour_type' => 'DIURNO',    'hours' => 3,    'multiplier' => 1,    'pay' => true,  'use_shift' => false],

      // DD - Domingo Diurno
      ['code' => 'DD',  'hour_type' => 'DIURNO',    'hours' => 8,    'multiplier' => 1,    'pay' => true,  'use_shift' => false],

      // DT - Diurno con horas extras
      ['code' => 'DT',  'hour_type' => 'DIURNO',    'hours' => 2,    'multiplier' => 1.25, 'pay' => true,  'use_shift' => false],
      ['code' => 'DT',  'hour_type' => 'DIURNO',    'hours' => 2,    'multiplier' => 1.35, 'pay' => true,  'use_shift' => false],
      ['code' => 'DT',  'hour_type' => 'REFRIGERIO', 'hours' => 0.75, 'multiplier' => 1.25, 'pay' => false, 'use_shift' => false],

      // NT - Nocturno con horas extras
      ['code' => 'NT',  'hour_type' => 'DIURNO',    'hours' => 2,    'multiplier' => 1,    'pay' => true,  'use_shift' => false],
      ['code' => 'NT',  'hour_type' => 'NOCTURNO',  'hours' => 6,    'multiplier' => 1,    'pay' => true,  'use_shift' => false],
      ['code' => 'NT',  'hour_type' => 'NOCTURNO',  'hours' => 2,    'multiplier' => 1.25, 'pay' => true,  'use_shift' => false],
      ['code' => 'NT',  'hour_type' => 'NOCTURNO',  'hours' => 1,    'multiplier' => 1.35, 'pay' => true,  'use_shift' => false],
    ];

    foreach ($rules as $rule) {
      AttendanceRule::withTrashed()->updateOrCreate(
        [
          'code'       => $rule['code'],
          'hour_type'  => $rule['hour_type'],
          'multiplier' => $rule['multiplier'],
          'hours'      => $rule['hours'],
        ],
        [
          'pay'        => $rule['pay'],
          'use_shift'  => $rule['use_shift'],
          'deleted_at' => null,
        ]
      );
    }
  }
}