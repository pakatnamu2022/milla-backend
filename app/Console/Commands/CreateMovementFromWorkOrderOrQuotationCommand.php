<?php

namespace App\Console\Commands;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateMovementFromWorkOrderOrQuotationCommand extends Command
{
  protected $signature = 'inventory:create-from-document {document_id : ID del comprobante electrónico}';

  protected $description = 'Crea movimiento de inventario desde un comprobante (solo registros, sin mover stock ni estados)';

  public function handle(): int
  {
    $this->info("╔═══════════════════════════════════════════════════════════════════════════╗");
    $this->info("║   CREAR MOVIMIENTO DESDE COMPROBANTE (SOLO REGISTROS)                    ║");
    $this->info("╚═══════════════════════════════════════════════════════════════════════════╝");
    $this->newLine();

    $documentId = $this->argument('document_id');

    // Buscar el comprobante
    $document = ElectronicDocument::find($documentId);

    if (!$document) {
      $this->error("❌ No se encontró el comprobante con ID {$documentId}");
      return 1;
    }

    // Mostrar información del comprobante
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📄 INFORMACIÓN DEL COMPROBANTE");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->table(
      ['Campo', 'Valor'],
      [
        ['ID', $document->id],
        ['Número', $document->full_number],
        ['Fecha emisión', $document->fecha_de_emision],
        ['Tipo consolidación', $document->consolidation_type ?? 'simple'],
        ['work_order_id', $document->work_order_id ?? 'NULL'],
        ['order_quotation_id', $document->order_quotation_id ?? 'NULL'],
      ]
    );

    // Variables para almacenar qué procesar
    $workOrderIdToProcess = null;
    $quotationIdToProcess = null;

    // Determinar qué procesar según tipo de comprobante
    if ($document->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      // MASIVO: Pedir ID de la OT
      $internalNotes = $document->internalNotes()->get();
      $workOrderIds = $internalNotes->where('work_order_id', '!=', null)->pluck('work_order_id')->toArray();

      $this->newLine();
      $this->info("📦 COMPROBANTE MASIVO");
      $this->info("   Total de notas internas con OT: " . count($workOrderIds));

      if (empty($workOrderIds)) {
        $this->error("   ❌ No se encontraron OTs en las notas internas");
        return 1;
      }

      $this->info("   OTs disponibles: " . implode(', ', $workOrderIds));
      $this->newLine();

      $workOrderIdToProcess = $this->ask('¿ID de la Orden de Trabajo que deseas procesar?');

      if (!in_array($workOrderIdToProcess, $workOrderIds)) {
        $this->error("❌ La OT {$workOrderIdToProcess} NO está en las notas internas de este comprobante masivo");
        $this->error("   OTs válidas: " . implode(', ', $workOrderIds));
        return 1;
      }

      $this->info("✓ OT {$workOrderIdToProcess} encontrada en el comprobante masivo");
      $this->newLine();

    } else {
      // SIMPLE: Detectar automáticamente
      if ($document->work_order_id) {
        $workOrderIdToProcess = $document->work_order_id;
        $this->newLine();
        $this->info("📦 COMPROBANTE SIMPLE - Orden de Trabajo ID: {$workOrderIdToProcess}");
      } elseif ($document->order_quotation_id) {
        $quotationIdToProcess = $document->order_quotation_id;
        $this->newLine();
        $this->info("📦 COMPROBANTE SIMPLE - Cotización ID: {$quotationIdToProcess}");
      } else {
        $this->error("❌ El comprobante no tiene work_order_id ni order_quotation_id");
        return 1;
      }
    }

