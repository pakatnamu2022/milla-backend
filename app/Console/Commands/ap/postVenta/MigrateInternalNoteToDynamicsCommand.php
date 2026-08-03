<?php

namespace App\Console\Commands\ap\postVenta;

use App\Http\Resources\Dynamics\InternalNoteTransactionDetailResource;
use App\Http\Resources\Dynamics\InternalNoteTransactionHeaderResource;
use App\Jobs\VerifyAndMigrateInternalNoteJob;
use App\Models\ap\comercial\VehiclePurchaseOrderMigrationLog;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateInternalNoteToDynamicsCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'internal-note:migrate-dynamics
                          {id? : ID de la nota interna a migrar}
                          {--preview : Solo mostrar previsualización sin ejecutar}
                          {--all : Migrar todas las notas internas pendientes (solo INTERNA_SC)}
                          {--force : Forzar migración aunque ya existan logs}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Migra notas internas a Microsoft Dynamics 365 (solo tipo INTERNA_SC)';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    if ($this->option('all')) {
      return $this->migrateAll();
    }

    $internalNoteId = $this->argument('id');

    if (!$internalNoteId) {
      $this->error('Debes proporcionar un ID de nota interna o usar --all');
      $this->info('Ejemplos:');
      $this->line('  php artisan internal-note:migrate-dynamics 123 --preview');
      $this->line('  php artisan internal-note:migrate-dynamics 123');
      $this->line('  php artisan internal-note:migrate-dynamics --all');
      return 1;
    }

    return $this->migrateSingle($internalNoteId);
  }

  /**
   * Migra una nota interna específica
   */
  protected function migrateSingle(int $internalNoteId): int
  {
    $this->info("🔍 Validando nota interna ID: {$internalNoteId}");
    $this->newLine();

    // 1. Buscar la nota interna
    $internalNote = ApInternalNote::with([
      'workOrder.items.typePlanning',
      'workOrder.parts.product',
      'inventoryMovements.details.product',
      'inventoryMovements.warehouse.sede'
    ])->find($internalNoteId);

    if (!$internalNote) {
      $this->error("✗ Nota interna ID {$internalNoteId} no encontrada.");
      return 1;
    }

    // 2. Validar que tenga orden de trabajo
    if (!$internalNote->workOrder) {
      $this->error("✗ La nota interna {$internalNote->number} no tiene orden de trabajo asociada.");
      return 1;
    }

    // 3. Validar tipo de planificación (solo INTERNA_SC)
    $item = $internalNote->workOrder->items()->whereNull('deleted_at')->first();
    if (!$item || !$item->typePlanning) {
      $this->error("✗ La orden de trabajo no tiene tipo de planificación válido.");
      return 1;
    }

    $typeDocument = $item->typePlanning->type_document;

    if ($typeDocument === TypePlanningWorkOrder::INTERNA_CC) {
      $this->error("✗ ERROR: La nota interna {$internalNote->number} es tipo INTERNA_CC (con comprobante).");
      $this->error("  Solo se pueden migrar notas internas tipo INTERNA_SC (sin comprobante).");
      $this->line("  Las INTERNA_CC se migran automáticamente cuando se emite el comprobante electrónico.");
      return 1;
    }

    if ($typeDocument !== TypePlanningWorkOrder::INTERNA_SC) {
      $this->error("✗ ERROR: Tipo de planificación no válido: {$typeDocument}");
      $this->error("  Solo se acepta: " . TypePlanningWorkOrder::INTERNA_SC);
      return 1;
    }

    // 4. Verificar que la OT tenga repuestos (product_id)
    $productParts = $internalNote->workOrder->parts->filter(fn($part) => $part->product_id !== null);

    if ($productParts->isEmpty()) {
      $this->warn("⚠️  La orden de trabajo {$internalNote->workOrder->correlative} NO tiene repuestos cargados.");
      $this->line("  Solo las OT con repuestos generan salida de inventario.");
      $this->line("  Esta nota interna NO requiere migración a Dynamics.");
      return 0; // No es error, simplemente no hay nada que hacer
    }

    $this->line("✓ OT con {$productParts->count()} repuesto(s) cargado(s)");
    $this->newLine();

    // 5. Verificar que tenga movimiento de inventario
    $inventoryMovement = $internalNote->inventoryMovements()
      ->where('movement_type', InventoryMovement::TYPE_ADJUSTMENT_OUT)
      ->first();

    if (!$inventoryMovement) {
      $this->error("✗ La nota interna {$internalNote->number} no tiene movimiento de inventario de tipo ADJUSTMENT_OUT.");
      $this->error("  Debe generarse primero el movimiento de inventario.");
      return 1;
    }

    if ($inventoryMovement->details->isEmpty()) {
      $this->error("✗ El movimiento de inventario no tiene detalles.");
      return 1;
    }

    // 5. Verificar duplicados (si ya existe migración)
    $existingLogs = VehiclePurchaseOrderMigrationLog::where('internal_note_id', $internalNoteId)
      ->whereIn('step', [
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION,
        VehiclePurchaseOrderMigrationLog::STEP_INTERNAL_NOTE_TRANSACTION_DETAIL,
      ])
      ->get();

    if ($existingLogs->isNotEmpty() && !$this->option('force')) {
      $this->warn("⚠️  Ya existen {$existingLogs->count()} logs de migración para esta nota interna:");
      $this->newLine();

      $headers = ['Step', 'Status', 'Attempts', 'Last Attempt'];
      $rows = $existingLogs->map(fn($log) => [
        $log->step,
        $log->status,
        $log->attempts,
        $log->last_attempt_at?->format('Y-m-d H:i:s') ?? 'N/A',
      ])->toArray();

      $this->table($headers, $rows);
      $this->newLine();

      $this->error('✗ La nota interna ya fue migrada o tiene intentos de migración.');
      $this->info('Usa --force para forzar una nueva migración.');
      return 1;
    }

    // 6. Mostrar previsualización
    $this->displayPreview($internalNote, $inventoryMovement);

    // Si es solo previsualización, terminar aquí
    if ($this->option('preview')) {
      $this->newLine();
      $this->info('✓ PREVISUALIZACIÓN COMPLETADA - No se ejecutó la migración.');
      $this->line('Para ejecutar la migración, ejecuta:');
      $this->line("  php artisan internal-note:migrate-dynamics {$internalNoteId}");
      return 0;
    }

    // 7. Confirmar antes de ejecutar
    if (!$this->confirm("¿Deseas migrar la nota interna {$internalNote->number} a Dynamics?", true)) {
      $this->info('Operación cancelada.');
      return 0;
    }

    // 8. Despachar el job
    $this->info('🚀 Despachando job de migración...');
    VerifyAndMigrateInternalNoteJob::dispatch($internalNoteId, false);

    $this->newLine();
    $this->info("✓ Job despachado correctamente para la nota interna {$internalNote->number}");
    $this->line("  El job se procesará en la cola 'internal_notes'");
    $this->line("  Puedes verificar el estado con: php artisan queue:work");

    return 0;
  }

  /**
   * Migra todas las notas internas pendientes
   */
  protected function migrateAll(): int
  {
    $this->info('🔍 Buscando notas internas pendientes de migración...');
    $this->newLine();

    // Buscar notas internas tipo INTERNA_SC con migration_status pendiente
    $pendingNotes = ApInternalNote::with([
      'workOrder.items.typePlanning',
      'workOrder.parts',
      'inventoryMovements',
    ])
      ->whereHas('workOrder.items.typePlanning', function ($query) {
        $query->where('type_document', TypePlanningWorkOrder::INTERNA_SC);
      })
      ->where(function ($query) {
        $query->whereNull('migration_status')
          ->orWhere('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_PENDING)
          ->orWhere('migration_status', VehiclePurchaseOrderMigrationLog::STATUS_FAILED);
      })
      ->whereNull('deleted_at')
      ->get();

    if ($pendingNotes->isEmpty()) {
      $this->info('✓ No se encontraron notas internas pendientes de migración.');
      return 0;
    }

    // Filtrar las que tienen repuestos Y movimiento de inventario
    $validNotes = $pendingNotes->filter(function ($note) {
      // 1. Verificar que tenga repuestos
      $hasProducts = $note->workOrder && $note->workOrder->parts->filter(fn($part) => $part->product_id !== null)->isNotEmpty();

      if (!$hasProducts) {
        return false;
      }

      // 2. Verificar que tenga movimiento de inventario
      return $note->inventoryMovements()
        ->where('movement_type', InventoryMovement::TYPE_ADJUSTMENT_OUT)
        ->exists();
    });

    if ($validNotes->isEmpty()) {
      $this->warn('⚠️  Se encontraron notas internas pero ninguna tiene repuestos o movimiento de inventario válido.');
      $this->line('  Solo se migran las notas internas cuyas OT tienen repuestos cargados.');
      return 0;
    }

    $this->info("Encontradas {$validNotes->count()} notas internas para migrar:");
    $this->newLine();

    // Mostrar tabla resumen
    $headers = ['ID', 'Número NI', 'OT', 'Fecha', 'Migration Status'];
    $rows = $validNotes->take(20)->map(fn($note) => [
      $note->id,
      $note->number,
      $note->workOrder->correlative ?? 'N/A',
      $note->created_date->format('Y-m-d'),
      $note->migration_status ?? 'null',
    ])->toArray();

    $this->table($headers, $rows);

    if ($validNotes->count() > 20) {
      $this->line("... y " . ($validNotes->count() - 20) . " notas más.");
    }
    $this->newLine();

    // Solo previsualización
    if ($this->option('preview')) {
      $this->info('✓ PREVISUALIZACIÓN COMPLETADA - No se ejecutó la migración.');
      $this->line('Para ejecutar la migración de todas, ejecuta:');
      $this->line("  php artisan internal-note:migrate-dynamics --all");
      return 0;
    }

    // Confirmar
    if (!$this->confirm("¿Deseas migrar {$validNotes->count()} notas internas a Dynamics?", false)) {
      $this->info('Operación cancelada.');
      return 0;
    }

    // Despachar jobs
    $this->info('🚀 Despachando jobs de migración...');
    $bar = $this->output->createProgressBar($validNotes->count());
    $bar->start();

    $dispatched = 0;
    foreach ($validNotes as $note) {
      try {
        VerifyAndMigrateInternalNoteJob::dispatch($note->id, false);
        $dispatched++;
      } catch (\Exception $e) {
        $this->newLine();
        $this->error("Error despachando job para {$note->number}: {$e->getMessage()}");
      }
      $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);

    $this->info("✓ Jobs despachados correctamente: {$dispatched}/{$validNotes->count()}");
    $this->line("  Los jobs se procesarán en la cola 'internal_notes'");
    $this->line("  Puedes verificar el estado con: php artisan queue:work");

    return 0;
  }

  /**
   * Muestra la previsualización de los datos que se enviarán
   */
  protected function displayPreview(ApInternalNote $internalNote, InventoryMovement $inventoryMovement): void
  {
    $this->info('📋 PREVISUALIZACIÓN DE DATOS A ENVIAR:');
    $this->newLine();

    // Información general
    $this->line("Nota Interna: <fg=cyan>{$internalNote->number}</>");
    $this->line("OT: <fg=cyan>{$internalNote->workOrder->correlative}</>");
    $this->line("Tipo: <fg=green>" . TypePlanningWorkOrder::INTERNA_SC . "</>");
    $this->line("Estado Migración: <fg=yellow>{$internalNote->migration_status}</>");
    $this->newLine();

    // CABECERA (neInTbTransaccionInventario)
    try {
      $headerResource = new InternalNoteTransactionHeaderResource($internalNote, false);
      $headerData = $headerResource->toArray(request());

      $this->info('📄 CABECERA (neInTbTransaccionInventario):');
      foreach ($headerData as $key => $value) {
        $this->line("  {$key}: <fg=cyan>{$value}</>");
      }
      $this->newLine();
    } catch (\Exception $e) {
      $this->error("Error generando cabecera: {$e->getMessage()}");
      $this->newLine();
    }

    // DETALLE (neInTbTransaccionInventarioDet)
    try {
      $detailResource = new InternalNoteTransactionDetailResource($internalNote, false);
      $detailsData = $detailResource->toArray(request());

      $this->info("📦 DETALLE (neInTbTransaccionInventarioDet): {$this->count($detailsData)} líneas");
      $this->newLine();

      $headers = ['Línea', 'Artículo', 'Cantidad', 'UM', 'Almacén', 'Costo Unit'];
      $rows = collect($detailsData)->take(10)->map(fn($detail) => [
        $detail['Linea'],
        $detail['ArticuloId'],
        $detail['Cantidad'],
        $detail['UnidadMedidaId'],
        $detail['AlmacenId'],
        $detail['CostoUnitario'],
      ])->toArray();

      $this->table($headers, $rows);

      if (count($detailsData) > 10) {
        $this->line("... y " . (count($detailsData) - 10) . " líneas más.");
      }
      $this->newLine();

    } catch (\Exception $e) {
      $this->error("Error generando detalle: {$e->getMessage()}");
      $this->newLine();
    }

    // Información del movimiento de inventario
    $this->info('📊 MOVIMIENTO DE INVENTARIO:');
    $this->line("  Número: <fg=cyan>{$inventoryMovement->movement_number}</>");
    $this->line("  Tipo: <fg=cyan>{$inventoryMovement->movement_type}</>");
    $this->line("  Fecha: <fg=cyan>{$inventoryMovement->movement_date->format('Y-m-d')}</>");
    $this->line("  Almacén: <fg=cyan>{$inventoryMovement->warehouse->dyn_code}</>");
    $this->line("  Total Items: <fg=cyan>{$inventoryMovement->details->count()}</>");
    $this->newLine();
  }

  /**
   * Helper para contar elementos
   */
  private function count(array $array): int
  {
    return count($array);
  }
}