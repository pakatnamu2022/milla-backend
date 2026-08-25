<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Vincula la orden de compra generada con la guía de remisión de
     * consignación que le dio origen. La columna ya era referenciada por
     * PurchaseOrderService::store() y StorePurchaseOrderRequest, pero nunca
     * se había creado en la tabla, por lo que el dato se perdía en la
     * asignación masiva (silenciosamente ignorada por no ser fillable).
     */
    public function up(): void
    {
        Schema::table('ap_purchase_order', function (Blueprint $table) {
            $table->unsignedBigInteger('consignment_shipping_guide_id')->nullable()->after('vehicle_movement_id');

            $table->foreign('consignment_shipping_guide_id')
                ->references('id')->on('shipping_guides')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_purchase_order', function (Blueprint $table) {
            $table->dropForeign(['consignment_shipping_guide_id']);
            $table->dropColumn('consignment_shipping_guide_id');
        });
    }
};
