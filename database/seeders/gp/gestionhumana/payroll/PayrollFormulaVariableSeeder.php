<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollFormulaVariable;
use Illuminate\Database\Seeder;

/**
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollFormulaVariableSeeder"
 */
class PayrollFormulaVariableSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $variables = [
      // Fixed values (Peru 2026)
      [
        'code' => 'MIN_WAGE',
        'name' => 'Minimum Wage',
        'description' => 'Peru minimum wage (RMV)',
        'type' => 'FIXED',
        'value' => 1025.0000,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'UIT',
        'name' => 'Tax Unit',
        'description' => 'Peru tax unit (UIT)',
        'type' => 'FIXED',
        'value' => 5150.0000,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'ESSALUD_RATE',
        'name' => 'EsSalud Rate',
        'description' => 'Employer health insurance rate',
        'type' => 'FIXED',
        'value' => 0.0900,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'AFP_RATE',
        'name' => 'AFP Rate',
        'description' => 'Pension fund contribution rate',
        'type' => 'FIXED',
        'value' => 0.1000,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'ONP_RATE',
        'name' => 'ONP Rate',
        'description' => 'National pension system rate',
        'type' => 'FIXED',
        'value' => 0.1300,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'NIGHT_BONUS_RATE',
        'name' => 'Night Bonus Rate',
        'description' => 'Night shift bonus percentage',
        'type' => 'FIXED',
        'value' => 0.3500,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'OVERTIME_25_RATE',
        'name' => 'Overtime 25% Rate',
        'description' => 'First 2 hours overtime multiplier',
        'type' => 'FIXED',
        'value' => 1.2500,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'OVERTIME_35_RATE',
        'name' => 'Overtime 35% Rate',
        'description' => 'Additional overtime multiplier',
        'type' => 'FIXED',
        'value' => 1.3500,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'HOLIDAY_RATE',
        'name' => 'Holiday Rate',
        'description' => 'Holiday work multiplier',
        'type' => 'FIXED',
        'value' => 2.0000,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'CTS_RATE',
        'name' => 'CTS Rate',
        'description' => 'Severance compensation rate (monthly)',
        'type' => 'FIXED',
        'value' => 0.0833,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'GRATIFICATION_RATE',
        'name' => 'Gratification Rate',
        'description' => 'Bonus rate (monthly provision)',
        'type' => 'FIXED',
        'value' => 0.1667,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],
      [
        'code' => 'VACATIONS_RATE',
        'name' => 'Vacations Rate',
        'description' => 'Vacation provision rate (monthly)',
        'type' => 'FIXED',
        'value' => 0.0833,
        'source_field' => null,
        'formula' => null,
        'active' => true,
      ],

      // Calculated variables (resolved at runtime)
      [
        'code' => 'HOURLY_RATE',
        'name' => 'Hourly Rate',
        'description' => 'Calculated from base salary',
        'type' => 'CALCULATED',
        'value' => null,
        'source_field' => null,
        'formula' => 'SUELDO / 30 / 8',
        'active' => true,
      ],
      [
        'code' => 'DAILY_RATE',
        'name' => 'Daily Rate',
        'description' => 'Calculated from base salary',
        'type' => 'CALCULATED',
        'value' => null,
        'source_field' => null,
        'formula' => 'SUELDO / 30',
        'active' => true,
      ],
    ];

    foreach ($variables as $variable) {
      PayrollFormulaVariable::updateOrCreate(
        ['code' => $variable['code']],
        $variable
      );
    }
  }
}
