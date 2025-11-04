<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cambia la referencia de vehicle_purchase_order_id de ap_vehicle_purchase_order
     * a ap_purchase_order
     */
    public function up(): void
    {
        // Deshabilitar foreign key checks para evitar problemas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('ap_vehicle_accessory', function (Blueprint $table) {
            // 1. Eliminar la foreign key existente
            $table->dropForeign(['vehicle_purchase_order_id']);

            // 2. Eliminar la columna
            $table->dropColumn('vehicle_purchase_order_id');
        });

        Schema::table('ap_vehicle_accessory', function (Blueprint $table) {
            // 3. Crear la columna de nuevo con la nueva referencia a ap_purchase_order
            $table->foreignId('vehicle_purchase_order_id')
                ->after('id')
                ->constrained('ap_purchase_order')
                ->onDelete('cascade');
        });

        // Rehabilitar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     * Revierte el cambio para que vehicle_purchase_order_id vuelva a referenciar
     * a ap_vehicle_purchase_order
     */
    public function down(): void
    {
        // Deshabilitar foreign key checks para evitar problemas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('ap_vehicle_accessory', function (Blueprint $table) {
            // 1. Eliminar la foreign key actual
            $table->dropForeign(['vehicle_purchase_order_id']);

            // 2. Eliminar la columna
            $table->dropColumn('vehicle_purchase_order_id');
        });

        Schema::table('ap_vehicle_accessory', function (Blueprint $table) {
            // 3. Recrear la columna con la referencia original a ap_vehicle_purchase_order
            $table->foreignId('vehicle_purchase_order_id')
                ->after('id')
                ->constrained('ap_vehicle_purchase_order')
                ->onDelete('cascade');
        });

        // Rehabilitar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
