<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixInternalNoteInventoryReferences extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'inventory:fix-internal-note-references
                          {--execute : Ejecutar la actualización (sin esta opción solo muestra previsualización)}
                          {--type= : Filtrar por tipo de documento (INTERNA_SC o INTERNA_CC)}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Actualiza las referencias de InventoryMovement a ApInternalNote para movimientos antiguos';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    // Validar opción --type si fue proporcionada
    $typeFilter = $this->option('type');
    if ($typeFilter && !in_array($typeFilter, [TypePlanningWorkOrder::INTERNA_SC, TypePlanningWorkOrder::INTERNA_CC])) {
      $this->error("Tipo inválido. Usa: INTERNA_SC o INTERNA_CC");
      return 1;
    }

    $this->info('🔍 Buscando movimientos de inventario sin referencia a notas internas...');
    if ($typeFilter) {
      $this->info("   Filtrando por tipo: {$typeFilter}");
    }
    $this->newLine();

    // Buscar movimientos sin referencia que parecen ser de notas internas
    $movements = InventoryMovement::whereNull('reference_type')
      ->whereNull('reference_id')
      ->where(function ($query) {
        $query->where('notes', 'LIKE', '%Nota Interna IN-%')
          ->orWhere('notes', 'LIKE', '%reversión de Nota Interna IN-%');
      })
      ->orderBy('id')
      ->get();

    if ($movements->isEmpty()) {
      $this->info('✓ No se encontraron movimientos sin referencia.');
      return 0;
    }

    $this->info("Encontrados {$movements->count()} movimientos de inventario sin referencia.");
    $this->newLine();

    // Analizar y preparar los datos para actualizar
    $toUpdate = [];
    $notFound = [];
    $filtered = [];

    foreach ($movements as $movement) {
      // Extraer el número de nota interna del campo notes
      // Patrones: "Nota Interna IN-00001" o "reversión de Nota Interna IN-00001"
      preg_match('/IN-\d{5}/', $movement->notes, $matches);

      if (empty($matches)) {
        $notFound[] = [
          'id' => $movement->id,
          'number' => $movement->movement_number,
          'notes' => $movement->notes,
          'reason' => 'No se encontró patrón IN-XXXXX en notes',
        ];
        continue;
      }

      $internalNoteNumber = $matches[0];

      // Buscar la nota interna con sus relaciones
      $internalNote = ApInternalNote::withTrashed()
        ->with(['workOrder.items.typePlanning', 'electronicDocuments'])
        ->where('number', $internalNoteNumber)
        ->first();

      if (!$internalNote) {
        $notFound[] = [
          'id' => $movement->id,
          'number' => $movement->movement_number,
          'notes' => $movement->notes,
          'reason' => "Nota interna {$internalNoteNumber} no encontrada",
        ];
        continue;
      }

      // Obtener el tipo de documento de la orden de trabajo
      $workOrder = $internalNote->workOrder;
      if (!$workOrder) {
        $notFound[] = [
          'id' => $movement->id,
          'number' => $movement->movement_number,
          'notes' => $movement->notes,
          'reason' => "Orden de trabajo no encontrada para {$internalNoteNumber}",
        ];
        continue;
      }

      // Obtener el item activo (no eliminado)
      $item = $workOrder->items()->whereNull('deleted_at')->first();
      if (!$item || !$item->typePlanning) {
        $notFound[] = [
          'id' => $movement->id,
          'number' => $movement->movement_number,
          'notes' => $movement->notes,
          'reason' => "Item o TypePlanning no encontrado para {$internalNoteNumber}",
        ];
        continue;
      }

      $typeDocument = $item->typePlanning->type_document;

      // Filtrar por tipo si se especificó
      if ($typeFilter && $typeDocument !== $typeFilter) {
        $filtered[] = [
          'id' => $movement->id,
          'number' => $movement->movement_number,
          'internal_note_number' => $internalNote->number,
          'type_document' => $typeDocument,
        ];
        continue;
      }

      // Datos base para actualizar
      $updateData = [
        'movement_id' => $movement->id,
        'movement_number' => $movement->movement_number,
        'movement_type' => $movement->movement_type,
        'movement_date' => $movement->movement_date->format('Y-m-d'),
        'warehouse' => $movement->warehouse?->dyn_code ?? 'N/A',
        'internal_note_id' => $internalNote->id,
        'internal_note_number' => $internalNote->number,
        'work_order_correlative' => $workOrder->correlative,
        'type_document' => $typeDocument,
        'notes' => $movement->notes,
        'electronic_document_id' => null,
        'electronic_document_number' => null,
        'new_movement_date' => null,
      ];

      // Para INTERNA_CC, buscar el comprobante electrónico
      if ($typeDocument === TypePlanningWorkOrder::INTERNA_CC) {
        $electronicDocument = $internalNote->electronicDocuments->first();

        if (!$electronicDocument) {
          $notFound[] = [
            'id' => $movement->id,
            'number' => $movement->movement_number,
            'notes' => $movement->notes,
            'reason' => "INTERNA_CC sin comprobante electrónico asociado a {$internalNoteNumber}",
          ];
          continue;
        }

        // Actualizar datos con el comprobante electrónico
        $updateData['electronic_document_id'] = $electronicDocument->id;
        $updateData['electronic_document_number'] = $electronicDocument->full_number;
        $updateData['new_movement_date'] = $electronicDocument->created_date
          ? $electronicDocument->created_date->format('Y-m-d')
          : $electronicDocument->created_at->format('Y-m-d');
        $updateData['new_movement_type'] = InventoryMovement::TYPE_SALE;
      }

      $toUpdate[] = $updateData;
    }

    // Mostrar previsualización
    $this->displayPreview($toUpdate, $notFound, $filtered, $typeFilter);

    // Si no hay --execute, solo mostrar previsualización
    if (!$this->option('execute')) {
      $this->newLine();
      $this->warn('⚠️  PREVISUALIZACIÓN - No se realizaron cambios.');
      $this->info('Para ejecutar la actualización, ejecuta:');
      $typeOption = $typeFilter ? " --type={$typeFilter}" : "";
      $this->line("  php artisan inventory:fix-internal-note-references --execute{$typeOption}");
      return 0;
    }

    // Pedir confirmación
    if (!$this->confirm("¿Deseas actualizar {$this->count($toUpdate)} movimientos de inventario?", false)) {
      $this->info('Operación cancelada.');
      return 0;
    }

    // Ejecutar actualización
    return $this->executeUpdate($toUpdate);
  }

  /**
   * Muestra la previsualización de los cambios
   */
  private function displayPreview(array $toUpdate, array $notFound, array $filtered, ?string $typeFilter): void
  {
    if (!empty($toUpdate)) {
      $this->info("📋 MOVIMIENTOS QUE SE ACTUALIZARÁN ({$this->count($toUpdate)}):");
      $this->newLine();

      // Determinar si hay movimientos INTERNA_CC para mostrar columnas adicionales
      $hasInternaCC = collect($toUpdate)->contains(function ($item) {
        return $item['type_document'] === TypePlanningWorkOrder::INTERNA_CC;
      });

      if ($hasInternaCC) {
        $headers = ['ID Mov', 'Núm. Mov', 'Tipo Actual', 'Nuevo Tipo', 'Fecha Actual', 'Nueva Fecha', 'Núm. NI', 'Tipo Doc', 'Comprobante'];
        $rows = array_map(function ($item) {
          return [
            $item['movement_id'],
            $item['movement_number'],
            $this->getMovementTypeShort($item['movement_type']),
            isset($item['new_movement_type']) ? $this->getMovementTypeShort($item['new_movement_type']) : '-',
            $item['movement_date'],
            $item['new_movement_date'] ?? '-',
            $item['internal_note_number'],
            $item['type_document'],
            $item['electronic_document_number'] ?? '-',
          ];
        }, array_slice($toUpdate, 0, 20));
      } else {
        $headers = ['ID Mov', 'Núm. Mov', 'Tipo', 'Fecha', 'Almacén', 'ID NI', 'Núm. NI', 'OT', 'Tipo Doc'];
        $rows = array_map(function ($item) {
          return [
            $item['movement_id'],
            $item['movement_number'],
            $this->getMovementTypeShort($item['movement_type']),
            $item['movement_date'],
            $item['warehouse'],
            $item['internal_note_id'],
            $item['internal_note_number'],
            $item['work_order_correlative'],
            $item['type_document'],
          ];
        }, array_slice($toUpdate, 0, 20));
      }

      $this->table($headers, $rows);

      if (count($toUpdate) > 20) {
        $this->line("... y " . (count($toUpdate) - 20) . " movimientos más.");
      }
      $this->newLine();
    }

    if (!empty($filtered) && $typeFilter) {
      $this->info("🔍 MOVIMIENTOS FILTRADOS (no coinciden con --type={$typeFilter}): {$this->count($filtered)}");
      $this->newLine();

      $headers = ['ID', 'Número', 'Nota Interna', 'Tipo Doc'];
      $rows = array_map(function ($item) {
        return [
          $item['id'],
          $item['number'],
          $item['internal_note_number'],
          $item['type_document'],
        ];
      }, array_slice($filtered, 0, 10));

      $this->table($headers, $rows);

      if (count($filtered) > 10) {
        $this->line("... y " . (count($filtered) - 10) . " movimientos más.");
      }
      $this->newLine();
    }

    if (!empty($notFound)) {
      $this->warn("⚠️  MOVIMIENTOS QUE NO SE PUDIERON PROCESAR ({$this->count($notFound)}):");
      $this->newLine();

      $headers = ['ID', 'Número', 'Razón'];
      $rows = array_map(function ($item) {
        return [
          $item['id'],
          $item['number'],
          $item['reason'],
        ];
      }, array_slice($notFound, 0, 10)); // Mostrar solo primeros 10

      $this->table($headers, $rows);

      if (count($notFound) > 10) {
        $this->line("... y " . (count($notFound) - 10) . " movimientos más.");
      }
      $this->newLine();
    }
  }

  /**
   * Ejecuta la actualización de los movimientos
   */
  private function executeUpdate(array $toUpdate): int
  {
    $this->info('🔄 Actualizando movimientos...');
    $this->newLine();

    $bar = $this->output->createProgressBar(count($toUpdate));
    $bar->start();

    $updated = 0;
    $errors = 0;

    DB::beginTransaction();

    try {
      foreach ($toUpdate as $item) {
        try {
          // Datos base para actualizar
          $updateFields = [
            'reference_type' => ApInternalNote::class,
            'reference_id' => $item['internal_note_id'],
          ];

          // Para INTERNA_CC, actualizar también electronic_document_id, movement_date y movement_type
          if ($item['electronic_document_id']) {
            $updateFields['electronic_document_id'] = $item['electronic_document_id'];
          }

          if ($item['new_movement_date']) {
            $updateFields['movement_date'] = $item['new_movement_date'];
          }

          if (isset($item['new_movement_type'])) {
            $updateFields['movement_type'] = $item['new_movement_type'];
          }

          InventoryMovement::where('id', $item['movement_id'])
            ->update($updateFields);

          $updated++;
        } catch (\Exception $e) {
          $errors++;
          $this->newLine();
          $this->error("Error al actualizar movimiento {$item['movement_number']}: {$e->getMessage()}");
        }

        $bar->advance();
      }

      DB::commit();

      $bar->finish();
      $this->newLine(2);

      $this->info("✓ Actualización completada:");
      $this->line("  - Actualizados: {$updated}");
      if ($errors > 0) {
        $this->line("  - Errores: {$errors}");
      }

      return 0;
    } catch (\Exception $e) {
      DB::rollBack();

      $bar->finish();
      $this->newLine(2);

      $this->error("✗ Error durante la actualización: {$e->getMessage()}");
      $this->error("Se revirtieron todos los cambios.");

      return 1;
    }
  }

  /**
   * Obtiene una versión corta del tipo de movimiento
   */
  private function getMovementTypeShort(string $type): string
  {
    $short = [
      InventoryMovement::TYPE_ADJUSTMENT_IN => 'AJUSTE IN',
      InventoryMovement::TYPE_ADJUSTMENT_OUT => 'AJUSTE OUT',
      InventoryMovement::TYPE_PURCHASE_RECEPTION => 'COMPRA',
      InventoryMovement::TYPE_SALE => 'VENTA',
      InventoryMovement::TYPE_TRANSFER_IN => 'TRANSF IN',
      InventoryMovement::TYPE_TRANSFER_OUT => 'TRANSF OUT',
      InventoryMovement::TYPE_RETURN_IN => 'DEV IN',
      InventoryMovement::TYPE_RETURN_OUT => 'DEV OUT',
    ];

    return $short[$type] ?? $type;
  }

  /**
   * Helper para contar elementos
   */
  private function count(array $array): int
  {
    return count($array);
  }
}
