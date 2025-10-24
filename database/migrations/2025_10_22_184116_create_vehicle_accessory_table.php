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
    Schema::create('ap_vehicle_accessory', function (Blueprint $table) {
      $table->id();
      $table->foreignId('vehicle_purchase_order_id')
        ->constrained('ap_vehicle_purchase_order')
        ->onDelete('cascade');
      $table->foreignId('accessory_id')
        ->constrained('ap_commercial_masters')
        ->onDelete('cascade');
      $table->decimal('unit_price', 10);
      $table->integer('quantity')->default(1);
      $table->decimal('total', 10);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_accessory');
  }
};
