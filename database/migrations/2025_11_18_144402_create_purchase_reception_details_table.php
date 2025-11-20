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
        Schema::create('purchase_reception_details', function (Blueprint $table) {
            $table->id();

            // Purchase Reception - Recepción de Compra
            // Relación con la cabecera de recepción
            $table->foreignId('purchase_reception_id')
                ->constrained('purchase_receptions')
                ->onDelete('cascade')
                ->comment('Related purchase reception header');

            // Purchase Order Item - Item de Orden de Compra
            // NULL si es BONUS o GIFT (no estaba en la orden original)
            $table->foreignId('purchase_order_item_id')
                ->nullable()
                ->constrained('ap_purchase_order_item')
                ->nullOnDelete()
                ->comment('Related purchase order item (NULL for BONUS/GIFT)');

            // Product - Producto
            // Siempre debe tener product_id para productos
            $table->foreignId('product_id')
                ->constrained('products')
                ->comment('Product being received');

            // Quantity Received - Cantidad Recibida
            // Cantidad que llegó físicamente
            $table->decimal('quantity_received', 10, 2)->comment('Actual quantity received');

            // Quantity Accepted - Cantidad Aceptada
            // Cantidad que se acepta y se registra en inventario
            $table->decimal('quantity_accepted', 10, 2)->comment('Quantity accepted for inventory');

            // Quantity Rejected - Cantidad Rechazada
            // Cantidad rechazada por daños, defectos, etc.
            $table->decimal('quantity_rejected', 10, 2)->default(0)->comment('Quantity rejected (damaged, defective)');

            // Reception Type - Tipo de Recepción
            // ORDERED: Producto que estaba en la orden de compra
            // BONUS: Cortesía/Oferta del proveedor (ej: compras 10, llegan 11)
            // GIFT: Regalo puro del proveedor (no relacionado a compra específica)
            // SAMPLE: Muestra gratis para prueba
            $table->enum('reception_type', ['ORDERED', 'BONUS', 'GIFT', 'SAMPLE'])
                ->default('ORDERED')
                ->comment('ORDERED, BONUS (courtesy from supplier), GIFT, or SAMPLE');

            // Unit Cost - Costo Unitario
            // $0.00 para BONUS, GIFT, SAMPLE
            // Costo del producto para ORDERED
            $table->decimal('unit_cost', 10, 2)->default(0)->comment('Unit cost (0 for BONUS/GIFT/SAMPLE)');

            // Is Charged - Se Cobra
            // false para BONUS, GIFT, SAMPLE
            // true para ORDERED
            $table->boolean('is_charged')
                ->default(true)
                ->comment('Whether this item is charged (false for BONUS/GIFT/SAMPLE)');

            // Total Cost - Costo Total
            // quantity_accepted * unit_cost
            $table->decimal('total_cost', 10, 2)->default(0)->comment('Total cost (quantity_accepted * unit_cost)');

            // Rejection Reason - Razón de Rechazo
            // Solo si quantity_rejected > 0
            $table->enum('rejection_reason', [
                'DAMAGED',
                'DEFECTIVE',
                'EXPIRED',
                'WRONG_PRODUCT',
                'WRONG_QUANTITY',
                'POOR_QUALITY',
                'OTHER'
            ])->nullable()->comment('Reason for rejection if quantity_rejected > 0');

            // Rejection Notes - Notas de Rechazo
            $table->text('rejection_notes')->nullable()->comment('Detailed rejection notes');

            // Bonus Reason - Razón del Bonus
            // Solo si reception_type = BONUS
            $table->string('bonus_reason', 255)->nullable()->comment('Reason for bonus (promotion, offer, etc.)');

            // Batch Number - Número de Lote
            // Para productos que manejan lotes
            $table->string('batch_number', 100)->nullable()->comment('Batch/lot number if applicable');

            // Expiration Date - Fecha de Vencimiento
            // Para productos perecederos
            $table->date('expiration_date')->nullable()->comment('Expiration date for perishable products');

            // Notes - Notas
            // Observaciones específicas del producto
            $table->text('notes')->nullable()->comment('Specific notes for this product reception');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('purchase_reception_id');
            $table->index('purchase_order_item_id');
            $table->index('product_id');
            $table->index('reception_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_reception_details');
    }
};