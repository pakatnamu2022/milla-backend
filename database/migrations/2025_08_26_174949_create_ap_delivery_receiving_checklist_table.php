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
    Schema::create('ap_delivery_receiving_checklist', function (Blueprint $table) {
      $table->id();
      $table->string('descripcion', 255);
      $table->enum('tipo', ['entrega', 'recepcion']);
      $table->boolean('status')->default(true);
      $table->foreignId('categoria_id')
        ->constrained('ap_commercial_masters')
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
    Schema::dropIfExists('ap_delivery_receiving_checklist');
  }
};
