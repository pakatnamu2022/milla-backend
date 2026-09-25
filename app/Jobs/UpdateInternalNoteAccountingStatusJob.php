<?php

namespace App\Jobs;

use App\Models\ap\facturacion\ApInternalNote;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para actualizar el estado contable de una nota interna individual
 * consultando los ajustes de inventario en Dynamics
 */
class UpdateInternalNoteAccountingStatusJob implements ShouldQueue
{
  use Queueable;

  const QUEUE_DEFAULT = 'internal_notes';

  public int $tries = 2;
  public int $timeout = 300;
  public int $backoff = 120;

  public function __construct(
    public int $internalNoteId,
    string $queue = self::QUEUE_DEFAULT
  ) {
    $this->onQueue($queue);
  }

  /**
   * Ejecuta el job
   */
  public function handle(): array
  {
    $internalNote = ApInternalNote::withTrashed()->find($this->internalNoteId);

    if (!$internalNote) {
      Log::warning("Nota interna no encontrada para actualizar estado contable", [
        'internal_note_id' => $this->internalNoteId
      ]);

      return [
        'success' => false,
        'message' => 'Nota interna no encontrada',
      ];
    }

    // Validar que la nota no esté en estado skipped
    if ($internalNote->migration_status === ApInternalNote::MIGRATION_STATUS_SKIPPED) {
      Log::warning("Nota interna omitida, no se actualiza estado contable", [
        'internal_note_id' => $this->internalNoteId
      ]);

      return [
        'success' => false,
        'message' => 'La nota interna fue omitida porque no tiene repuestos cargados',
      ];
    }

    // Consultar ajustes de inventario en Dynamics
    $dynamicsAdjustments = $this->consultAjustesInventario();

    if (empty($dynamicsAdjustments)) {
      Log::error("No se obtuvieron ajustes de inventario desde Dynamics", [
        'internal_note_id' => $this->internalNoteId
      ]);

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

    $updateData = [];

    // Verificar SALIDA (dyn_series_out)
    if ($internalNote->dyn_series_out && isset($adjustmentsMap[$internalNote->dyn_series_out]['SALIDA'])) {
      if (!$internalNote->is_accounted_out) {
        $updateData['is_accounted_out'] = true;
      }
    }

    // Verificar INGRESO (dyn_series_in)
    if ($internalNote->dyn_series_in && isset($adjustmentsMap[$internalNote->dyn_series_in]['INGRESO'])) {
      if (!$internalNote->is_accounted_in) {
        $updateData['is_accounted_in'] = true;
      }
    }

    // Actualizar solo si hay cambios
    $updated = false;
    if (!empty($updateData)) {
      $internalNote->update($updateData);
      $updated = true;
      $internalNote->refresh();

      Log::info("Nota interna actualizada con estado contable", [
        'internal_note_id' => $internalNote->id,
        'internal_note_number' => $internalNote->number,
        'changes' => $updateData
      ]);
    }

    return [
      'success' => true,
      'message' => $updated ? 'Nota interna actualizada correctamente' : 'No se encontraron cambios para actualizar',
      'data' => [
        'updated' => $updated,
        'internal_note_id' => $internalNote->id,
        'internal_note_number' => $internalNote->number,
        'is_accounted_in' => $internalNote->is_accounted_in,
        'is_accounted_out' => $internalNote->is_accounted_out,
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
      Log::error('Error ejecutando PA neIvConsultarAjustesInventario', [
        'error' => $e->getMessage(),
        'internal_note_id' => $this->internalNoteId
      ]);
      throw $e;
    }
  }
}