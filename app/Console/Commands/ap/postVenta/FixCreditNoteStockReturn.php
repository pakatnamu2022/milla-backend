<?php

namespace App\Console\Commands\ap\postVenta;

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixCreditNoteStockReturn extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'fix:credit-note-stock-return {electronic_document_id : ID del comprobante (Nota de Crédito)}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Retorna el stock al almacén basándose en movimientos RETURN_IN existentes de una NC masiva';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $documentId = $this->argument('electronic_document_id');

    $this->info("🔍 Buscando comprobante ID: {$documentId}");

    // Buscar el documento
    $document = ElectronicDocument::with(['originalDocument', 'internalNotes.workOrder'])->find($documentId);

    if (!$document) {
      $this->error("❌ No se encontró el comprobante con ID: {$documentId}");
      return 1;
    }

    // Validar que sea nota de crédito
    if ($document->sunat_concept_document_type_id !== ElectronicDocument::TYPE_NOTA_CREDITO) {
      $this->error("❌ El comprobante no es una Nota de Crédito");
      $this->info("   Tipo: {$document->sunat_concept_document_type_id}");
      return 1;
    }

    $this->info("✅ Nota de Crédito encontrada: {$document->full_number}");

    // Validar que tenga documento original
    if (!$document->originalDocument) {
      $this->error("❌ La NC no tiene documento original asociado");
      return 1;
    }

    $originalDocument = $document->originalDocument;
    $this->info("   Documento original: {$originalDocument->full_number}");

    // Validar que sea masiva
    if ($originalDocument->consolidation_type !== ElectronicDocument::CONSOLIDATION_MASSIVE) {
      $this->error("❌ El documento original NO es una factura masiva");
      $this->info("   Tipo consolidación: {$originalDocument->consolidation_type}");
      return 1;
    }

    $this->info("✅ Factura masiva confirmada");

    // Buscar movimientos RETURN_IN asociados a esta NC
    $movements = InventoryMovement::where('reference_type', ElectronicDocument::class)
      ->where('reference_id', $document->id)
      ->where('movement_type', InventoryMovement::TYPE_RETURN_IN)
      ->where('status', InventoryMovement::STATUS_APPROVED)
      ->with(['details.product', 'warehouse'])
      ->get();

    if ($movements->isEmpty()) {
      $this->warn("⚠️  No se encontraron movimientos RETURN_IN para esta NC");
      $this->info("   ¿Quieres verificar si se crearon los movimientos?");
      return 1;
    }

    // ==========================================
    // VERIFICAR SI YA SE EJECUTÓ ESTE COMANDO ANTES
    // ==========================================
    // Verificamos si algún movimiento tiene una marca en 'notes' indicando que ya se procesó
    $alreadyProcessed = $movements->first(function ($movement) {
      return str_contains($movement->notes ?? '', '[STOCK_RETORNADO_MANUALMENTE]');
    });

    if ($alreadyProcessed) {
      $this->warn("⚠️  ADVERTENCIA: Esta NC ya fue procesada anteriormente");
      $this->info("   El movimiento {$alreadyProcessed->movement_number} tiene la marca [STOCK_RETORNADO_MANUALMENTE]");
      $this->newLine();

      if (!$this->confirm("¿Estás SEGURO que quieres volver a ejecutar? Esto DUPLICARÁ el stock retornado")) {
        $this->warn("❌ Operación cancelada por seguridad");
        $this->info("   Si necesitas revertir, primero resta manualmente el stock antes de volver a ejecutar");
        return 1;
      }

      $this->warn("⚠️  CONTINUANDO BAJO TU RESPONSABILIDAD - El stock se sumará NUEVAMENTE");
      $this->newLine();
    }

    $this->info("📦 Movimientos RETURN_IN encontrados: {$movements->count()}");
    $this->newLine();

    // ==========================================
    // PASO 1: PREVISUALIZACIÓN
    // ==========================================
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("🔍 PREVISUALIZACIÓN DE CAMBIOS");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->newLine();

    $previewData = [];
    $totalProductsToUpdate = 0;
    $totalQuantityToReturn = 0;

    foreach ($movements as $movement) {
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("📋 Movimiento: {$movement->movement_number}");
      $this->info("📍 Almacén DESTINO: {$movement->warehouse->name}");
      $this->info("📅 Fecha: {$movement->movement_date->format('d/m/Y H:i')}");
      $this->info("📦 Total productos: {$movement->total_items}");
      $this->newLine();

      $movementPreview = [];

      foreach ($movement->details as $detail) {
        // Buscar el stock actual
        $currentStock = \App\Models\ap\postventa\gestionProductos\ProductWarehouseStock::where('product_id', $detail->product_id)
          ->where('warehouse_id', $movement->warehouse_id)
          ->first();

        $currentQuantity = $currentStock ? $currentStock->quantity : 0;
        $newQuantity = $currentQuantity + $detail->quantity;

        $movementPreview[] = [
          'Producto' => $detail->product->name ?? 'N/A',
          'Código' => $detail->product->code ?? 'N/A',
          'Stock Actual' => number_format($currentQuantity, 2),
          'A Retornar' => '+' . number_format($detail->quantity, 2),
          'Stock Final' => number_format($newQuantity, 2),
        ];

        $totalProductsToUpdate++;
        $totalQuantityToReturn += $detail->quantity;

        // Guardar para procesamiento posterior
        $previewData[] = [
          'movement' => $movement,
          'detail' => $detail,
          'current_qty' => $currentQuantity,
          'add_qty' => $detail->quantity,
          'final_qty' => $newQuantity,
        ];
      }

      $this->table(
        ['Producto', 'Código', 'Stock Actual', 'A Retornar', 'Stock Final'],
        $movementPreview
      );

      $this->comment("💡 Todos estos productos retornarán al almacén: {$movement->warehouse->name}");
      $this->newLine();
    }

    // Mostrar resumen de la previsualización
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📊 RESUMEN DE PREVISUALIZACIÓN");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📦 Total de productos a actualizar: {$totalProductsToUpdate}");
    $this->info("📈 Cantidad total a retornar: " . number_format($totalQuantityToReturn, 2));
    $this->info("🏢 Movimientos a procesar: {$movements->count()}");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->newLine();

    // ==========================================
    // PASO 2: CONFIRMAR EJECUCIÓN
    // ==========================================
    if (!$this->confirm("¿Confirmas que deseas EJECUTAR estos cambios y actualizar el stock?")) {
      $this->warn("❌ Operación cancelada por el usuario");
      $this->info("   No se realizó ningún cambio en la base de datos");
      return 0;
    }

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("⚙️  EJECUTANDO ACTUALIZACIÓN DE STOCK");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->newLine();

    // ==========================================
    // PASO 3: EJECUTAR ACTUALIZACIÓN DIRECTA
    // ==========================================
    $processedCount = 0;
    $errorCount = 0;
    $totalProductsUpdated = 0;

    // Agrupar todos los detalles por producto-almacén para actualizar una sola vez
    $stockUpdates = [];

    foreach ($movements as $movement) {
      foreach ($movement->details as $detail) {
        $key = $detail->product_id . '-' . $movement->warehouse_id;

        if (!isset($stockUpdates[$key])) {
          $stockUpdates[$key] = [
            'product_id' => $detail->product_id,
            'warehouse_id' => $movement->warehouse_id,
            'total_quantity' => 0,
            'product' => $detail->product,
          ];
        }

        $stockUpdates[$key]['total_quantity'] += $detail->quantity;
      }
    }

    // Ahora actualizar el stock de cada producto
    foreach ($stockUpdates as $update) {
      $productCode = $update['product']->code ?? 'N/A';
      $productName = $update['product']->name ?? 'N/A';

      $this->info("🔄 Actualizando stock: {$productCode} - {$productName}");
      $this->info("   Cantidad a retornar: " . number_format($update['total_quantity'], 2));

      try {
        // Buscar el registro de stock
        $stock = \App\Models\ap\postventa\gestionProductos\ProductWarehouseStock::where('product_id', $update['product_id'])
          ->where('warehouse_id', $update['warehouse_id'])
          ->first();

        if (!$stock) {
          $this->error("❌ No se encontró registro de stock para producto {$productCode}");
          $errorCount++;
          continue;
        }

        $stockBefore = $stock->quantity;
        $availableBefore = $stock->available_quantity;

        // ACTUALIZACIÓN DIRECTA como lo hace el usuario manualmente
        $stock->quantity += $update['total_quantity'];
        $stock->available_quantity += $update['total_quantity'];
        $stock->last_movement_date = now();
        $stock->save();

        $this->info("✅ Stock actualizado correctamente");
        $this->info("   Cantidad: {$stockBefore} → {$stock->quantity}");
        $this->info("   Disponible: {$availableBefore} → {$stock->available_quantity}");

        $totalProductsUpdated++;
        $processedCount++;

      } catch (\Exception $e) {
        $this->error("❌ Error al actualizar stock de {$productCode}:");
        $this->error("   {$e->getMessage()}");
        $errorCount++;
      }

      $this->newLine();
    }

    // ==========================================
    // PASO 4: MARCAR MOVIMIENTOS COMO PROCESADOS
    // ==========================================
    if ($errorCount === 0 && $totalProductsUpdated > 0) {
      $this->info("🏷️  Marcando movimientos como procesados...");

      foreach ($movements as $movement) {
        $existingNotes = $movement->notes ?? '';
        $newNotes = trim($existingNotes . "\n[STOCK_RETORNADO_MANUALMENTE] Procesado con comando fix:credit-note-stock-return el " . now()->format('Y-m-d H:i:s'));

        $movement->update(['notes' => $newNotes]);
      }

      $this->info("✅ Movimientos marcados correctamente");
      $this->newLine();
    }

    // Resumen final
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📊 RESUMEN FINAL");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("✅ Movimientos procesados: {$processedCount}/{$movements->count()}");
    $this->info("📦 Productos actualizados: {$totalProductsUpdated}");

    if ($errorCount > 0) {
      $this->error("❌ Errores encontrados: {$errorCount}");
    }

    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    return $errorCount > 0 ? 1 : 0;
  }
}