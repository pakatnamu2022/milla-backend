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
        Schema::table('ap_purchase_order_item', function (Blueprint $table) {
            // Product ID - ID del Producto
            // Relación con la tabla products cuando is_vehicle = false (es repuesto/producto)
            // NULL cuando is_vehicle = true (es vehículo)
            $table->foreignId('product_id')
                ->nullable()
                ->after('purchase_order_id')
                ->constrained('products')
                ->nullOnDelete()
                ->comment('Product ID when item is a spare part/product (not vehicle)');

            // Quantity Received - Cantidad Recibida
            // Controla cuánto se ha recibido vs lo ordenado
            $table->decimal('quantity_received', 10, 2)
                ->default(0)
                ->after('quantity')
                ->comment('Total quantity received so far');

            // Quantity Pending - Cantidad Pendiente (Computed: quantity - quantity_received)
            // Se puede calcular pero lo dejamos para queries más rápidas
            $table->decimal('quantity_pending', 10, 2)
                ->default(0)
                ->after('quantity_received')
                ->comment('Pending quantity to receive (quantity - quantity_received)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_purchase_order_item', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'quantity_received', 'quantity_pending']);
        });
    }
};
