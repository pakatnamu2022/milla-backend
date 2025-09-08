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
    Schema::create('ap_vehicle_status', function (Blueprint $table) {
      $table->id();
      $table->string('code', 50);
      $table->string('description', 255);
      $table->enum('use', ['VENTA', 'TALLER'])->default('VENTA');
      $table->string('color', 20);
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_status');
  }
};
