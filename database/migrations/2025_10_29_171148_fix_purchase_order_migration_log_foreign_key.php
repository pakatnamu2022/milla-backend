<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 0. Limpiar registros huérfanos (IDs que no existen en ap_purchase_order)
        DB::table('ap_vehicle_purchase_order_migration_log')
            ->where('vehicle_purchase_order_id', 0)
            ->orWhereNotIn('vehicle_purchase_order_id', function ($query) {
                $query->select('id')->from('ap_purchase_order');
            })
            ->delete();

        // 1. Agregar el foreign key correcto apuntando a ap_purchase_order
        Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
            $table->foreign('vehicle_purchase_order_id', 'vehicle_purchase_order_id')
                ->references('id')
                ->on('ap_purchase_order')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
            // Eliminar el foreign key
            $table->dropForeign('vehicle_purchase_order_id');
        });
    }
};
