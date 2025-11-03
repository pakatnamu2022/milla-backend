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
        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            // Cambiar de decimal(12,10) a decimal(12,2)
            // Esto permite valores hasta 9,999,999,999.99
            // Ya fueron corregidas: valor_unitario, precio_unitario, subtotal, igv
            // Solo falta: total
            $table->decimal('total', 12, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            // Revertir a la precisión original
            $table->decimal('total', 12, 10)->change();
        });
    }
};
