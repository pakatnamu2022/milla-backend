<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfillea `deleted_at` para filas que el legacy web_millagp_2 marcó como
 * eliminadas (`status_deleted = 0`) antes de que la tabla tuviera la columna
 * `deleted_at`, o que el legacy siga borrando después sin tocar `deleted_at`.
 *
 * Comando central y acumulativo: cada vez que se migre una tabla nueva del
 * patrón `status_deleted` a `deleted_at` (ver
 * docs/MIGRACION_STATUS_DELETED_A_DELETED_AT.md), se agrega su nombre a
 * self::TABLES en vez de crear una migración de backfill aparte. Es
 * idempotente (solo toca filas con `deleted_at IS NULL`), así que se puede
 * correr las veces que haga falta, incluso periódicamente vía scheduler,
 * mientras ambas columnas convivan.
 */
class BackfillDeletedAtCommand extends Command
{
  protected $signature = 'deleted-at:backfill {--dry-run : Solo mostrar cuántas filas se actualizarían, sin escribir}';

  protected $description = 'Backfillea deleted_at a partir de status_deleted=0 en las tablas migradas al patrón deleted_at';

  /**
   * Tablas migradas del patrón legacy status_deleted a deleted_at nativo.
   * Agregar aquí cada tabla nueva que se sume a ese registro central.
   */
  private const TABLES = [
    'rrhh_contrato',
    'rrhh_firmante',
    'rrhh_plantilla_contrato',
    'rrhh_tipo_contrato',
  ];

  public function handle(): int
  {
    $dryRun = (bool) $this->option('dry-run');

    foreach (self::TABLES as $table) {
      $query = DB::table($table)
        ->where('status_deleted', 0)
        ->whereNull('deleted_at');

      $count = $query->count();

      if ($count === 0) {
        $this->info("{$table}: sin filas pendientes.");
        continue;
      }

      if ($dryRun) {
        $this->warn("{$table}: {$count} fila(s) pendientes de backfill (dry-run, no se modificó nada).");
        continue;
      }

      $updated = DB::table($table)
        ->where('status_deleted', 0)
        ->whereNull('deleted_at')
        ->update([
          'deleted_at' => DB::raw('COALESCE(updated_at, created_at, NOW())'),
        ]);

      $this->info("{$table}: {$updated} fila(s) backfilleadas.");
    }

    return self::SUCCESS;
  }
}
