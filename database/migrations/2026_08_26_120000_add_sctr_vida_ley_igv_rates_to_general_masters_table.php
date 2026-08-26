<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aportes SCTR / EsSalud / Vida Ley (entregable cronograma 17/08-28/08/26).
 *
 * `general_masters` ya traía sembrada la tabla "INFORMACION REFERENCIAL" del Excel
 * de planillas (RMV id=18, ES/EsSalud id=19, ES-VI id=20, SCTR id=21, RMA id=22),
 * pero el id=21 (SCTR) es un solo valor en 0 y el sistema real reporta SCTR salud
 * y SCTR pensión por separado (0.50% cada uno, confirmado en
 * "SCTR JUNIO 2026 TP SAC.xls"). El id=20 (ES-VI = "ESSALUD-VIDA") estaba en 0 sin
 * usar en ningún lado del código — se reutiliza como tasa de Vida Ley (3.12%,
 * confirmado en "CALCULO VIDA LEY TP - POR PERSONA POLIZA 2025-2026.xlsx": costo
 * por persona = sueldo básico x 3.12%, + IGV, prorrateado a 12 meses).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ES-VI ya existía sin uso real -> se reutiliza como tasa Vida Ley.
        DB::table('general_masters')
            ->where('id', 20)
            ->update([
                'description' => 'ESSALUD-VIDA (TASA SEGURO VIDA LEY)',
                'value' => '0.0312',
                'updated_at' => now(),
            ]);

        DB::table('general_masters')->insert([
            [
                'id' => 55,
                'code' => 'SCTR SALUD',
                'description' => 'SCTR SALUD',
                'type' => 'SPREADSHEET_PARAMETERS',
                'value' => '0.0050',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 56,
                'code' => 'SCTR PENSION',
                'description' => 'SCTR PENSION',
                'type' => 'SPREADSHEET_PARAMETERS',
                'value' => '0.0050',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 57,
                'code' => 'IGV',
                'description' => 'IMPUESTO GENERAL A LAS VENTAS',
                'type' => 'SPREADSHEET_PARAMETERS',
                'value' => '0.18',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('general_masters')->whereIn('id', [55, 56, 57])->delete();

        DB::table('general_masters')
            ->where('id', 20)
            ->update([
                'description' => 'ESSALUD-VIDA',
                'value' => '0',
                'updated_at' => now(),
            ]);
    }
};
