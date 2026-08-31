<?php

namespace App\Console\Commands;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateInventoryOutputCommand extends Command
{
  protected $signature = 'inventory:simulate-output {document_id : ID del comprobante electrónico}';

  protected $description = 'Simula (sin ejecutar) el proceso de salida de inventario para un comprobante';

  private int $totalProducts = 0;
  private int $totalProductsWithIssues = 0;
  private int $totalProductsOk = 0;
  private array $globalIssues = [];

  public function handle(): int
  {
    $documentId = $this->argument('document_id');

    $this->info("╔═══════════════════════════════════════════════════════════════════════════╗");
    $this->info("║           SIMULACIÓN DE SALIDA DE INVENTARIO (SIN EJECUTAR)              ║");
    $this->info("╚═══════════════════════════════════════════════════════════════════════════╝");
    $this->newLine();

    // Buscar el comprobante
    $document = ElectronicDocument::find($documentId);

    if (!$document) {
      $this->error("❌ No se encontró el comprobante con ID {$documentId}");
      return 1;
    }

    // Mostrar información del comprobante
    $this->showDocumentInfo($document);

    // Validar que sea de las áreas permitidas
    if (!in_array($document->area_id, [ApMasters::AREA_TALLER, ApMasters::AREA_MESON])) {
      $this->warn("⚠️  Este comando solo funciona para áreas 881 (Taller) y 882 (Mesón)");
      $this->info("   Área del comprobante: {$document->area_id}");
      return 1;
    }

    // Detectar si es una Nota de Crédito
    if ($document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO) {
      $this->newLine();
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("📋 TIPO DE DOCUMENTO: NOTA DE CRÉDITO");
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->simulateCreditNote($document);
      $this->showFinalSummary();
      return 0;
    }

    // Determinar tipo de procesamiento
    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📋 TIPO DE PROCESAMIENTO");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    if ($document->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $this->info("✓ Facturación MASIVA");
      $this->simulateMassiveInvoice($document);
    } elseif ($document->work_order_id) {
      $this->info("✓ Orden de Trabajo Individual");
      $this->simulateWorkOrder($document->work_order_id, $document);
    } elseif ($document->order_quotation_id) {
      $this->info("✓ Cotización de Mesón Individual");
      $this->simulateQuotation($document->order_quotation_id, $document);
    } else {
      $this->warn("⚠️  No se pudo determinar el tipo de procesamiento");
      return 1;
    }

    // Mostrar resumen final
    $this->showFinalSummary();

