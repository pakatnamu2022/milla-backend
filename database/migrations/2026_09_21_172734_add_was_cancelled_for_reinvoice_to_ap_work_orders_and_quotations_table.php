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
        // Agregar campo a ap_work_orders
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->boolean('was_cancelled_for_reinvoice')->default(false)->after('stock_re_reserved')
                ->comment('Indica si el comprobante fue cancelado/anulado en Nubefact y se va a volver a facturar');
        });

        // Agregar campo a ap_order_quotations
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->boolean('was_cancelled_for_reinvoice')->default(false)->after('stock_re_reserved')
                ->comment('Indica si el comprobante fue cancelado/anulado en Nubefact y se va a volver a facturar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->dropColumn('was_cancelled_for_reinvoice');
        });

        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->dropColumn('was_cancelled_for_reinvoice');
        });
    }
};
