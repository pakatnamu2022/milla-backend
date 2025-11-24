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
    Schema::create('inventory_movement_details', function (Blueprint $table) {
      $table->id();

      // Inventory Movement - Movimiento de Inventario
      // Relación con la cabecera del movimiento
      $table->foreignId('inventory_movement_id')->constrained('inventory_movements')->onDelete('cascade')->comment('Inventory movement reference');

      // Product - Producto
      // Producto que se mueve
      $table->foreignId('product_id')->constrained('products')->comment('Product reference');

      // Quantity - Cantidad
      // Cantidad del movimiento (positivo = ingreso, negativo = salida)
      $table->decimal('quantity', 10, 2)->comment('Quantity (positive = in, negative = out)');

      // Unit Cost - Costo Unitario
      // Costo unitario del producto en el momento del movimiento
      $table->decimal('unit_cost', 10, 2)->default(0)->comment('Unit cost at the time of movement');

      // Total Cost - Costo Total
      // quantity * unit_cost
      $table->decimal('total_cost', 10, 2)->default(0)->comment('Total cost (quantity × unit_cost)');

      // Batch Number - Número de Lote
      // Lote del producto (trazabilidad)
      $table->string('batch_number', 50)->nullable()->comment('Product batch number');

      // Expiration Date - Fecha de Vencimiento
      // Fecha de vencimiento del lote
      $table->date('expiration_date')->nullable()->comment('Batch expiration date');

      // Notes - Notas
      // Observaciones específicas del detalle
      $table->text('notes')->nullable()->comment('Detail notes');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('inventory_movement_id');
      $table->index('product_id');
      $table->index('batch_number');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('inventory_movement_details');
  }
};
