<?php

namespace App\Jobs;

use App\Models\ap\facturacion\ApInternalNote;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para actualizar masivamente el estado contable de las notas internas
 * que aún no están verificadas
 */
class BulkUpdateInternalNotesAccountingStatusJob implements ShouldQueue
{
  use Queueable;

  const QUEUE_DEFAULT = 'internal_notes';

  public int $tries = 2;
  public int $timeout = 600; // 10 minutos para procesar muchas notas
  public int $backoff = 120;

  public function __construct(
    string $queue = self::QUEUE_DEFAULT
  ) {
    $this->onQueue($queue);
  }

  /**
   * Ejecuta el job
   */
  public function handle(): array
  {
    Log::info("Iniciando actualización masiva de estado contable de notas internas");

    // Consultar ajustes de inventario en Dynamics una sola vez
    $dynamicsAdjustments = $this->consultAjustesInventario();

    if (empty($dynamicsAdjustments)) {
      Log::error("No se obtuvieron ajustes de inventario desde Dynamics para actualización masiva");

      return [
        'success' => false,
        'message' => 'No se obtuvieron registros de ajustes de inventario desde Dynamics',
      ];
    }

    // Crear un mapa para búsqueda rápida: [Numero][Tipo_Movimiento] = true
    $adjustmentsMap = [];
    foreach ($dynamicsAdjustments as $adjustment) {
      $numero = $adjustment->Numero ?? null;
      $tipoMovimiento = $adjustment->Tipo_Movimiento ?? null;

      if ($numero && $tipoMovimiento) {
        if (!isset($adjustmentsMap[$numero])) {
          $adjustmentsMap[$numero] = [];
        }
        $adjustmentsMap[$numero][$tipoMovimiento] = true;
      }
    }

    // Obtener notas internas según la lógica especificada
    $internalNotes = ApInternalNote::withTrashed()
      ->where('migration_status', ApInternalNote::MIGRATION_STATUS_COMPLETED)
      ->where(function ($query) {
        // Caso 1: Solo tiene dyn_series_out (no se ha revertido) → validar is_accounted_out = 0
        $query->where(function ($q) {
          $q->whereNotNull('dyn_series_out')
            ->whereNull('dyn_series_in')
            ->where('is_accounted_out', 0);
        })
        // Caso 2: Solo tiene dyn_series_in (ya se revirtió) → validar is_accounted_in = 0
        ->orWhere(function ($q) {
          $q->whereNotNull('dyn_series_in')
            ->whereNull('dyn_series_out')
            ->where('is_accounted_in', 0);
        })
        // Caso 3: Tiene ambos → validar que al menos uno no esté contabilizado
        ->orWhere(function ($q) {
          $q->whereNotNull('dyn_series_out')
            ->whereNotNull('dyn_series_in')
            ->where(function ($subQ) {
              $subQ->where('is_accounted_in', 0)
                ->orWhere('is_accounted_out', 0);
            });
        });
      })
      ->get();

    if ($internalNotes->isEmpty()) {
      Log::info("No hay notas internas pendientes de verificación contable");

      return [
        'success' => true,
        'message' => 'No hay notas internas pendientes de verificación contable',
        'data' => [
          'total_checked' => 0,
          'total_updated' => 0,
          'details' => [],
        ],
      ];
    }

    $totalChecked = $internalNotes->count();
    $totalUpdated = 0;
    $details = [];

    Log::info("Procesando {$totalChecked} notas internas para actualización de estado contable");

    // Procesar cada nota interna
    foreach ($internalNotes as $internalNote) {
      $updateData = [];
      $changes = [];

      // Verificar SALIDA (dyn_series_out)
      if ($internalNote->dyn_series_out && isset($adjustmentsMap[$internalNote->dyn_series_out]['SALIDA'])) {
        if (!$internalNote->is_accounted_out) {
          $updateData['is_accounted_out'] = true;
          $changes[] = 'is_accounted_out';
        }
      }

      // Verificar INGRESO (dyn_series_in)
      if ($internalNote->dyn_series_in && isset($adjustmentsMap[$internalNote->dyn_series_in]['INGRESO'])) {
        if (!$internalNote->is_accounted_in) {
          $updateData['is_accounted_in'] = true;
          $changes[] = 'is_accounted_in';
        }
      }

      // Actualizar solo si hay cambios
      if (!empty($updateData)) {
        $internalNote->update($updateData);
        $totalUpdated++;

        $details[] = [
          'id' => $internalNote->id,
          'number' => $internalNote->number,
          'changes' => $changes,
          'is_accounted_in' => $internalNote->fresh()->is_accounted_in,
          'is_accounted_out' => $internalNote->fresh()->is_accounted_out,
        ];

        Log::info("Nota interna actualizada", [
          'internal_note_id' => $internalNote->id,
          'internal_note_number' => $internalNote->number,
          'changes' => $changes
        ]);
      }
    }

    Log::info("Actualización masiva completada", [
      'total_checked' => $totalChecked,
      'total_updated' => $totalUpdated
    ]);

    return [
      'success' => true,
      'message' => "Proceso completado. {$totalUpdated} de {$totalChecked} notas actualizadas.",
      'data' => [
        'total_checked' => $totalChecked,
        'total_updated' => $totalUpdated,
        'details' => $details,
      ],
    ];
  }

  /**
   * Consulta los ajustes de inventario en Dynamics
   */
  protected function consultAjustesInventario(): array
  {
    try {
      return DB::connection(Company::CONNECTION_DYNAMICS_3)
        ->select("EXEC neIvConsultarAjustesInventario");
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarAjustesInventario en bulk update', [
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }
}