    // Cargar datos y mostrar PREVISUALIZACIÓN completa
    try {
      if ($workOrderIdToProcess) {
        // Previsualización de OT
        $previewData = $this->previewWorkOrder($document, $workOrderIdToProcess);
        if (!$previewData) {
          return 1;
        }
      } elseif ($quotationIdToProcess) {
        // Previsualización de Cotización
        $previewData = $this->previewQuotation($document, $quotationIdToProcess);
        if (!$previewData) {
          return 1;
        }
      } else {
        $this->error("❌ No se pudo determinar qué procesar");
        return 1;
      }

      // Mostrar advertencia
      $this->newLine();
      $this->warn("⚠️  IMPORTANTE:");
      $this->warn("   - Este comando SOLO creará registros en inventory_movements e inventory_movement_details");
      $this->warn("   - NO actualizará stock en product_warehouse_stock");
      $this->warn("   - NO actualizará output_generation_warehouse ni estados de OT/Cotización");
      $this->warn("   - Es solo para regularización de movimientos históricos");
      $this->newLine();

      // CONFIRMAR ANTES DE CREAR
      if (!$this->confirm('¿Deseas crear este movimiento?', true)) {
        $this->info('Operación cancelada');
        return 0;
      }

      // CREAR el movimiento
      $movement = $this->createMovementFromPreview($previewData);

      if (!$movement) {
        $this->error("❌ No se pudo crear el movimiento");
        return 1;
      }

      $this->newLine();
      $this->info("╔═══════════════════════════════════════════════════════════════════════════╗");
      $this->info("║                        ✓ MOVIMIENTO CREADO                                ║");
      $this->info("╚═══════════════════════════════════════════════════════════════════════════╝");
      $this->newLine();
      $this->info("ID del movimiento: {$movement->id}");
      $this->info("Número de movimiento: {$movement->movement_number}");
      $this->info("Total de productos: {$movement->total_items}");
      $this->info("Cantidad total: {$movement->total_quantity}");

      return 0;

    } catch (\Exception $e) {
      $this->error("❌ Error: {$e->getMessage()}");
      $this->error($e->getTraceAsString());
      return 1;
    }
  }

  /**
   * Mostrar previsualización de movimiento para Orden de Trabajo
   */
  private function previewWorkOrder(ElectronicDocument $document, int $workOrderId): ?array
  {
    $workOrder = ApWorkOrder::with(['parts.product', 'sede'])->find($workOrderId);

    if (!$workOrder) {
      $this->error("❌ No se encontró la OT {$workOrderId}");
      return null;
    }

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📋 PREVISUALIZACIÓN - ORDEN DE TRABAJO: {$workOrder->correlative}");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    // Obtener almacén
    $warehouse = Warehouse::where('sede_id', $workOrder->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("❌ No se encontró almacén físico para sede {$workOrder->sede_id}");
      return null;
    }

    // Filtrar productos
    $productParts = $workOrder->parts
      ->where('product_id', '!=', null)
      ->where('is_traverse', false);

    if ($productParts->isEmpty()) {
      $this->warn("⚠️  Sin repuestos para generar salida (solo servicios o travesía)");
      return null;
    }

    // Mostrar info del movimiento que se creará
    $this->table(
      ['Campo', 'Valor'],
      [
        ['OT ID', $workOrder->id],
        ['Correlativo', $workOrder->correlative],
        ['Almacén', "{$warehouse->name} (ID: {$warehouse->id})"],
        ['movement_type', InventoryMovement::TYPE_SALE],
        ['movement_date', $document->fecha_de_emision],
        ['Comprobante', $document->full_number],
        ['reference_type', ApWorkOrder::class],
        ['reference_id', $workOrder->id],
        ['electronic_document_id', $document->id],
        ['status', InventoryMovement::STATUS_APPROVED],
      ]
    );

    // Mostrar productos
    $this->newLine();
    $this->info("📦 REPUESTOS QUE SE INCLUIRÁN:");
    $rows = [];
    $totalItems = 0;
    $totalQuantity = 0;

    foreach ($productParts as $part) {
      $rows[] = [
        $part->product->code ?? 'N/A',
        $part->product->name ?? "ID {$part->product_id}",
        number_format($part->quantity_used, 2),
        number_format($part->unit_price, 2),
        number_format($part->net_amount, 2),
      ];
      $totalItems++;
      $totalQuantity += $part->quantity_used;
    }

    $this->table(['Código', 'Producto', 'Cantidad', 'Precio Unit.', 'Total'], $rows);
    $this->info("Total productos: {$totalItems} | Total cantidad: " . number_format($totalQuantity, 2));

    // Retornar datos para crear el movimiento
    return [
      'type' => 'work_order',
      'document' => $document,
      'workOrder' => $workOrder,
      'warehouse' => $warehouse,
      'productParts' => $productParts,
      'totalItems' => $totalItems,
      'totalQuantity' => $totalQuantity,
    ];
  }

  /**
   * Mostrar previsualización de movimiento para Cotización
   */
  private function previewQuotation(ElectronicDocument $document, int $quotationId): ?array
  {
    $quotation = ApOrderQuotations::with(['details.product', 'sede'])->find($quotationId);

    if (!$quotation) {
      $this->error("❌ No se encontró la cotización {$quotationId}");
      return null;
    }

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📋 PREVISUALIZACIÓN - COTIZACIÓN: {$quotation->quotation_number}");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    // Obtener almacén
    $warehouse = Warehouse::where('sede_id', $quotation->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("❌ No se encontró almacén físico para sede {$quotation->sede_id}");
      return null;
    }

    // Filtrar productos
    $productDetails = $quotation->details
      ->where('item_type', '!=', 'LABOR')
      ->where('product_id', '!=', null)
      ->where('is_traverse', false);

    if ($productDetails->isEmpty()) {
      $this->warn("⚠️  Sin productos para generar salida (solo servicios o travesía)");
      return null;
    }

    // Mostrar info del movimiento que se creará
    $this->table(
      ['Campo', 'Valor'],
      [
        ['Cotización ID', $quotation->id],
        ['Número', $quotation->quotation_number],
        ['Almacén', "{$warehouse->name} (ID: {$warehouse->id})"],
        ['movement_type', InventoryMovement::TYPE_SALE],
        ['movement_date', $document->fecha_de_emision],
        ['Comprobante', $document->full_number],
        ['reference_type', ApOrderQuotations::class],
        ['reference_id', $quotation->id],
        ['electronic_document_id', $document->id],
        ['status', InventoryMovement::STATUS_APPROVED],
      ]
    );

    // Mostrar productos
    $this->newLine();
    $this->info("📦 PRODUCTOS QUE SE INCLUIRÁN:");
    $rows = [];
    $totalItems = 0;
    $totalQuantity = 0;

    foreach ($productDetails as $detail) {
      $rows[] = [
        $detail->product->code ?? 'N/A',
        $detail->product->name ?? "ID {$detail->product_id}",
        number_format($detail->quantity, 2),
        number_format($detail->unit_price, 2),
        number_format($detail->total_cost, 2),
      ];
      $totalItems++;
      $totalQuantity += $detail->quantity;
    }

    $this->table(['Código', 'Producto', 'Cantidad', 'Precio Unit.', 'Total'], $rows);
    $this->info("Total productos: {$totalItems} | Total cantidad: " . number_format($totalQuantity, 2));

    // Retornar datos para crear el movimiento
    return [
      'type' => 'quotation',
      'document' => $document,
      'quotation' => $quotation,
      'warehouse' => $warehouse,
      'productDetails' => $productDetails,
      'totalItems' => $totalItems,
      'totalQuantity' => $totalQuantity,
    ];
  }

  /**
   * Crear el movimiento a partir de los datos de previsualización
   */
  private function createMovementFromPreview(array $previewData): ?InventoryMovement
  {
    DB::beginTransaction();
    try {
      $document = $previewData['document'];
      $warehouse = $previewData['warehouse'];
      $totalItems = $previewData['totalItems'];
      $totalQuantity = $previewData['totalQuantity'];

      $this->info("DEBUG: Iniciando creación del movimiento...");

      if ($previewData['type'] === 'work_order') {
        $workOrder = $previewData['workOrder'];
        $productParts = $previewData['productParts'];

        $this->info("DEBUG: Creando movimiento para OT {$workOrder->id}...");

        // Crear movimiento
        $movement = InventoryMovement::create([
          'movement_number' => InventoryMovement::generateMovementNumber(),
          'movement_type' => InventoryMovement::TYPE_SALE,
          'movement_date' => $document->fecha_de_emision,
          'warehouse_id' => $warehouse->id,
          'currency_id' => 1,
          'exchange_rate' => 1.00,
          'reference_type' => ApWorkOrder::class,
          'reference_id' => $workOrder->id,
          'electronic_document_id' => $document->id,
          'user_id' => $workOrder->created_by ?? 1,
          'status' => InventoryMovement::STATUS_APPROVED,
          'notes' => "Salida por venta - Orden de Trabajo {$workOrder->correlative}",
          'total_items' => $totalItems,
          'total_quantity' => $totalQuantity,
        ]);

        $this->info("DEBUG: Movimiento creado con ID: {$movement->id}");
        $this->info("DEBUG: Creando {$productParts->count()} detalles...");

        // Crear detalles
        foreach ($productParts as $part) {
          InventoryMovementDetail::create([
            'inventory_movement_id' => $movement->id,
            'product_id' => $part->product_id,
            'code' => $part->product->code,
            'description' => $part->product->name,
            'quantity' => $part->quantity_used,
            'unit_cost' => $part->unit_price,
            'total_cost' => $part->net_amount,
            'notes' => "Venta orden de trabajo {$workOrder->correlative}",
          ]);
        }

        $this->info("DEBUG: Detalles creados correctamente");

      } elseif ($previewData['type'] === 'quotation') {
        $quotation = $previewData['quotation'];
        $productDetails = $previewData['productDetails'];

        $this->info("DEBUG: Creando movimiento para Cotización {$quotation->id}...");

        // Crear movimiento
        $movement = InventoryMovement::create([
          'movement_number' => InventoryMovement::generateMovementNumber(),
          'movement_type' => InventoryMovement::TYPE_SALE,
          'movement_date' => $document->fecha_de_emision,
          'warehouse_id' => $warehouse->id,
          'currency_id' => 1,
          'exchange_rate' => 1.00,
          'reference_type' => ApOrderQuotations::class,
          'reference_id' => $quotation->id,
          'electronic_document_id' => $document->id,
          'user_id' => $quotation->created_by ?? 1,
          'status' => InventoryMovement::STATUS_APPROVED,
          'notes' => "Salida por venta - Cotización {$quotation->quotation_number}",
          'total_items' => $totalItems,
          'total_quantity' => $totalQuantity,
        ]);

        $this->info("DEBUG: Movimiento creado con ID: {$movement->id}");
        $this->info("DEBUG: Creando {$productDetails->count()} detalles...");

        // Crear detalles
        foreach ($productDetails as $detail) {
          InventoryMovementDetail::create([
            'inventory_movement_id' => $movement->id,
            'product_id' => $detail->product_id,
            'code' => $detail->product->code,
            'description' => $detail->description,
            'quantity' => $detail->quantity,
            'unit_cost' => $detail->unit_price,
            'total_cost' => $detail->total_cost,
            'notes' => "Venta cotización {$quotation->quotation_number}",
          ]);
        }

        $this->info("DEBUG: Detalles creados correctamente");

      } else {
        throw new \Exception("Tipo de previsualización no reconocido");
      }

      $this->info("DEBUG: Ejecutando commit...");

      // Mostrar información de la conexión ANTES del commit
      $connectionName = $movement->getConnectionName() ?? 'default';
      $this->info("DEBUG: Conexión del modelo: {$connectionName}");

      $dbConfig = config("database.connections.{$connectionName}");
      $this->info("DEBUG: Base de datos: " . ($dbConfig['database'] ?? 'N/A'));
      $this->info("DEBUG: Host: " . ($dbConfig['host'] ?? 'N/A'));

      DB::commit();
      $this->info("DEBUG: Commit ejecutado correctamente");

      // Verificar que el registro existe en la BD
      $exists = DB::table('inventory_movements')->where('id', $movement->id)->exists();
      $this->info("DEBUG: ¿Existe en BD después del commit? " . ($exists ? 'SÍ' : 'NO'));

      // Hacer un SELECT completo del registro
      $record = DB::table('inventory_movements')->where('id', $movement->id)->first();
      if ($record) {
        $this->info("DEBUG: Registro encontrado:");
        $this->info("  - ID: {$record->id}");
        $this->info("  - movement_number: {$record->movement_number}");
        $this->info("  - warehouse_id: {$record->warehouse_id}");
        $this->info("  - created_at: {$record->created_at}");
      } else {
        $this->error("DEBUG: ¡NO se encontró el registro con SELECT!");
      }

      // Contar total de registros en la tabla
      $totalRecords = DB::table('inventory_movements')->count();
      $this->info("DEBUG: Total de registros en inventory_movements: {$totalRecords}");

      return $movement;

    } catch (\Exception $e) {
      DB::rollBack();
      $this->error("❌ Error al crear movimiento: {$e->getMessage()}");
      $this->error("Stack trace: " . $e->getTraceAsString());
      return null;
    }
  }
}