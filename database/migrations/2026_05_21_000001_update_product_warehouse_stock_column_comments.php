<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('product_warehouse_stock', function (Blueprint $table) {
      $table->decimal('quantity', 10, 2)
        ->default(0)
        ->comment('Stock físico en Almacén')
        ->change();

      $table->decimal('quantity_in_transit', 10, 2)
        ->default(0)
        ->comment('Stock en tránsito por un traslado entre sedes')
        ->change();

      $table->decimal('quantity_pending_credit_note', 10, 2)
        ->default(0)
        ->comment('Shortage pending credit note resolution')
        ->change();

      $table->decimal('reserved_quantity', 10, 2)
        ->default(0)
        ->comment('Stock reservado por cotizaciones de venta u ordenes de trabajo')
        ->change();

      $table->decimal('available_quantity', 10, 2)
        ->default(0)
        ->comment('Stock disponible para la venta (cantidad - cantidad_reservada)')
        ->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('product_warehouse_stock', function (Blueprint $table) {
      $table->decimal('quantity', 10, 2)
        ->default(0)
        ->comment('Physical stock in warehouse')
        ->change();

      $table->decimal('quantity_in_transit', 10, 2)
        ->default(0)
        ->comment('Stock in approved purchase orders not yet received')
        ->change();

      $table->decimal('quantity_pending_credit_note', 10, 2)
        ->default(0)
        ->comment('Shortage pending credit note resolution')
        ->change();

      $table->decimal('reserved_quantity', 10, 2)
        ->default(0)
        ->comment('Stock reserved for sales orders')
        ->change();

      $table->decimal('available_quantity', 10, 2)
        ->default(0)
        ->comment('Available stock for sale (quantity - reserved_quantity)')
        ->change();
    });
  }
};

