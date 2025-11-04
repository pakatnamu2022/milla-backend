<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina ap_vehicle_purchase_order_id y crea ap_vehicle_id que referencia a ap_vehicles
     */
    public function up(): void
    {
        // Deshabilitar foreign key checks para evitar problemas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('purchase_request_quote', function (Blueprint $table) {
            // 1. Eliminar la foreign key existente
            $table->dropForeign(['ap_vehicle_purchase_order_id']);

            // 2. Eliminar la columna
            $table->dropColumn('ap_vehicle_purchase_order_id');
        });

        Schema::table('purchase_request_quote', function (Blueprint $table) {
            // 3. Crear la nueva columna ap_vehicle_id que referencia a ap_vehicles
            $table->foreignId('ap_vehicle_id')
                ->nullable()
                ->after('ap_models_vn_id')
                ->constrained('ap_vehicles')
                ->onDelete('cascade');
        });

        // Rehabilitar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     * Revierte el cambio: elimina ap_vehicle_id y recrea ap_vehicle_purchase_order_id
     */
    public function down(): void
    {
        // Deshabilitar foreign key checks para evitar problemas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('purchase_request_quote', function (Blueprint $table) {
            // 1. Eliminar la foreign key de ap_vehicle_id
            $table->dropForeign(['ap_vehicle_id']);

            // 2. Eliminar la columna
            $table->dropColumn('ap_vehicle_id');
        });

        Schema::table('purchase_request_quote', function (Blueprint $table) {
            // 3. Recrear la columna ap_vehicle_purchase_order_id
            $table->foreignId('ap_vehicle_purchase_order_id')
                ->nullable()
                ->after('ap_models_vn_id')
                ->constrained('ap_vehicle_purchase_order')
                ->onDelete('cascade');
        });

        // Rehabilitar foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
