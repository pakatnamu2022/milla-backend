<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar código '1' para tipo de IGV usado en anticipos por Nubefact
        // Este es el código simplificado que usa Nubefact para Gravado (tribute_code 1000)
        // El tributo 9996 se genera automáticamente cuando sunat_transaction = 04
        DB::table('sunat_concepts')->insert([
            'code_nubefact' => '1',
            'description' => 'Gravado - Operación Onerosa (código Nubefact)',
            'type' => 'BILLING_IGV_TYPE',
            'tribute_code' => '1000',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sunat_concepts')
            ->where('code_nubefact', '1')
            ->where('tribute_code', '9996')
            ->where('type', 'BILLING_IGV_TYPE')
            ->delete();
    }
};
