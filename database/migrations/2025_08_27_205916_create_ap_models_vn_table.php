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
    Schema::create('ap_models_vn', function (Blueprint $table) {
      $table->id();
      $table->string('codigo', length: 50);
      $table->string('version', length: 255);
      $table->string('potencia', length: 50);
      $table->year('anio_modelo');
      $table->string('distancias_ejes', length: 50);
      $table->integer('num_ejes');
      $table->foreignId('familia_id')
        ->constrained('ap_families')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('clase_id')
        ->constrained('ap_class_article')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('combustible_id')
        ->constrained('ap_tipo_combustible')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('tipo_vehiculo_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('tipo_carroceria_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('tipo_traccion_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_models_vn');
  }
};
