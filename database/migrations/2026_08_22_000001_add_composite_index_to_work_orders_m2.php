<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * Agrega índices adicionales complementarios a la migración principal.
   * Trabaja en conjunto con: 2026_08_21_000001_add_composite_index_to_work_orders.php
   *
   * Índices para:
   * - Queries que filtran solo por opening_date o status_id
   * - Eager loading de relaciones más usadas
   */
  public function up(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      // Índice de fecha para queries que solo filtran por opening_date (sin otros filtros)
      $table->index('opening_date', 'idx_wo_opening_date');

      // Índice de estado para queries que solo filtran por status_id (sin otros filtros)
      $table->index('status_id', 'idx_wo_status_id');

      // Índices para foreign keys MÁS USADAS en eager loading
      $table->index('advisor_id', 'idx_wo_advisor_id');
      $table->index('delivery_by', 'idx_wo_delivery_by');
      $table->index('created_by', 'idx_wo_created_by');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      // Eliminar índices en orden inverso
      $table->dropIndex('idx_wo_created_by');
      $table->dropIndex('idx_wo_delivery_by');
      $table->dropIndex('idx_wo_advisor_id');
      $table->dropIndex('idx_wo_status_id');
      $table->dropIndex('idx_wo_opening_date');
    });
  }
};
