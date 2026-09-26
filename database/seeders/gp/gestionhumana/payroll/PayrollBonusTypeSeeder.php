<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\GpMasters;
use Illuminate\Database\Seeder;

/**
 * Catálogo de tipos de bono/comisión. Dos "type" distintos en gp_masters porque ya existían
 * separados en el código al momento de escribir este seeder:
 * - PAYROLL_BONUS (histórico): usado solo por PayrollHistoricalBonusImport para el bono de
 *   conductores que entra en el promedio de gratificación/CTS.
 * - PAYROLL_BUNESES: el que consulta el módulo de Bonificaciones (BonusForm/PayrollBonusImport)
 *   para el selector de "Tipo" — aquí va el bono de producción de Transportes Pakatnamú.
 *
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollBonusTypeSeeder"
 */
class PayrollBonusTypeSeeder extends Seeder
{
    const string TYPE_HISTORICAL = 'PAYROLL_BONUS';
    const string TYPE_BONUS = 'PAYROLL_BUNESES';

    public function run(): void
    {
        $historicalCodes = [
            'BONO_CONDUCTOR' => 'Bono Conductores',
        ];

        foreach ($historicalCodes as $code => $description) {
            GpMasters::updateOrCreate(
                ['code' => $code, 'type' => self::TYPE_HISTORICAL],
                ['description' => $description, 'status' => 1],
            );
        }

        $bonusCodes = [
            'BONO_PRODUCCION' => 'Bono de Producción',
        ];

        foreach ($bonusCodes as $code => $description) {
            GpMasters::updateOrCreate(
                ['code' => $code, 'type' => self::TYPE_BONUS],
                ['description' => $description, 'status' => 1],
            );
        }
    }
}
