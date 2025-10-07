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
    Schema::create('vehicle_vn', function (Blueprint $table) {
      $table->id();
      $table->string('vin');
      $table->string('order_number');
      $table->integer('year');
      $table->string('engine_number');
      $table->boolean('status')->default(true);
      $table->foreignId('ap_models_vn_id')
        ->constrained('ap_models_vn')->onDelete('cascade');
      $table->foreignId('vehicle_color_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('supplier_order_type_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('engine_type_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('vehicle_vn');
  }
};
