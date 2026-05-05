<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollCalculation;
use Illuminate\Database\Seeder;

/**
 * Seeder para PayrollCalculation - Empresa GP (company_id = 4)
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollCalculationGPSeeder"
 */
class PayrollCalculationGPSeeder extends Seeder
{
  // CONFIGURACIÓN DE PERIOD_IDs
  // Si en producción los IDs son diferentes, modifica estos valores
  const PERIOD_1 = 30; // Septiembre 2025
  const PERIOD_2 = 31; // Octubre 2025
  const PERIOD_3 = 32; // Noviembre 2025
  const PERIOD_4 = 33; // Diciembre 2025
  const PERIOD_5 = 34; // Enero 2026
  const PERIOD_6 = 35; // Febrero 2026

  // Constantes de la empresa
  const COMPANY_ID = 4; // GP
  const SEDE_ID = 3;

  const STATUS = 'PAID';

  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $calculations = [
      // PERIOD 1 (Septiembre 2025)
      [
        'worker_id' => 5044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 0,
        'overtime_35' => 0,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 319.283854166667,
        'overtime_35' => 235.18125,
        'holiday_pay' => 0,
        'compensatory_pay' => 365.8375,
        'night_bonus' => 218.235833333333,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7156,
        'salary' => 1700,
        'shift_hours' => 12,
        'overtime_25' => 243.489583333333,
        'overtime_35' => 420.75,
        'holiday_pay' => 0,
        'compensatory_pay' => 159.375,
        'night_bonus' => 23.0333333333333,
        'base_hour_value' => 7.08,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5045,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 286.325520833333,
        'overtime_35' => 241.5375,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 118.666666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5039,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 0,
        'overtime_35' => 0,
        'holiday_pay' => 233.34,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 8881,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 303.393229166667,
        'overtime_35' => 228.825,
        'holiday_pay' => 0,
        'compensatory_pay' => 235.8875,
        'night_bonus' => 181.046666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_1,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 2 (Octubre 2025)
      [
        'worker_id' => 5044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 285.1484375,
        'overtime_35' => 260.60625,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 0,
        'night_bonus' => 136.084166666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 184.802083333333,
        'overtime_35' => 171.61875,
        'holiday_pay' => 105.9375,
        'compensatory_pay' => 211.875,
        'night_bonus' => 115.125833333333,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7156,
        'salary' => 1700,
        'shift_hours' => 12,
        'overtime_25' => 199.21875,
        'overtime_35' => 344.25,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 7.08,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5045,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 326.640625,
        'overtime_35' => 247.89375,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 148.325833333334,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5039,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 0,
        'overtime_35' => 0,
        'holiday_pay' => 233.34,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 8881,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 303.393229166667,
        'overtime_35' => 228.825,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 129.95,
        'night_bonus' => 193.055,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_2,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 3 (Noviembre 2025)
      [
        'worker_id' => 5044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 244.833333333333,
        'overtime_35' => 254.25,
        'holiday_pay' => 105.9375,
        'compensatory_pay' => 0,
        'night_bonus' => 94.4200000000001,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 286.325520833333,
        'overtime_35' => 241.5375,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 365.8375,
        'night_bonus' => 203.876666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7156,
        'salary' => 1700,
        'shift_hours' => 12,
        'overtime_25' => 221.354166666667,
        'overtime_35' => 382.5,
        'holiday_pay' => 159.375,
        'compensatory_pay' => 0,
        'night_bonus' => 23.04,
        'base_hour_value' => 7.08,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5045,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 294.859375,
        'overtime_35' => 235.18125,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 0,
        'night_bonus' => 155.859166666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5039,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 0,
        'overtime_35' => 0,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 8881,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 286.325520833333,
        'overtime_35' => 241.5375,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 0,
        'night_bonus' => 145.971666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_3,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 4 (Diciembre 2025)
      [
        'worker_id' => 5044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 296.036458333333,
        'overtime_35' => 216.1125,
        'holiday_pay' => 365.8375,
        'compensatory_pay' => 129.95,
        'night_bonus' => 235.663333333333,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 338.705729166667,
        'overtime_35' => 184.33125,
        'holiday_pay' => 235.8875,
        'compensatory_pay' => 389.85,
        'night_bonus' => 312.405833333334,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7156,
        'salary' => 1700,
        'shift_hours' => 12,
        'overtime_25' => 143.880208333333,
        'overtime_35' => 248.625,
        'holiday_pay' => 159.375,
        'compensatory_pay' => 162.9375,
        'night_bonus' => 46.0500000000001,
        'base_hour_value' => 7.08,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5045,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 280.145833333333,
        'overtime_35' => 209.75625,
        'holiday_pay' => 341.825,
        'compensatory_pay' => 0,
        'night_bonus' => 186.469166666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5039,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 0,
        'overtime_35' => 0,
        'holiday_pay' => 233.33,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 8881,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 278.96875,
        'overtime_35' => 228.825,
        'holiday_pay' => 235.8875,
        'compensatory_pay' => 0,
        'night_bonus' => 161.278333333334,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_4,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 5 (Enero 2026)
      [
        'worker_id' => 5044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 310.75,
        'overtime_35' => 241.5375,
        'holiday_pay' => 105.9375,
        'compensatory_pay' => 0,
        'night_bonus' => 153.741666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 286.325520833333,
        'overtime_35' => 241.5375,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 129.95,
        'night_bonus' => 173.28,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7156,
        'salary' => 1700,
        'shift_hours' => 12,
        'overtime_25' => 289.973958333333,
        'overtime_35' => 454.041666666667,
        'holiday_pay' => 155.833333333333,
        'compensatory_pay' => 0,
        'night_bonus' => 21.2666666666667,
        'base_hour_value' => 7.08,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5045,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 277.791666666667,
        'overtime_35' => 247.89375,
        'holiday_pay' => 105.9375,
        'compensatory_pay' => 0,
        'night_bonus' => 124.079166666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5039,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 0,
        'overtime_35' => 0,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 8881,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 310.75,
        'overtime_35' => 241.5375,
        'holiday_pay' => 129.95,
        'compensatory_pay' => 0,
        'night_bonus' => 165.746666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_5,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],

      // PERIOD 6 (Febrero 2026)
      [
        'worker_id' => 5044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 287.502604166667,
        'overtime_35' => 222.46875,
        'holiday_pay' => 0,
        'compensatory_pay' => 129.95,
        'night_bonus' => 155.855833333334,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_6,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7044,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 270.434895833333,
        'overtime_35' => 235.18125,
        'holiday_pay' => 0,
        'compensatory_pay' => 389.85,
        'night_bonus' => 190.690833333334,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_6,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 7156,
        'salary' => 1700,
        'shift_hours' => 12,
        'overtime_25' => 154.947916666667,
        'overtime_35' => 267.75,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 7.08,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_6,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5045,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 146.841145833333,
        'overtime_35' => 127.125,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 59.3316666666667,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_6,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 5039,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 0,
        'overtime_35' => 0,
        'holiday_pay' => 0,
        'compensatory_pay' => 0,
        'night_bonus' => 0,
        'base_hour_value' => 4.71,
        'sede_id' => self::SEDE_ID,
        'period_id' => self::PERIOD_6,
        'biweekly' => null,
        'company_id' => self::COMPANY_ID,
        'status' => self::STATUS
      ],
      [
        'worker_id' => 8881,
        'salary' => 1130,
        'shift_hours' => 12,
        'overtime_25' => 271.611979166667,
        'overtime_35' => 216.1125,
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
