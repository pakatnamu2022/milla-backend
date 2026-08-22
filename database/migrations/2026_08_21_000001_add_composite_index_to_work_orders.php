<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * Migración para agregar índice compuesto a ap_work_orders
   *
   * Optimiza queries de listado con filtros múltiples:
   * - status_id IN (884, 889, 890, 891, 892)
   * - sede_id = 15
   * - opening_date BETWEEN '2026-08-01' AND '2026-08-21'
   *
   * ANTES: Escanea ~372 rows, filtra en memoria (0.98% eficiencia)
   * DESPUÉS: Usa índice compuesto, escanea solo rows que cumplen
   *
   * Mejora estimada: 60-80% en tiempo de respuesta
   */
  public function up(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      // Índice compuesto para filtros de listado
      // Orden: status_id, sede_id, opening_date
      // - status_id primero porque es el más selectivo en queries reales
      // - sede_id segundo para filtrar exactamente
      // - opening_date último para rangos
      $table->index(
        ['status_id', 'sede_id', 'opening_date'],
        'idx_work_orders_list_filters'
      );

      // Nota: Los índices individuales se mantienen porque son útiles
      // para otras queries y para las foreign keys
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropIndex('idx_work_orders_list_filters');
    });
  }
};
