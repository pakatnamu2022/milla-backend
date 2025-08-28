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
    Schema::create('ap_families', function (Blueprint $table) {
      $table->id();
      $table->string('codigo', length: 100);
      $table->string('descripcion', length: 255);
      $table->boolean('status')->default(true);
      $table->foreignId('marca_id')
        ->constrained('ap_vehicle_brand')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_families');
  }
};
