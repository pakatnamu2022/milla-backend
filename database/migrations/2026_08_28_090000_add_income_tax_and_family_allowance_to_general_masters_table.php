<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data de referencia para Renta de 5ta categoría (proyección anual simplificada)
 * y Asignación Familiar, faltantes en `general_masters` (entregable planilla marzo/26).
 *
 * UIT = 5150 (consistente con gh_payroll_formula_variables.UIT).
 * Asignación Familiar = 10% de la RMV (id=18, valor 1130) = 113.
 * Deducción de 7 UIT antes de aplicar los tramos progresivos de renta de 5ta
 * (los tramos en sí -8/14/17/20/30% sobre 5/20/35/45 UIT- son ley y van
 * hardcodeados en PayrollRegisterService, no cambian por empresa/periodo).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('general_masters')->insert([
            [
                'id' => 58,
                'code' => 'UIT',
                'description' => 'UNIDAD IMPOSITIVA TRIBUTARIA',
                'type' => 'SPREADSHEET_PARAMETERS',
                'value' => '5150',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 59,
                'code' => 'FAMILY_ALLOWANCE',
                'description' => 'ASIGNACION FAMILIAR (10% RMV)',
                'type' => 'SPREADSHEET_PARAMETERS',
                'value' => '113',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 60,
                'code' => 'INCOME_TAX_DEDUCTION_UIT',
                'description' => 'DEDUCCION RENTA 5TA (EN UIT)',
                'type' => 'SPREADSHEET_PARAMETERS',
                'value' => '7',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('general_masters')->whereIn('id', [58, 59, 60])->delete();
    }
};
