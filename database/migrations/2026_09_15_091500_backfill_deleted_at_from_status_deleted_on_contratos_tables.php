<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de `deleted_at` para filas que el legacy web_millagp_2 ya había
 * marcado como eliminadas (`status_deleted = 0`) ANTES de que
 * 2026_09_15_090000_add_deleted_at_to_contratos_tables agregara la columna.
 * Sin este backfill esas filas quedaban con deleted_at NULL, es decir,
 * visibles como "activas" para SoftDeletes en namu-frontend a pesar de estar
 * borradas en el legacy. Se usa updated_at (o created_at si no hay) como
 * aproximación de la fecha real de borrado, ya que el legacy no la guarda.
 * Ver docs/MIGRACION_STATUS_DELETED_A_DELETED_AT.md.
 */
return new class extends Migration
{
  private array $tables = [
    'rrhh_contrato',
    'rrhh_firmante',
    'rrhh_plantilla_contrato',
    'rrhh_tipo_contrato',
  ];

  public function up(): void
  {
    foreach ($this->tables as $table) {
      DB::table($table)
        ->where('status_deleted', 0)
        ->whereNull('deleted_at')
        ->update([
          'deleted_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
        ]);
    }
  }

  public function down(): void
  {
    // Backfill irreversible a propósito: no sabemos cuáles deleted_at fueron
    // puestos por este backfill vs. por un delete() real posterior.
  }
};
