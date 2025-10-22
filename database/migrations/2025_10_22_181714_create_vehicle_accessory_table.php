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
    Schema::create('vehicle_accessory', function (Blueprint $table) {
      $table->id();
      $table->foreignId('vehicle_id')->constrained('ap_vehicle_purchase_order');
      $table->foreignId('accessory_id')->constrained('ap_commercial_masters');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('vehicle_accessory');
  }
};
