<?php

namespace Database\Seeders\gp\gestionhumana\payroll;

use App\Models\gp\GpMasters;
use Illuminate\Database\Seeder;

/**
 * Catálogo de tipos de bono/comisión (gh_payroll_bonuses.type_id). Por ahora solo el bono de
 * conductores (comisión variable mensual que sí se incluye en la remuneración computable de
 * gratificación/CTS, ver PayrollCalculation::calcularPromedioUltimos6Meses()) — se puede
 * ampliar con más códigos cuando aparezcan otros tipos de bono.
 *
 * php artisan db:seed --class="Database\Seeders\gp\gestionhumana\payroll\PayrollBonusTypeSeeder"
 */
class PayrollBonusTypeSeeder extends Seeder
{
    const string TYPE = 'PAYROLL_BONUS';

    public function run(): void
    {
        $codes = [
            'BONO_CONDUCTOR' => 'Bono Conductores',
        ];

        foreach ($codes as $code => $description) {
            GpMasters::updateOrCreate(
                ['code' => $code, 'type' => self::TYPE],
                ['description' => $description, 'status' => 1],
            );
        }
    }
}
