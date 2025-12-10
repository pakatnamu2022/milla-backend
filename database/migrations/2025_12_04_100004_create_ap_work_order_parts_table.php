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
    Schema::create('ap_work_order_parts', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('work_order_id')->comment('Orden de trabajo')
        ->constrained('ap_work_orders')->onDelete('cascade');

      $table->integer('group_number')->comment('Número de grupo para agrupar');

      $table->foreignId('product_id')->comment('Repuesto utilizado')
        ->constrained('products')->onDelete('cascade');

      $table->foreignId('warehouse_id')->comment('Almacén de donde salió')
        ->constrained('warehouse')->onDelete('cascade');

      // Quantities and pricing
      $table->decimal('quantity_used', 10, 2)->comment('Cantidad utilizada');
      $table->decimal('unit_cost', 12, 2)->default(0)->comment('Costo unitario');
      $table->decimal('unit_price', 12, 2)->default(0)->comment('Precio de venta unitario');
      $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Porcentaje de descuento');
      $table->decimal('subtotal', 12, 2)->default(0)->comment('Subtotal sin impuestos');
      $table->decimal('tax_amount', 12, 2)->default(0)->comment('Impuestos (IGV)');
      $table->decimal('total_amount', 12, 2)->default(0)->comment('Total con impuestos');
      $table->boolean('is_delivered')->default(false)->comment('Indica si el repuesto ha sido entregado al operario');

      // Dates and audit
      $table->integer('registered_by')->comment('Usuario que registró');
      $table->foreign('registered_by')->references('id')->on('usr_users')->onDelete('cascade');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('work_order_id');
      $table->index('product_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_work_order_parts');
  }
};
