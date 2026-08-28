<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\GpMasters;
use Illuminate\Database\Seeder;

/**
 * Catálogo de tipos de "BB.SS. truncos" (gh_payroll_liquidation_bbss.type_id).
 * RRHH carga los montos manualmente en el módulo "Liquidación BB.SS." eligiendo
 * uno de estos tipos; PayrollRegisterService::generate() los suma por código.
 *
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollLiquidationBbssTypeSeeder"
 */
class PayrollLiquidationBbssTypeSeeder extends Seeder
{
  const string TYPE = 'LIQUIDATION_BBSS';

  public function run(): void
  {
    $codes = [
      'CTS_TRUNCADA' => 'CTS Truncada',
      'GRATIFICACION_TRUNCADA' => 'Gratificación Truncada',
      'BONIFICACION_EXTRAORDINARIA' => 'Bonificación Extraordinaria',
      'VACACIONES_TRUNCADAS' => 'Vacaciones Truncadas',
      'AGUINALDO' => 'Aguinaldo',
      'GRATIFICACION_NAVIDAD' => 'Gratificación x Navidad',
      'BONIF_EXTRAORD_NAVIDAD' => 'Bonificación Extraordinaria 9% (Navidad)',
    ];

    foreach ($codes as $code => $description) {
      GpMasters::updateOrCreate(
        ['code' => $code, 'type' => self::TYPE],
        ['description' => $description, 'status' => 1],
      );
    }
  }
}
