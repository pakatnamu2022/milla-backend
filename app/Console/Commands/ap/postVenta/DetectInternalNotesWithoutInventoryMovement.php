<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Console\Command;

class DetectInternalNotesWithoutInventoryMovement extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'internal-notes:detect-missing-inventory
                          {--type= : Filtrar por tipo de documento (INTERNA_SC o INTERNA_CC)}
                          {--status= : Filtrar por estado de nota interna (pending o invoiced)}
                          {--limit=100 : Número máximo de resultados a mostrar (default: 100)}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Detecta notas internas que deberían tener salida de inventario pero no la tienen';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $typeFilter = $this->option('type');
    $statusFilter = $this->option('status');
    $limit = (int)$this->option('limit');

    // Validar tipo si fue proporcionado
    if ($typeFilter && !in_array($typeFilter, [TypePlanningWorkOrder::INTERNA_SC, TypePlanningWorkOrder::INTERNA_CC])) {
      $this->error("Tipo inválido. Usa: INTERNA_SC o INTERNA_CC");
      return 1;
    }

    // Validar status si fue proporcionado
    if ($statusFilter && !in_array($statusFilter, [ApInternalNote::STATUS_PENDING, ApInternalNote::STATUS_INVOICED])) {
      $this->error("Estado inválido. Usa: pending o invoiced");
      return 1;
    }

    $this->info('🔍 Analizando notas internas...');
    if ($typeFilter) {
      $this->info("   Filtrando por tipo: {$typeFilter}");
    }
    if ($statusFilter) {
      $this->info("   Filtrando por estado: {$statusFilter}");
    }
    $this->newLine();

    // Obtener todas las notas internas con sus relaciones
    $query = ApInternalNote::with([
      'workOrder.items.typePlanning',
      'workOrder.parts.product', // Agregar parts para verificar si tiene repuestos
      'inventoryMovements',
      'electronicDocuments'
    ]);

    if ($statusFilter) {
      $query->where('status', $statusFilter);
    }

    $internalNotes = $query->orderBy('id', 'desc')->get();

    if ($internalNotes->isEmpty()) {
      $this->info('✓ No se encontraron notas internas.');
      return 0;
    }

    $this->info("Analizando {$internalNotes->count()} notas internas...");
    $this->newLine();

    // Analizar cada nota interna
    $withoutMovement = [];
    $withMovement = [];
    $errors = [];

    foreach ($internalNotes as $internalNote) {
      $workOrder = $internalNote->workOrder;

      if (!$workOrder) {
        $errors[] = [
          'internal_note_id' => $internalNote->id,
          'internal_note_number' => $internalNote->number,
          'status' => $internalNote->status,
          'reason' => 'Orden de trabajo no encontrada',
        ];
        continue;
      }

      // Obtener el item activo
      $item = $workOrder->items()->whereNull('deleted_at')->first();
      if (!$item || !$item->typePlanning) {
        $errors[] = [
          'internal_note_id' => $internalNote->id,
          'internal_note_number' => $internalNote->number,
          'status' => $internalNote->status,
          'reason' => 'Item o TypePlanning no encontrado',
        ];
        continue;
      }

      $typeDocument = $item->typePlanning->type_document;

      // Aplicar filtro de tipo si existe
      if ($typeFilter && $typeDocument !== $typeFilter) {
        continue;
      }

      // CLAVE: Verificar si la OT tiene repuestos (parts con product_id)
      // Solo si tiene repuestos debe tener salida de inventario
      $productParts = $workOrder->parts->filter(fn($part) => $part->product_id !== null);
      $hasProductParts = $productParts->isNotEmpty();

      // Si NO tiene repuestos, esta OT no debe tener salida de inventario (es normal)
      // Saltar al siguiente
      if (!$hasProductParts) {
        continue;
      }

      // Verificar si tiene movimientos de inventario
      $hasInventoryMovement = $internalNote->inventoryMovements()->exists();

      $noteData = [
        'internal_note_id' => $internalNote->id,
        'internal_note_number' => $internalNote->number,
        'work_order_id' => $workOrder->id,
        'work_order_correlative' => $workOrder->correlative,
        'type_document' => $typeDocument,
        'status' => $internalNote->status,
        'created_date' => $internalNote->created_date?->format('Y-m-d'),
        'closed_date' => $internalNote->closed_date?->format('Y-m-d'),
        'has_movement' => $hasInventoryMovement,
        'has_electronic_document' => $internalNote->electronicDocuments->isNotEmpty(),
        'electronic_document_numbers' => $internalNote->electronicDocuments->pluck('full_number')->join(', '),
        'parts_count' => $productParts->count(),
        'total_quantity' => $productParts->sum('quantity_used'),
      ];

      // Para INTERNA_SC: DEBE tener movimiento de inventario (si tiene repuestos)
      // Para INTERNA_CC: NO debe tener movimiento al crear, solo al facturar (si tiene repuestos)
      if ($typeDocument === TypePlanningWorkOrder::INTERNA_SC) {
        // INTERNA_SC con repuestos DEBE tener movimiento tipo ADJUSTMENT_OUT
        if (!$hasInventoryMovement) {
          $withoutMovement[] = $noteData;
        } else {
          $withMovement[] = $noteData;
        }
      } elseif ($typeDocument === TypePlanningWorkOrder::INTERNA_CC) {
        // INTERNA_CC con repuestos no debe tener movimiento hasta que se facture
        // Si está facturada (invoiced) y tiene comprobante, DEBE tener movimiento tipo SALE
        if ($internalNote->status === ApInternalNote::STATUS_INVOICED && $noteData['has_electronic_document']) {
          if (!$hasInventoryMovement) {
            $withoutMovement[] = $noteData;
          } else {
            $withMovement[] = $noteData;
          }
        }
      }
    }

    // Mostrar resultados
    $this->displayResults($withoutMovement, $withMovement, $errors, $limit);

    return 0;
  }

  /**
   * Muestra los resultados del análisis
   */
  private function displayResults(array $withoutMovement, array $withMovement, array $errors, int $limit): void
  {
    // Notas internas SIN movimiento cuando DEBERÍAN tenerlo
    if (!empty($withoutMovement)) {
      $this->error("❌ NOTAS INTERNAS CON REPUESTOS SIN SALIDA DE INVENTARIO ({$this->count($withoutMovement)}):");
      $this->newLine();

      $headers = ['ID NI', 'Núm. NI', 'ID OT', 'OT', 'Tipo Doc', 'Repuestos', 'Cant. Total', 'Estado', 'Fecha Creación'];
      $rows = array_map(function ($item) {
        return [
          $item['internal_note_id'],
          $item['internal_note_number'],
          $item['work_order_id'],
          $item['work_order_correlative'],
          $item['type_document'],
          $item['parts_count'],
          $item['total_quantity'],
          $item['status'],
          $item['created_date'] ?? '-',
        ];
      }, array_slice($withoutMovement, 0, $limit));

      $this->table($headers, $rows);

      if (count($withoutMovement) > $limit) {
        $this->line("... y " . (count($withoutMovement) - $limit) . " notas más.");
      }

      $this->newLine();
      $this->warn("⚠️ Estas notas internas tienen repuestos pero NO tienen su salida de inventario.");
      $this->warn("   Esto significa que el inventario NO se descontó cuando se generó/facturó la nota interna.");
      $this->newLine();
    } else {
      $this->info("✓ Todas las notas internas tienen su salida de inventario correctamente.");
      $this->newLine();
    }

    // Notas internas CON movimiento (correcto)
    if (!empty($withMovement)) {
      $this->info("✓ NOTAS INTERNAS CON SALIDA DE INVENTARIO ({$this->count($withMovement)}):");
      $this->line("Estas notas tienen correctamente su movimiento de inventario.");
      $this->newLine();
    }

    // Errores
    if (!empty($errors)) {
      $this->warn("⚠️ NOTAS INTERNAS CON ERRORES ({$this->count($errors)}):");
      $this->newLine();

      $headers = ['ID NI', 'Núm. NI', 'Estado', 'Razón'];
      $rows = array_map(function ($item) {
        return [
          $item['internal_note_id'],
          $item['internal_note_number'],
          $item['status'],
          $item['reason'],
        ];
      }, array_slice($errors, 0, 20));

      $this->table($headers, $rows);

      if (count($errors) > 20) {
        $this->line("... y " . (count($errors) - 20) . " notas más.");
      }
      $this->newLine();
    }

    // Instrucciones para verificar en el sistema
    if (!empty($withoutMovement)) {
      $this->displayVerificationInstructions($withoutMovement);
    }
  }

  /**
   * Muestra instrucciones para verificar en el sistema
   */
  private function displayVerificationInstructions(array $withoutMovement): void
  {
    $this->info("📝 CÓMO VERIFICAR EN EL SISTEMA:");
    $this->newLine();

    $firstNote = $withoutMovement[0];

    $this->line("1. Abre el sistema y ve a la orden de trabajo:");
    $this->line("   - ID OT: {$firstNote['work_order_id']}");
    $this->line("   - Correlativo: {$firstNote['work_order_correlative']}");
    $this->newLine();

    $this->line("2. Verifica la nota interna:");
    $this->line("   - ID NI: {$firstNote['internal_note_id']}");
    $this->line("   - Número: {$firstNote['internal_note_number']}");
    $this->newLine();

    $this->line("3. Ve a 'Movimientos de Inventario' y busca:");
    $this->line("   - Movimientos asociados a la nota interna {$firstNote['internal_note_number']}");
    $this->line("   - Tipo de movimiento: AJUSTE OUT (para INTERNA_SC) o VENTA (para INTERNA_CC)");
    $this->newLine();

    $this->line("4. Si NO encuentras el movimiento:");
    $this->line("   - Confirma que la OT tiene tipo de documento: {$firstNote['type_document']}");
    if ($firstNote['type_document'] === TypePlanningWorkOrder::INTERNA_SC) {
      $this->line("   - INTERNA_SC DEBE tener salida de inventario al crear la nota interna");
    } else {
      $this->line("   - INTERNA_CC DEBE tener salida de inventario al facturar (generar comprobante)");
      if ($firstNote['has_electronic_document']) {
        $this->line("   - Esta nota YA tiene comprobante: {$firstNote['electronic_document_numbers']}");
      }
    }
    $this->newLine();

    $this->warn("💡 SUGERENCIA:");
    $this->line("Puedes usar el comando de corrección que creamos anteriormente:");
    $this->line("  php artisan inventory:fix-internal-note-references --type=INTERNA_SC");
    $this->line("  php artisan inventory:fix-internal-note-references --type=INTERNA_CC");
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
