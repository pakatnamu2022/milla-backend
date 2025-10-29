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
    Schema::create('ap_purchase_order_item', function (Blueprint $table) {
      $table->id();

      $table->foreignId('purchase_order_id')->comment('ID de la orden de compra')->constrained('ap_purchase_order')->onDelete('cascade');
      $table->foreignId('unit_measurement_id')->comment('ID de la unidad de medida del ítem')->constrained('unit_measurement');

      $table->string('description')->comment('Descripción del ítem');
      $table->decimal('unit_price', 10)->comment('Precio unitario del ítem');
      $table->integer('quantity')->default(1)->comment('Cantidad del ítem comprado');
      $table->decimal('total', 10)->comment('Precio total del ítem (unit_price * quantity)');

      $table->boolean('is_vehicle')->default(false)->comment('Indica si el ítem es un vehículo (true) o es otro tipo de ítem (false)');

      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_purchase_order_item');
  }
};
