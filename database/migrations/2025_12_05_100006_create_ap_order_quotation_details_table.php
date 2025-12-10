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
    Schema::create('ap_order_quotation_details', function (Blueprint $table) {
      $table->id();

      // Relations
      $table->foreignId('order_quotation_id')->comment('Cotización asociada')
        ->constrained('ap_order_quotations')->onDelete('cascade');

      // Tipo de ítem
      $table->enum('item_type', ['PRODUCT', 'LABOR'])
        ->default('PRODUCT')
        ->comment('Tipo: PRODUCT=repuesto, LABOR=mano de obra');

      // Item details
      $table->foreignId('product_id')->nullable()
        ->comment('Producto si item_type=product')
        ->constrained('products')->onDelete('set null');

      $table->string('description')->comment('Descripción del ítem o trabajo');

      // Pricing
      $table->decimal('purchase_price', 12, 2)->nullable()
        ->comment('Precio de compra unitario al momento de cotizar');
      $table->decimal('quantity', 10, 2)->comment('Cantidad');
      $table->string('unit_measure', 50)->default('UND')
        ->comment('Unidad de medida: UND, HRS para mano de hora se toma HRS y para repuestos UND');
      $table->decimal('unit_price', 12, 2)->comment('Precio de venta unitario');
      $table->decimal('discount', 12, 2)->default(0)->comment('Descuento aplicado al ítem');
      $table->decimal('total_amount', 12, 2)->comment('Monto total del ítem');

      // Notes
      $table->text('observations')->nullable()->comment('Observaciones del ítem');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('order_quotation_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_order_quotation_details');
  }
};
