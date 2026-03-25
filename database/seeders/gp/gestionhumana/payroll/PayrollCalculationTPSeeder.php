<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use Illuminate\Database\Seeder;

/**
 * Seeder para PayrollCalculation - Empresa TP (company_id = 1)
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollCalculationTPSeeder"
 */
class PayrollCalculationTPSeeder extends Seeder
{
  // CONFIGURACIÓN DE PERIOD_IDs
  // Si en producción los IDs son diferentes, modifica estos valores
  const PERIOD_1 = 12; // Periodo 12
  const PERIOD_2 = 13; // Periodo 13
  const PERIOD_3 = 14; // Periodo 14
  const PERIOD_4 = 15; // Periodo 15
  const PERIOD_5 = 16; // Periodo 16
  const PERIOD_6 = 17; // Periodo 17

  // Constantes de la empresa
  const COMPANY_ID = 1; // TP
  const SEDE_ID = 1;

  const STATUS = 'PAID';

  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $calculations = [
      // PERIOD 1 (Periodo 24)
      [
        'worker_id' => 7246,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 210.109375,
        'overtime_35' => 241.5375,
        'holiday_pay' => 0,
        'compensatory_pay' => 129.95,
        'night_bonus' => 165.748333333333,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7904,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 113.588541666667,
        'overtime_35' => 139.8375,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 59.3316666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 2 (Periodo 25)
      [
        'worker_id' => 7246,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 209.815104166667,
        'overtime_35' => 235.18125,
        'holiday_pay' => 105.9375,
        'compensatory_pay' => 0,
        'night_bonus' => 163.649166666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7904,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 210.9921875,
        'overtime_35' => 260.60625,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 0,
        'night_bonus' => 136.089166666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 3 (Periodo 26)
      [
        'worker_id' => 7246,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 97.109375,
        'overtime_35' => 114.4125,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 0,
        'night_bonus' => 86.645,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7904,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 194.21875,
        'overtime_35' => 228.825,
        'holiday_pay' => 105.9375,
        'compensatory_pay' => 0,
        'night_bonus' => 133.99,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 4 (Periodo 27)
      [
        'worker_id' => 7246,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 194.807291666667,
        'overtime_35' => 241.5375,
        'holiday_pay' => 259.9,
        'compensatory_pay' => 0,
        'night_bonus' => 153.518333333333,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7904,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 80.6302083333333,
        'overtime_35' => 88.9875,
        'holiday_pay' => 211.875,
        'compensatory_pay' => 0,
        'night_bonus' => 89.9783333333335,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 5 (Periodo 28)
      [
        'worker_id' => 7246,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 306.041666666667,
        'overtime_35' => 254.25,
        'holiday_pay' => 105.9375,
        'compensatory_pay' => 0,
        'night_bonus' => 133.986666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7904,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 321.34375,
        'overtime_35' => 241.5375,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 0,
        'night_bonus' => 165.751666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 6 (Periodo 29)
      [
        'worker_id' => 7246,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 289.5625,
        'overtime_35' => 228.825,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 118.663333333334,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_6,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7904,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 289.5625,
        'overtime_35' => 228.825,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 118.663333333334,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_6,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
    ];

    // Insertar o actualizar los datos sin duplicados
    foreach ($calculations as $calculation) {
      PayrollCalculation::updateOrCreate(
        [
          'worker_id' => $calculation['worker_id'],
          'period_id' => $calculation['period_id'],
          'company_id' => $calculation['company_id'],
        ],
        $calculation
      );
    }
  }
}
