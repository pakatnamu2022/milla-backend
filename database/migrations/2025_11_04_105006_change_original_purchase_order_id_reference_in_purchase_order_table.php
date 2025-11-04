<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cambia la referencia de original_purchase_order_id de ap_vehicle_purchase_order
     * a ap_purchase_order (relación recursiva)
     */
    public function up(): void
    {
        // Deshabilitar foreign key checks para evitar problemas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('ap_purchase_order', function (Blueprint $table) {
            // 1. Eliminar la foreign key existente
            $table->dropForeign(['original_purchase_order_id']);

            // 2. Eliminar la columna
            $table->dropColumn('original_purchase_order_id');
        });

        Schema::table('ap_purchase_order', function (Blueprint $table) {
            // 3. Crear la columna de nuevo con la nueva referencia (recursiva)
            $table->foreignId('original_purchase_order_id')
                ->nullable()
                ->after('resent')
                ->constrained('ap_purchase_order')
                ->onDelete('cascade')
                ->comment('ID de la OC original cuando esta es una corrección por NC (relación recursiva)');
        });

        // Rehabilitar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     * Revierte el cambio para que original_purchase_order_id vuelva a referenciar
     * a ap_vehicle_purchase_order
     */
    public function down(): void
    {
        // Deshabilitar foreign key checks para evitar problemas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('ap_purchase_order', function (Blueprint $table) {
            // 1. Eliminar la foreign key actual (recursiva)
            $table->dropForeign(['original_purchase_order_id']);

            // 2. Eliminar la columna
            $table->dropColumn('original_purchase_order_id');
        });

        Schema::table('ap_purchase_order', function (Blueprint $table) {
            // 3. Recrear la columna con la referencia original a ap_vehicle_purchase_order
            $table->unsignedBigInteger('original_purchase_order_id')
                ->nullable()
                ->after('resent')
                ->comment('ID de la OC original cuando esta es una corrección por NC');

            $table->foreign('original_purchase_order_id')
                ->references('id')
                ->on('ap_vehicle_purchase_order')
                ->onDelete('set null');
        });

        // Rehabilitar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
