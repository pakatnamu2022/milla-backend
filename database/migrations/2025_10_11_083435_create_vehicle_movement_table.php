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
    Schema::create('ap_vehicle_movement', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_vehicle_status_id')->constrained('ap_vehicle_status');
      $table->foreignId('ap_vehicle_purchase_order_id')->constrained('ap_vehicle_purchase_order');
      $table->text('observation')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_movement');
  }
};
