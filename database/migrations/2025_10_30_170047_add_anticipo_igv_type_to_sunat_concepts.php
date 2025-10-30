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
        // Actualizar el registro existente con el código correcto
        // Para anticipos, Nubefact usa tipo_de_igv = 10 (Gravado) pero con IGV = 0
        // No se necesita un código especial, solo enviar IGV en 0
        // Vamos a eliminar este registro si existe
        DB::table('sunat_concepts')
            ->where('code_nubefact', '38')
            ->where('tribute_code', '9996')
            ->where('type', 'BILLING_IGV_TYPE')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en el rollback
    }
};
