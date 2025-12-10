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
    Schema::create('ap_order_purchase_request_details', function (Blueprint $table) {
      $table->id();
      // Relations
      $table->foreignId('order_purchase_request_id')->comment('Solicitud de compra a la que pertenece')
        ->constrained('ap_order_purchase_requests', 'id', 'fk_order_purchase_request')->onDelete('cascade');

      $table->foreignId('product_id')->comment('Producto si item_type=product')
        ->constrained('products')->onDelete('cascade');

      // Datos del ítem
      $table->decimal('quantity', 10, 2)->comment('Cantidad solicitada');
      $table->text('notes')->nullable()->comment('Observaciones del ítem');
      $table->date('requested_delivery_date')->nullable()->comment('Fecha deseada de entrega');

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_order_purchase_request_details');
  }
};
