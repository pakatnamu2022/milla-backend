<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_receiving_accessory_status', function (Blueprint $table) {
      $table->id();
      $table->foreignId('shipping_guide_id')->constrained('shipping_guides')->cascadeOnDelete();
      $table->foreignId('purchase_order_item_id')
        ->nullable()
        ->constrained('ap_purchase_order_item')
        ->nullOnDelete();
      $table->string('description', 500);
      $table->decimal('quantity', 10, 2)->default(1);
      $table->boolean('received')->default(true)
        ->comment('true = llegó con el vehículo, false = no llegó, requiere instalación posterior');
      $table->unsignedBigInteger('work_order_id')->nullable()
        ->comment('OT de instalación generada cuando el accesorio no llegó');
      $table->foreign('work_order_id')->references('id')->on('ap_work_orders')->nullOnDelete();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_receiving_accessory_status');
  }
};