    return 0;
  }

  private function showDocumentInfo(ElectronicDocument $document): void
  {
    $this->info("📄 INFORMACIÓN DEL COMPROBANTE");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->table(
      ['Campo', 'Valor'],
      [
        ['ID', $document->id],
        ['Número', $document->full_number],
        ['Área', $this->getAreaName($document->area_id)],
        ['Tipo consolidación', $document->consolidation_type ?? 'N/A'],
        ['Es anticipo', $document->is_advance_payment ? 'SÍ' : 'NO'],
        ['Está contabilizada', $document->is_accounted ? 'SÍ' : 'NO'],
        ['Está anulada', $document->is_annulled ? 'SÍ' : 'NO'],
        ['Fecha emisión', $document->fecha_de_emision],
        ['Estado migración', $document->migration_status],
      ]
    );
  }

  private function getAreaName(int $areaId): string
  {
    return match ($areaId) {
      ApMasters::AREA_TALLER => "881 - TALLER",
      ApMasters::AREA_MESON => "882 - MESÓN/REPUESTOS",
      ApMasters::AREA_COMERCIAL => "COMERCIAL",
      default => "Área {$areaId}"
    };
  }

  private function simulateMassiveInvoice(ElectronicDocument $document): void
  {
    $internalNotes = $document->internalNotes()->get();

    $this->info("   Total de notas internas: " . $internalNotes->count());
    $this->newLine();

    $otIndex = 1;
    foreach ($internalNotes as $note) {
      if (!$note->work_order_id) {
        continue;
      }

      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("📦 OT #{$otIndex} - ID: {$note->work_order_id}");
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

      $this->simulateWorkOrder($note->work_order_id, $document, $otIndex);

      $this->newLine();
      $otIndex++;
    }
  }

  private function simulateWorkOrder(int $workOrderId, ElectronicDocument $document, ?int $index = null): void
  {
    $workOrder = ApWorkOrder::with(['parts.product', 'sede'])->find($workOrderId);

    if (!$workOrder) {
      $this->error("   ❌ No se encontró la OT {$workOrderId}");
      return;
    }

    // Información de la OT
    $this->table(
      ['Campo', 'Valor'],
      [
        ['Correlativo', $workOrder->correlative],
        ['Sede ID', $workOrder->sede_id],
        ['Ya generó salida', $workOrder->output_generation_warehouse ? '✓ SÍ' : '✗ NO'],
        ['Está facturada', $workOrder->is_invoiced ? '✓ SÍ' : '✗ NO'],
      ]
    );

    // Verificar si ya generó salida
    if ($workOrder->output_generation_warehouse) {
      $this->warn("   ⚠️  Esta OT ya generó salida de inventario previamente");
      $this->info("   → El Job la omitirá (no generará movimiento duplicado)");
      return;
    }

    // Obtener almacén
    $warehouse = Warehouse::where('sede_id', $workOrder->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("   ❌ ERROR: No se encontró almacén físico activo para sede {$workOrder->sede_id}");
      $this->globalIssues[] = "OT {$workOrder->correlative}: Sin almacén físico";
      return;
    }

    $this->info("   ✓ Almacén: ID {$warehouse->id}");

    // Filtrar productos
    $productParts = $workOrder->parts
      ->where('product_id', '!=', null)
      ->where('is_traverse', false);

    if ($productParts->isEmpty()) {
      $this->warn("   ⚠️  Esta OT no tiene repuestos (solo servicios o productos de travesía)");
      $this->info("   → No generará movimiento de inventario");
      $this->info("   → Pero SÍ se marcará como facturada y cerrada");
      return;
    }

    $this->newLine();
    $this->info("   🔍 SIMULACIÓN DE SALIDA DE STOCK (ÓRDENES DE TRABAJO)");
    $this->info("   ─────────────────────────────────────────────────────────────────────────");
    $this->info("   Tipo de reserva: SIEMPRE CON RESERVA PREVIA");
    $this->info("   Flujo: releaseReservedStockAndRemove()");
    $this->newLine();

    // Tabla de repuestos
    $rows = [];
    $hasIssues = false;

    foreach ($productParts as $part) {
      $this->totalProducts++;

      $stock = ProductWarehouseStock::where('product_id', $part->product_id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

      $productName = $part->product->name ?? "ID {$part->product_id}";
      $quantity = $part->quantity_used;

      if (!$stock) {
        $this->totalProductsWithIssues++;
        $hasIssues = true;
        $rows[] = [
          $productName,
          number_format($quantity, 2),
          '<fg=red>SIN REGISTRO</>',
          '<fg=red>-</>',
          '<fg=red>-</>',
          '<fg=red>❌ Sin registro de stock</>',
        ];
        $this->globalIssues[] = "OT {$workOrder->correlative}: Producto '{$productName}' sin registro de stock";
        continue;
      }

      // Simular validaciones
      $issues = [];
      $status = '<fg=green>✓ OK</>';

      // Validación 1: Stock reservado suficiente
      if ($stock->reserved_quantity < $quantity) {
        $issues[] = "Reserva insuf. (tiene {$stock->reserved_quantity}, necesita {$quantity})";
        $hasIssues = true;
        $this->totalProductsWithIssues++;
      }

      // Validación 2: Stock físico suficiente
      if ($stock->quantity < $quantity) {
        $issues[] = "Stock físico insuf. (tiene {$stock->quantity}, necesita {$quantity})";
        $hasIssues = true;
        $this->totalProductsWithIssues++;
      }

      if (!empty($issues)) {
        $status = '<fg=red>❌ ' . implode('; ', $issues) . '</>';
        $this->globalIssues[] = "OT {$workOrder->correlative}: Producto '{$productName}' - " . implode('; ', $issues);
      } else {
        $this->totalProductsOk++;
      }

      // Simular resultado después del proceso
      $newQuantity = $stock->quantity - $quantity;
      $newReserved = max(0, $stock->reserved_quantity - $quantity);
      $newAvailable = $newQuantity - $newReserved;

      $rows[] = [
        $productName,
        number_format($quantity, 2),
        number_format($stock->quantity, 2) . ' → ' . number_format($newQuantity, 2),
        number_format($stock->reserved_quantity, 2) . ' → ' . number_format($newReserved, 2),
        number_format($stock->available_quantity, 2) . ' → ' . number_format($newAvailable, 2),
        $status,
      ];
    }

    $this->table(
      ['Producto', 'Cantidad', 'Stock Físico (antes→después)', 'Reservado (antes→después)', 'Disponible (antes→después)', 'Estado'],
      $rows
    );

    if ($hasIssues) {
      $this->error("   ❌ Esta OT NO generará movimiento de inventario (hay errores)");
      $this->error("   → El Job capturará el error y continuará con las siguientes OTs");
    } else {
      $this->info("   ✓ Esta OT SÍ generará movimiento de inventario correctamente");
      $this->info("   → Se marcará como: output_generation_warehouse=1, is_invoiced=1, status=CLOSED");
    }
  }

  private function simulateQuotation(int $quotationId, ElectronicDocument $document): void
  {
    $quotation = ApOrderQuotations::with(['details.product', 'sede'])->find($quotationId);

    if (!$quotation) {
      $this->error("   ❌ No se encontró la cotización {$quotationId}");
      return;
    }

    // Información de la cotización
    $this->table(
      ['Campo', 'Valor'],
      [
        ['Número cotización', $quotation->quotation_number],
        ['Sede ID', $quotation->sede_id],
        ['Ya generó salida', $quotation->output_generation_warehouse ? '✓ SÍ' : '✗ NO'],
        ['Está confirmada', $quotation->confirmed_at ? '✓ SÍ' : '✗ NO'],
      ]
    );

    // Verificar si ya generó salida
    if ($quotation->output_generation_warehouse) {
      $this->warn("   ⚠️  Esta cotización ya generó salida de inventario previamente");
      $this->info("   → El Job la omitirá (no generará movimiento duplicado)");
      return;
    }

    // Obtener almacén
    $warehouse = Warehouse::where('sede_id', $quotation->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("   ❌ ERROR: No se encontró almacén físico activo para sede {$quotation->sede_id}");
      $this->globalIssues[] = "Cotización {$quotation->quotation_number}: Sin almacén físico";
      return;
    }

    $this->info("   ✓ Almacén: ID {$warehouse->id}");

    // Filtrar productos
    $productDetails = $quotation->details
      ->where('item_type', '!=', 'LABOR')
      ->where('product_id', '!=', null)
      ->where('is_traverse', false);

    if ($productDetails->isEmpty()) {
      $this->warn("   ⚠️  Esta cotización no tiene productos (solo servicios o productos de travesía)");
      $this->info("   → No generará movimiento de inventario");
      $this->info("   → Pero SÍ se marcará como facturada");
      return;
    }

    $this->newLine();
    $this->info("   🔍 SIMULACIÓN DE SALIDA DE STOCK (COTIZACIONES DE MESÓN)");
    $this->info("   ─────────────────────────────────────────────────────────────────────────");
    $this->newLine();

    // Tabla de productos
    $rows = [];
    $hasIssues = false;

    foreach ($productDetails as $detail) {
      $this->totalProducts++;

      $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

      $productName = $detail->product->name ?? "ID {$detail->product_id}";
      $quantity = $detail->quantity;
      $supplyType = $detail->supply_type;

      // Determinar si tiene reserva
      $hasReservation = $supplyType === ApOrderQuotationDetails::SUPPLY_TYPE_STOCK;
      $flowType = $hasReservation
        ? '<fg=yellow>CON RESERVA</> (releaseReservedStockAndRemove)'
        : '<fg=cyan>SIN RESERVA</> (removeStockWithoutReservation)';

      if (!$stock) {
        $this->totalProductsWithIssues++;
        $hasIssues = true;
        $rows[] = [
          $productName,
          $supplyType,
          $flowType,
          number_format($quantity, 2),
          '<fg=red>SIN REGISTRO</>',
          '<fg=red>-</>',
          '<fg=red>-</>',
          '<fg=red>❌ Sin registro de stock</>',
        ];
        $this->globalIssues[] = "Cotización {$quotation->quotation_number}: Producto '{$productName}' sin registro de stock";
        continue;
      }

      // Simular validaciones según tipo
      $issues = [];
      $status = '<fg=green>✓ OK</>';

      if ($hasReservation) {
        // supply_type = STOCK: Validar reserva y stock físico
        if ($stock->reserved_quantity < $quantity) {
          $issues[] = "Reserva insuf. (tiene {$stock->reserved_quantity}, necesita {$quantity})";
          $hasIssues = true;
          $this->totalProductsWithIssues++;
        }
        if ($stock->quantity < $quantity) {
          $issues[] = "Stock físico insuf. (tiene {$stock->quantity}, necesita {$quantity})";
          $hasIssues = true;
          $this->totalProductsWithIssues++;
        }
      } else {
        // supply_type != STOCK: Validar solo stock disponible
        if ($stock->available_quantity < $quantity) {
          $issues[] = "Stock disponible insuf. (tiene {$stock->available_quantity}, necesita {$quantity})";
          $hasIssues = true;
          $this->totalProductsWithIssues++;
        }
      }

      if (!empty($issues)) {
        $status = '<fg=red>❌ ' . implode('; ', $issues) . '</>';
        $this->globalIssues[] = "Cotización {$quotation->quotation_number}: Producto '{$productName}' - " . implode('; ', $issues);
      } else {
        $this->totalProductsOk++;
      }

      // Simular resultado después del proceso
      $newQuantity = $stock->quantity - $quantity;
      $newReserved = $hasReservation ? max(0, $stock->reserved_quantity - $quantity) : $stock->reserved_quantity;
      $newAvailable = $newQuantity - $newReserved;

      $rows[] = [
        $productName,
        $supplyType,
        $flowType,
        number_format($quantity, 2),
        number_format($stock->quantity, 2) . ' → ' . number_format($newQuantity, 2),
        number_format($stock->reserved_quantity, 2) . ' → ' . number_format($newReserved, 2),
        number_format($stock->available_quantity, 2) . ' → ' . number_format($newAvailable, 2),
        $status,
      ];
    }

    $this->table(
      ['Producto', 'Supply Type', 'Flujo', 'Cantidad', 'Stock Físico', 'Reservado', 'Disponible', 'Estado'],
      $rows
    );

    if ($hasIssues) {
      $this->error("   ❌ Esta cotización NO generará movimiento de inventario (hay errores)");
      $this->error("   → El Job capturará el error y la cotización quedará sin procesar");
    } else {
      $this->info("   ✓ Esta cotización SÍ generará movimiento de inventario correctamente");
      $this->info("   → Se marcará como: output_generation_warehouse=1, status=FACTURADO");
    }
  }

  private function simulateCreditNote(ElectronicDocument $creditNote): void
  {
    // Obtener documento original
    $originalDocument = $creditNote->originalDocument;

    if (!$originalDocument) {
      $this->error("❌ No se encontró el documento original para esta NC");
      return;
    }

    $this->table(
      ['Campo', 'Valor'],
      [
        ['Tipo de NC', $creditNote->sunat_concept_credit_note_type_id],
        ['Documento Original', $originalDocument->full_number],
        ['Es Anticipo', $originalDocument->is_advance_payment ? 'SÍ' : 'NO'],
        ['re_invoice', $creditNote->re_invoice ? '<fg=yellow>TRUE</> (Re-facturará)' : '<fg=cyan>FALSE</> (NO re-facturará)'],
      ]
    );

    // Solo procesar si el documento original NO es anticipo
    if ($originalDocument->is_advance_payment) {
      $this->warn("⚠️  El documento original es un ANTICIPO");
      $this->info("   → Los anticipos NO generan movimiento de inventario");
      $this->info("   → Esta NC NO afectará el inventario");
      return;
    }

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📦 SIMULACIÓN DE REVERSIÓN DE STOCK");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    // Simular según tipo de documento original
    if ($originalDocument->work_order_id) {
      $this->info("✓ Documento original: ORDEN DE TRABAJO ID {$originalDocument->work_order_id}");
      $this->simulateCreditNoteForWorkOrder($creditNote, $originalDocument);
    } elseif ($originalDocument->order_quotation_id) {
      $this->info("✓ Documento original: COTIZACIÓN ID {$originalDocument->order_quotation_id}");
      $this->simulateCreditNoteForQuotation($creditNote, $originalDocument);
    } elseif ($originalDocument->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $this->info("✓ Documento original: FACTURA MASIVA");
      $this->simulateCreditNoteForMassive($creditNote, $originalDocument);
    } else {
      $this->warn("⚠️  No se pudo determinar el tipo de documento original");
    }

    // Simular re-reserva si re_invoice = true
    if ($creditNote->re_invoice) {
      $this->newLine();
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("🔄 RE-RESERVA AUTOMÁTICA (re_invoice = TRUE)");
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("   Como re_invoice = TRUE, después de devolver el stock al almacén,");
      $this->info("   el sistema RE-RESERVARÁ automáticamente el stock para refacturación.");
      $this->newLine();
      $this->info("   <fg=green>✓ Stock regresará a RESERVADO (no a disponible)</>");
      $this->info("   <fg=green>✓ Cuando se contabilice el nuevo comprobante, NO afectará el reservado</>");
      $this->info("   <fg=green>✓ Solo consumirá el stock físico y liberará la reserva</>");
    } else {
      $this->newLine();
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("📍 SIN RE-RESERVA (re_invoice = FALSE)");
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("   Como re_invoice = FALSE, el stock regresará completamente disponible.");
      $this->newLine();
      $this->info("   <fg=cyan>✓ Stock regresará a DISPONIBLE (NO reservado)</>");
      $this->info("   <fg=cyan>✓ NO se marcará para re-facturación</>");
    }
  }

  private function simulateCreditNoteForWorkOrder(ElectronicDocument $creditNote, ElectronicDocument $originalDocument): void
  {
    $workOrder = ApWorkOrder::with(['parts.product', 'sede'])->find($originalDocument->work_order_id);

    if (!$workOrder) {
      $this->error("   ❌ No se encontró la OT {$originalDocument->work_order_id}");
      return;
    }

    $this->info("   OT: {$workOrder->correlative}");
    $this->info("   Ya generó salida: " . ($workOrder->output_generation_warehouse ? 'SÍ' : 'NO'));

    if (!$workOrder->output_generation_warehouse) {
      $this->warn("   ⚠️  Esta OT NO ha generado salida de inventario aún");
      $this->info("   → NO hay nada que revertir");
      return;
    }

    $warehouse = Warehouse::where('sede_id', $workOrder->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("   ❌ No se encontró almacén");
      return;
    }

    $productParts = $workOrder->parts
      ->where('product_id', '!=', null)
      ->where('is_traverse', false);

    if ($productParts->isEmpty()) {
      $this->info("   → Sin repuestos para revertir");
      return;
    }

    $this->newLine();
    $this->info("   Productos que REGRESARÁN al almacén:");
    $rows = [];

    foreach ($productParts as $part) {
      $stock = ProductWarehouseStock::where('product_id', $part->product_id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

      $productName = $part->product->name ?? "ID {$part->product_id}";
      $quantity = $part->quantity_used;

      if (!$stock) {
        $rows[] = [
          $productName,
          number_format($quantity, 2),
          '<fg=red>SIN REGISTRO</>',
          '<fg=red>-</>',
        ];
        continue;
      }

      // Simular devolución
      $newQuantity = $stock->quantity + $quantity;

      $rows[] = [
        $productName,
        number_format($quantity, 2),
        number_format($stock->quantity, 2) . ' → ' . number_format($newQuantity, 2),
        '<fg=green>✓ Se devolverá</>',
      ];
    }

    $this->table(
      ['Producto', 'Cantidad', 'Stock Físico (antes→después)', 'Estado'],
      $rows
    );
  }

  private function simulateCreditNoteForQuotation(ElectronicDocument $creditNote, ElectronicDocument $originalDocument): void
  {
    $quotation = ApOrderQuotations::with(['details.product', 'sede'])->find($originalDocument->order_quotation_id);

    if (!$quotation) {
      $this->error("   ❌ No se encontró la cotización {$originalDocument->order_quotation_id}");
      return;
    }

    $this->info("   Cotización: {$quotation->quotation_number}");
    $this->info("   Ya generó salida: " . ($quotation->output_generation_warehouse ? 'SÍ' : 'NO'));

    if (!$quotation->output_generation_warehouse) {
      $this->warn("   ⚠️  Esta cotización NO ha generado salida de inventario aún");
      $this->info("   → NO hay nada que revertir");
      return;
    }

    $warehouse = Warehouse::where('sede_id', $quotation->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("   ❌ No se encontró almacén");
      return;
    }

    $productDetails = $quotation->details
      ->where('item_type', '!=', 'LABOR')
      ->where('product_id', '!=', null)
      ->where('is_traverse', false);

    if ($productDetails->isEmpty()) {
      $this->info("   → Sin productos para revertir");
      return;
    }

    $this->newLine();
    $this->info("   Productos que REGRESARÁN al almacén:");
    $rows = [];

    foreach ($productDetails as $detail) {
      $stock = ProductWarehouseStock::where('product_id', $detail->product_id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

      $productName = $detail->product->name ?? "ID {$detail->product_id}";
      $quantity = $detail->quantity;

      if (!$stock) {
        $rows[] = [
          $productName,
          number_format($quantity, 2),
          '<fg=red>SIN REGISTRO</>',
          '<fg=red>-</>',
        ];
        continue;
      }

      // Simular devolución
      $newQuantity = $stock->quantity + $quantity;

      $rows[] = [
        $productName,
        number_format($quantity, 2),
        number_format($stock->quantity, 2) . ' → ' . number_format($newQuantity, 2),
        '<fg=green>✓ Se devolverá</>',
      ];
    }

    $this->table(
      ['Producto', 'Cantidad', 'Stock Físico (antes→después)', 'Estado'],
      $rows
    );
  }

  private function simulateCreditNoteForMassive(ElectronicDocument $creditNote, ElectronicDocument $originalDocument): void
  {
    $internalNotes = $originalDocument->internalNotes()->get();

    $this->info("   Total de OTs en factura masiva: " . $internalNotes->count());
    $this->newLine();

    $otIndex = 1;
    foreach ($internalNotes as $note) {
      if (!$note->work_order_id) {
        continue;
      }

      $this->info("   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("   📦 OT #{$otIndex} - ID: {$note->work_order_id}");
      $this->info("   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

      $this->simulateCreditNoteForWorkOrder($creditNote, $originalDocument);

      $this->newLine();
      $otIndex++;
    }
  }

  private function showFinalSummary(): void
  {
    $this->newLine();
    $this->info("╔═══════════════════════════════════════════════════════════════════════════╗");
    $this->info("║                           RESUMEN FINAL                                   ║");
    $this->info("╚═══════════════════════════════════════════════════════════════════════════╝");
    $this->newLine();

    $this->table(
      ['Métrica', 'Cantidad'],
      [
        ['Total de productos analizados', $this->totalProducts],
        ['Productos que procesarán OK', "<fg=green>{$this->totalProductsOk}</>"],
        ['Productos con problemas', "<fg=red>{$this->totalProductsWithIssues}</>"],
      ]
    );

    if (!empty($this->globalIssues)) {
      $this->newLine();
      $this->error("❌ PROBLEMAS DETECTADOS:");
      foreach ($this->globalIssues as $issue) {
        $this->error("   • {$issue}");
      }
      $this->newLine();
      $this->warn("⚠️  IMPORTANTE: Estos errores impedirán que se genere el movimiento de inventario.");
      $this->warn("   El Job continuará procesando otras OTs/cotizaciones y registrará estos errores en el log.");
    } else {
      $this->newLine();
      $this->info("✓ ¡Todo está correcto! Este comprobante generará movimientos de inventario sin problemas.");
    }
  }
}
