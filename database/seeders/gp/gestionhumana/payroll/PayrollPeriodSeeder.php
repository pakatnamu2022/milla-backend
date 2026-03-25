<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\gestionhumana\payroll\PayrollPeriod;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

/**
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollPeriodSeeder"
 */
class PayrollPeriodSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $companies = [1, 2, 3, 4];

    // Definir los periodos: Septiembre 2025 a Febrero 2026
    $periods = [
      ['year' => 2025, 'month' => 9],  // Septiembre 2025
      ['year' => 2025, 'month' => 10], // Octubre 2025
      ['year' => 2025, 'month' => 11], // Noviembre 2025
      ['year' => 2025, 'month' => 12], // Diciembre 2025
      ['year' => 2026, 'month' => 1],  // Enero 2026
      ['year' => 2026, 'month' => 2],  // Febrero 2026
    ];

    foreach ($companies as $companyId) {
      foreach ($periods as $index => $period) {
        $year = $period['year'];
        $month = $period['month'];

        // Calcular fechas
        $startDate = Carbon::create($year, $month, 1);
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Fecha quincenal (día 15 del mes)
        $biweeklyDate = Carbon::create($year, $month, 15);

        // Fecha de pago (día 5 del siguiente mes)
        $paymentDate = Carbon::create($year, $month, 1)->addMonth()->day(5);

        $data = [
          'code' => PayrollPeriod::generateCode($year, $month),
          'name' => PayrollPeriod::generateName($year, $month),
          'year' => $year,
          'month' => $month,
          'start_date' => $startDate,
          'end_date' => $endDate,
          'payment_date' => $paymentDate,
          'biweekly_date' => $biweeklyDate,
          'status' => PayrollPeriod::STATUS_CLOSED,
          'company_id' => $companyId,
        ];

        PayrollPeriod::updateOrCreate(
          [
            'code' => $data['code'],
            'company_id' => $companyId,
          ],
          $data
        );
      }
    }
  }
}
