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
      $table->string('num_ejes', length: 50);
      $table->string('ancho', length: 50);
      $table->string('largo', length: 50);
      $table->string('altura', length: 50);
      $table->string('num_asientos', length: 50);
      $table->string('num_puertas', length: 50);
      $table->string('peso_neto', length: 50);
      $table->string('peso_bruto', length: 50);
      $table->string('carga_util', length: 50);
      $table->string('cilindrada', length: 50);
      $table->string('num_cilindros', length: 50);
      $table->string('num_pasajeros', length: 50);
      $table->string('num_ruedas', length: 50);
      $table->decimal('precio_distribuidor', 10, 4);
      $table->decimal('costo_transporte', 10, 4);
      $table->decimal('otros_importes', 10, 4);
      $table->decimal('descuento_compra', 10, 4);
      $table->decimal('importe_igv', 10, 4);
      $table->decimal('total_total_compra_sigv', 10, 4);
      $table->decimal('total_total_compra_cigv', 10, 4);
      $table->decimal('precio_venta', 10, 4);
      $table->decimal('margen', 10, 4);
      $table->foreignId('familia_id')
        ->constrained('ap_families')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('clase_id')
        ->constrained('ap_class_article')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('combustible_id')
        ->constrained('ap_fuel_type')
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
      $table->foreignId('transmision_id')
        ->constrained('ap_commercial_masters')
        ->cascadeOnUpdate()
        ->restrictOnDelete();
      $table->foreignId('tipo_moneda_id')
        ->constrained('type_currency')
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
    Schema::dropIfExists('ap_models_vn');
  }
};
