<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('product_warehouse_stock', function (Blueprint $table) {
      $table->id();

      // Product - Producto
      // Relación con el producto
      $table->foreignId('product_id')->constrained('products')->onDelete('cascade')->comment('Product reference');

      // Warehouse - Almacén
      // Relación con el almacén
      $table->foreignId('warehouse_id')->constrained('warehouse')->onDelete('cascade')->comment('Warehouse reference');

      // Quantity - Cantidad en Stock
      // Stock físico real en el almacén
      $table->decimal('quantity', 10, 2)->default(0)->comment('Physical stock in warehouse');

      // Quantity In Transit - Cantidad en Tránsito
      // Stock confirmado en OC pero aún no recibido
      $table->decimal('quantity_in_transit', 10, 2)->default(0)->comment('Stock in approved purchase orders not yet received');

      // Quantity Pending Credit Note - Cantidad Pendiente NC
      // Faltantes registrados esperando nota de crédito
      $table->decimal('quantity_pending_credit_note', 10, 2)->default(0)->comment('Shortage pending credit note resolution');

      // Reserved Quantity - Cantidad Reservada
      // Stock comprometido para ventas pero no despachado
      $table->decimal('reserved_quantity', 10, 2)->default(0)->comment('Stock reserved for sales orders');

      // Available Quantity - Cantidad Disponible
      // Calculado: quantity - reserved_quantity
      // Se puede vender
      $table->decimal('available_quantity', 10, 2)->default(0)->comment('Available stock for sale (quantity - reserved_quantity)');

      // Minimum Stock - Stock Mínimo
      // Cantidad mínima que debe haber en este almacén
      $table->decimal('minimum_stock', 10, 2)->default(0)->comment('Minimum inventory threshold for this warehouse');

      // Maximum Stock - Stock Máximo
      // Cantidad máxima recomendada en este almacén
      $table->decimal('maximum_stock', 10, 2)->nullable()->comment('Maximum recommended inventory for this warehouse');
      
      // Last Movement Date - Fecha Último Movimiento
      // Fecha del último movimiento de inventario
      $table->timestamp('last_movement_date')->nullable()->comment('Date of last inventory movement');

      $table->timestamps();

      // Unique constraint: Un producto solo puede tener un registro por almacén
      $table->unique(['product_id', 'warehouse_id'], 'unique_product_warehouse');

      // Indexes para mejorar performance
      $table->index('product_id');
      $table->index('warehouse_id');
      $table->index('quantity');
      $table->index('available_quantity');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('product_warehouse_stock');
  }
};
