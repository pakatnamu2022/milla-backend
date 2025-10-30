<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sunat_concepts', function (Blueprint $table) {
            // Campo para BILLING_DOCUMENT_TYPE (Factura, Boleta, NC, ND)
            $table->string('prefix', 1)->nullable()->after('type')->comment('Prefijo para series de documentos (F, B, N)');

            // Campo para TYPE_DOCUMENT (documentos de identidad)
            $table->integer('length')->nullable()->after('prefix')->comment('Longitud del documento de identidad');

            // Campos para BILLING_IGV_TYPE
            $table->string('tribute_code', 4)->nullable()->after('length')->comment('Código de tributo SUNAT');
            $table->boolean('affects_total')->nullable()->after('tribute_code')->comment('Afecta al total de la factura');

            // Campos para BILLING_CURRENCY
            $table->string('iso_code', 3)->nullable()->after('affects_total')->comment('Código ISO 4217 de moneda');
            $table->string('symbol', 5)->nullable()->after('iso_code')->comment('Símbolo de moneda');

            // Campo para BILLING_DETRACTION_TYPE
            $table->decimal('percentage', 5, 3)->nullable()->after('symbol')->comment('Porcentaje de detracción');

            // Índice compuesto para optimizar búsquedas por tipo y código
            $table->index(['type', 'code_nubefact'], 'idx_type_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sunat_concepts', function (Blueprint $table) {
            $table->dropIndex('idx_type_code');
            $table->dropColumn([
                'prefix',
                'length',
                'tribute_code',
                'affects_total',
                'iso_code',
                'symbol',
                'percentage',
            ]);
        });
    }
};
