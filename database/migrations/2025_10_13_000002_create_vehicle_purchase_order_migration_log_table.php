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
    Schema::create('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
      $table->id();

      $table->foreignId('vehicle_purchase_order_id')
        ->constrained('ap_vehicle_purchase_order', 'id', 'vehicle_purchase_order_id')
        ->onDelete('cascade')
        ->comment('Referencia a la orden de compra de vehículo');

      $table->enum('step', ['supplier', 'supplier_address', 'article', 'purchase_order', 'purchase_order_detail', 'reception', 'reception_detail', 'reception_detail_serial'])
        ->comment('Paso del proceso de migración');

      $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])
        ->default('pending')
        ->comment('Estado del paso de migración');

      $table->string('table_name')
        ->comment('Nombre de la tabla intermedia afectada');

      $table->string('external_id')->nullable()
        ->comment('ID o clave del registro en la tabla intermedia');

      $table->tinyInteger('proceso_estado')->nullable()
        ->comment('Valor de ProcesoEstado en la BD intermedia (0=pendiente, 1=procesado)');

      $table->text('error_message')->nullable()
        ->comment('Mensaje de error si el paso falló');

      $table->integer('attempts')->default(0)
        ->comment('Número de intentos de sincronización');

      $table->timestamp('last_attempt_at')->nullable()
        ->comment('Fecha y hora del último intento');

      $table->timestamp('completed_at')->nullable()
        ->comment('Fecha y hora en que se completó el paso');

      $table->timestamps();

      // Índices para optimizar consultas
      $table->index(['vehicle_purchase_order_id', 'step'], 'vpo_step_index');
      $table->index(['status'], 'vpo_status_index');
      $table->index(['vehicle_purchase_order_id', 'status'], 'vpo_status_vpo_index');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_purchase_order_migration_log');
  }
};
