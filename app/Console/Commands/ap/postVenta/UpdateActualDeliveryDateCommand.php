<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateActualDeliveryDateCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'work-orders:update-delivery-dates {--dry-run : Preview changes without applying them} {--force : Force the update without confirmation}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Actualiza el actual_delivery_date y official_closing_date de las órdenes de trabajo según su comprobante final o nota interna';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $isDryRun = $this->option('dry-run');
    $isForced = $this->option('force');

    $this->info('🔍 Analizando órdenes de trabajo...');
    $this->newLine();

    // Obtener todas las actualizaciones necesarias
    $updates = $this->getUpdates();

    // Categorizar
    $matching = $updates->where('status', 'match');
    $different = $updates->where('status', 'different');
    $empty = $updates->where('status', 'empty');

    // Mostrar resumen
    $this->showSummary($matching, $different, $empty);

    if ($isDryRun) {
      $this->newLine();
      $this->info('✅ Preview completado. Usa el comando sin --dry-run para aplicar los cambios.');
      return 0;
    }

    // Confirmar antes de ejecutar
    if ($different->count() + $empty->count() === 0) {
      $this->info('✅ Todas las fechas ya están correctas. No hay nada que actualizar.');
      return 0;
    }

    $this->newLine();
    // Si se usa --force, omitir la confirmación
    if (!$isForced && !$this->confirm('¿Deseas proceder con la actualización de ' . ($different->count() + $empty->count()) . ' órdenes de trabajo?')) {
      $this->warn('❌ Actualización cancelada.');
      return 0;
    }

    // Ejecutar actualización
    $this->executeUpdate($different->merge($empty));

    $this->newLine();
    $this->info('✅ Actualización completada exitosamente.');

    return 0;
  }

  /**
   * Obtiene todas las actualizaciones necesarias
   */
  private function getUpdates()
  {
    $updates = collect();

    // 1. Órdenes con factura final DIRECTA (simple)
    $this->info('📋 Procesando órdenes con factura final directa...');
    $simpleInvoices = $this->getWorkOrdersWithSimpleInvoice();
    foreach ($simpleInvoices as $data) {
      $updates->push($this->analyzeWorkOrder($data['workOrder'], $data['date'], 'Factura Final Directa'));
    }

    // 2. Órdenes con factura CONSOLIDADA/MASIVA
    $this->info('📋 Procesando órdenes con factura consolidada/masiva...');
    $massiveInvoices = $this->getWorkOrdersWithMassiveInvoice();
    foreach ($massiveInvoices as $data) {
      $updates->push($this->analyzeWorkOrder($data['workOrder'], $data['date'], 'Factura Consolidada/Masiva'));
    }

    // 3. Órdenes con nota interna SIN comprobante (excepto tipos especiales)
    $this->info('📋 Procesando órdenes con nota interna sin comprobante...');
    $internalNotes = $this->getWorkOrdersWithInternalNoteOnly();
    foreach ($internalNotes as $data) {
      $updates->push($this->analyzeWorkOrder($data['workOrder'], $data['date'], 'Nota Interna'));
    }

    return $updates;
  }

  /**
   * Obtiene órdenes con factura final directa (simple)
   * Agrupa por work_order_id y toma la factura más reciente
   */
  private function getWorkOrdersWithSimpleInvoice()
  {
    $documents = ElectronicDocument::query()
      ->with(['workOrder'])
      ->where('anulado', false)
      ->where('is_advance_payment', false) // Solo facturas finales
      ->whereNotNull('work_order_id') // Facturación SIMPLE
      ->whereIn('sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA
      ])
      ->orderBy('fecha_de_emision', 'desc') // Más recientes primero
      ->get();

    // Agrupar por work_order_id y tomar solo la factura más reciente de cada OT
    $groupedByWorkOrder = [];
    foreach ($documents as $document) {
      if ($document->workOrder && !isset($groupedByWorkOrder[$document->work_order_id])) {
        $groupedByWorkOrder[$document->work_order_id] = [
          'workOrder' => $document->workOrder,
          'date' => $document->fecha_de_emision,
        ];
      }
    }

    return array_values($groupedByWorkOrder);
  }

  /**
   * Obtiene órdenes con factura consolidada/masiva
   * Agrupa por work_order_id y toma la factura más reciente
   */
  private function getWorkOrdersWithMassiveInvoice()
  {
    $documents = ElectronicDocument::query()
      ->with(['internalNotes.workOrder'])
      ->where('anulado', false)
      ->where('is_advance_payment', false) // Solo facturas finales
      ->whereHas('internalNotes', function ($q) {
        $q->where('status', 'invoiced');
      })
      ->whereIn('sunat_concept_document_type_id', [
        ElectronicDocument::TYPE_FACTURA,
        ElectronicDocument::TYPE_BOLETA
      ])
      ->orderBy('fecha_de_emision', 'desc') // Más recientes primero
      ->get();

    // Agrupar por work_order_id y tomar solo la factura más reciente de cada OT
    $groupedByWorkOrder = [];
    foreach ($documents as $document) {
      if ($document->internalNotes && $document->internalNotes->count() > 0) {
        foreach ($document->internalNotes as $internalNote) {
          if ($internalNote->workOrder) {
            $workOrderId = $internalNote->workOrder->id;
            // Solo agregar si no existe o si esta factura es más reciente
            if (!isset($groupedByWorkOrder[$workOrderId])) {
              $groupedByWorkOrder[$workOrderId] = [
                'workOrder' => $internalNote->workOrder,
                'date' => $document->fecha_de_emision,
              ];
            }
          }
        }
      }
    }

    return array_values($groupedByWorkOrder);
  }

  /**
   * Obtiene órdenes con nota interna SIN comprobante
   * (excepto TYPE_PLANNING_DERCO_WARRANTY_ID y TYPE_PLANNING_ODEBRECHT_MAINTENANCE)
   */
  private function getWorkOrdersWithInternalNoteOnly()
  {
    $workOrders = ApWorkOrder::query()
      ->with(['internalNotes'])
      ->where('status_id', ApMasters::CLOSED_WORK_ORDER_ID)
      ->whereHas('internalNotes', function ($q) {
        $q->whereNotNull('number');
      })
      ->whereHas('items', function ($q) {
        $q->whereHas('typePlanning', function ($subQ) {
          $subQ->whereIn('type_document', [TypePlanningWorkOrder::INTERNA_CC, TypePlanningWorkOrder::INTERNA_SC])
            ->whereNotIn('id', [
              TypePlanningWorkOrder::TYPE_PLANNING_DERCO_WARRANTY_ID,
              TypePlanningWorkOrder::TYPE_PLANNING_ODEBRECHT_MAINTENANCE,
            ]);
        });
      })
      ->whereNotExists(function ($query) {
        $query->select(DB::raw(1))
          ->from('ap_billing_electronic_documents')
          ->whereColumn('ap_billing_electronic_documents.work_order_id', 'ap_work_orders.id')
          ->where('ap_billing_electronic_documents.anulado', false);
      })
      ->whereDoesntHave('internalNotes', function ($q) {
        $q->whereHas('electronicDocuments');
      })
      ->get();

    $results = [];
    foreach ($workOrders as $workOrder) {
      // Obtener la primera nota interna con fecha
      $internalNote = $workOrder->internalNotes->first();
      if ($internalNote && $internalNote->created_at) {
        $results[] = [
          'workOrder' => $workOrder,
          'date' => $internalNote->created_at,
        ];
      }
    }

    return $results;
  }

  /**
   * Analiza una orden de trabajo y determina su estado
   */
  private function analyzeWorkOrder($workOrder, $correctDate, $source)
  {
    $currentDeliveryDate = $workOrder->actual_delivery_date;
    $currentClosingDate = $workOrder->official_closing_date;

    // Combinar fecha del documento/nota con hora del created_at
    $newDate = null;
    if ($correctDate && $workOrder->created_at) {
      $newDate = $correctDate->format('Y-m-d') . ' ' . $workOrder->created_at->format('H:i:s');
    }

    $deliveryStatus = $this->compareDate($currentDeliveryDate, $correctDate);
    $closingStatus = $this->compareDate($currentClosingDate, $correctDate);

    // Si cualquiera de los dos campos no coincide, la OT se marca para actualizar
    $status = 'match';
    if ($deliveryStatus === 'empty' || $closingStatus === 'empty') {
      $status = 'empty';
    } elseif ($deliveryStatus === 'different' || $closingStatus === 'different') {
      $status = 'different';
    }

    return [
      'workOrder' => $workOrder,
      'correlative' => $workOrder->correlative,
      'current_date' => $currentDeliveryDate ? $currentDeliveryDate->format('Y-m-d H:i:s') : null,
      'current_closing_date' => $currentClosingDate ? $currentClosingDate->format('Y-m-d H:i:s') : null,
      'correct_date' => $newDate,
      'status' => $status,
      'source' => $source,
    ];
  }

  /**
   * Compara la fecha actual de un campo contra la fecha correcta (solo día)
   */
  private function compareDate(?\Illuminate\Support\Carbon $currentDate, ?\Illuminate\Support\Carbon $correctDate): string
  {
    if ($currentDate === null) {
      return 'empty';
    }

    if ($correctDate && $currentDate->format('Y-m-d') !== $correctDate->format('Y-m-d')) {
      return 'different';
    }

    return 'match';
  }

  /**
   * Muestra el resumen de cambios
   */
  private function showSummary($matching, $different, $empty)
  {
    $this->newLine();
    $this->info('═══════════════════════════════════════════════════════════════');
    $this->info('                      RESUMEN DE ANÁLISIS                       ');
    $this->info('═══════════════════════════════════════════════════════════════');
    $this->newLine();

    // Coinciden
    $this->line("✅ <fg=green>COINCIDEN</> (ya correctas): <fg=yellow>{$matching->count()}</>");
    $this->newLine();

    // No coinciden
    $this->line("❌ <fg=red>NO COINCIDEN</> (necesitan actualización): <fg=yellow>{$different->count()}</>");
    if ($different->count() > 0) {
      $this->line('   Primeras 5:');
      foreach ($different->take(5) as $item) {
        $this->line("   • OT: <fg=cyan>{$item['correlative']}</> | {$item['source']}");
        $this->line("     Entrega actual: <fg=red>{$item['current_date']}</> → Nuevo: <fg=green>{$item['correct_date']}</>");
        $this->line("     Cierre actual: <fg=red>{$item['current_closing_date']}</> → Nuevo: <fg=green>{$item['correct_date']}</>");
      }
      if ($different->count() > 5) {
        $this->line("   ... y " . ($different->count() - 5) . " más");
      }
    }
    $this->newLine();

    // Vacías
    $this->line("⚠️  <fg=yellow>VACÍAS</> (sin fecha actual): <fg=yellow>{$empty->count()}</>");
    if ($empty->count() > 0) {
      $this->line('   Primeras 5:');
      foreach ($empty->take(5) as $item) {
        $this->line("   • OT: <fg=cyan>{$item['correlative']}</> | {$item['source']}");
        $this->line("     Entrega: {$item['current_date']} → <fg=green>{$item['correct_date']}</>");
        $this->line("     Cierre: {$item['current_closing_date']} → <fg=green>{$item['correct_date']}</>");
      }
      if ($empty->count() > 5) {
        $this->line("   ... y " . ($empty->count() - 5) . " más");
      }
    }

    $this->newLine();
    $this->info('═══════════════════════════════════════════════════════════════');
  }

  /**
   * Ejecuta la actualización
   */
  private function executeUpdate($updates)
  {
    $this->newLine();
    $this->info('🔄 Actualizando órdenes de trabajo...');

    $bar = $this->output->createProgressBar($updates->count());
    $bar->start();

    $updated = 0;
    foreach ($updates as $item) {
      try {
        $item['workOrder']->update([
          'actual_delivery_date' => $item['correct_date'],
          'official_closing_date' => $item['correct_date'],
        ]);
        $updated++;
      } catch (\Exception $e) {
        $this->error("Error al actualizar OT {$item['correlative']}: " . $e->getMessage());
      }
      $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);
    $this->info("✅ {$updated} órdenes de trabajo actualizadas correctamente.");
  }
}
