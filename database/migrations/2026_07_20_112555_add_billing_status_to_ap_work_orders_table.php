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
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->enum('billing_status', [
                'pending',              // Sin factura ni cierre
                'invoiced_simple',      // Facturada directo (work_order_id)
                'invoiced_massive',     // Facturada vía nota interna
                'closed_internal',      // Cerrada con nota interna SIN factura
                'with_advances'         // Tiene anticipos pero sin factura final
            ])->default('pending')->after('status_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->dropColumn('billing_status');
        });
    }
};
