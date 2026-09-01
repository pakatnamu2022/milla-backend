<?php

namespace App\Console\Commands;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use App\Models\ap\postventa\taller\ApWorkOrder;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateMovementFromWorkOrderOrQuotationCommand extends Command
{
  protected $signature = 'inventory:create-from-document {document_id : ID del comprobante electrónico}';

  protected $description = 'Crea movimiento de inventario desde un comprobante o nota de crédito (solo registros, sin mover stock ni estados)';

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

    // Determinar si es nota de crédito
    $isCreditNote = $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO;
    $originalDocument = null;

    if ($isCreditNote) {
      // Buscar documento original
      $originalDocument = $document->originalDocument;
      if (!$originalDocument) {
        $this->error("❌ La nota de crédito no tiene un documento original asociado");
        return 1;
      }

      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("📄 INFORMACIÓN - NOTA DE CRÉDITO");
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

      $creditNoteTypeLabel = 'Desconocido';
      if ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_ANULACION) {
        $creditNoteTypeLabel = 'Anulación de operación (01)';
      } elseif ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL) {
        $creditNoteTypeLabel = 'Devolución total (06)';
      } elseif ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_ITEM) {
        $creditNoteTypeLabel = 'Devolución por ítem (02)';
      }

      $this->table(
        ['Campo', 'Valor'],
        [
          ['ID NC', $document->id],
          ['Número NC', $document->full_number],
          ['Tipo NC', $creditNoteTypeLabel],
          ['Fecha emisión', $document->fecha_de_emision],
          ['Doc. Original', $originalDocument->full_number],
          ['Doc. Original ID', $originalDocument->id],
          ['Tipo consolidación', $originalDocument->consolidation_type ?? 'simple'],
          ['work_order_id', $originalDocument->work_order_id ?? 'NULL'],
          ['order_quotation_id', $originalDocument->order_quotation_id ?? 'NULL'],
        ]
      );
    } else {
      // Mostrar información del comprobante normal
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
    }

    // Variables para almacenar qué procesar
    $workOrderIdToProcess = null;
    $quotationIdToProcess = null;

    // Si es NC, usar el documento original para obtener referencias
    $documentToCheck = $isCreditNote ? $originalDocument : $document;

    // Determinar qué procesar según tipo de comprobante
    if ($documentToCheck->consolidation_type === ElectronicDocument::CONSOLIDATION_MASSIVE) {
      // MASIVO: Pedir ID de la OT
      $internalNotes = $documentToCheck->internalNotes()->get();
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
      if ($documentToCheck->work_order_id) {
        $workOrderIdToProcess = $documentToCheck->work_order_id;
        $this->newLine();
        $this->info("📦 COMPROBANTE SIMPLE - Orden de Trabajo ID: {$workOrderIdToProcess}");
      } elseif ($documentToCheck->order_quotation_id) {
        $quotationIdToProcess = $documentToCheck->order_quotation_id;
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

    // Detectar si es nota de crédito
    $isCreditNote = $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO;

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    if ($isCreditNote) {
      $this->info("📋 PREVISUALIZACIÓN - DEVOLUCIÓN POR NC - OT: {$workOrder->correlative}");
    } else {
      $this->info("📋 PREVISUALIZACIÓN - ORDEN DE TRABAJO: {$workOrder->correlative}");
    }
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
      if ($isCreditNote) {
        $this->warn("⚠️  Sin repuestos para generar devolución (solo servicios o travesía)");
      } else {
        $this->warn("⚠️  Sin repuestos para generar salida (solo servicios o travesía)");
      }
      return null;
    }

    // Determinar tipo de movimiento y referencia según sea NC o no
    $movementType = $isCreditNote ? InventoryMovement::TYPE_RETURN_IN : InventoryMovement::TYPE_SALE;
    $referenceType = $isCreditNote ? ElectronicDocument::class : ApWorkOrder::class;
    $referenceId = $isCreditNote ? $document->id : $workOrder->id;

    // Mostrar info del movimiento que se creará
    $this->table(
      ['Campo', 'Valor'],
      [
        ['OT ID', $workOrder->id],
        ['Correlativo', $workOrder->correlative],
        ['Almacén', "{$warehouse->name} (ID: {$warehouse->id})"],
        ['movement_type', $movementType . ($isCreditNote ? ' (Devolución Entrada)' : ' (Salida)')],
        ['movement_date', $document->fecha_de_emision],
        ['Comprobante', $document->full_number],
        ['reference_type', $referenceType],
        ['reference_id', $referenceId],
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

    // Detectar si es nota de crédito
    $isCreditNote = $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO;

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    if ($isCreditNote) {
      $this->info("📋 PREVISUALIZACIÓN - DEVOLUCIÓN POR NC - COTIZACIÓN: {$quotation->quotation_number}");
    } else {
      $this->info("📋 PREVISUALIZACIÓN - COTIZACIÓN: {$quotation->quotation_number}");
    }
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
      if ($isCreditNote) {
        $this->warn("⚠️  Sin productos para generar devolución (solo servicios o travesía)");
      } else {
        $this->warn("⚠️  Sin productos para generar salida (solo servicios o travesía)");
      }
      return null;
    }

    // Determinar tipo de movimiento y referencia según sea NC o no
    $movementType = $isCreditNote ? InventoryMovement::TYPE_RETURN_IN : InventoryMovement::TYPE_SALE;
    $referenceType = $isCreditNote ? ElectronicDocument::class : ApOrderQuotations::class;
    $referenceId = $isCreditNote ? $document->id : $quotation->id;

    // Mostrar info del movimiento que se creará
    $this->table(
      ['Campo', 'Valor'],
      [
        ['Cotización ID', $quotation->id],
        ['Número', $quotation->quotation_number],
        ['Almacén', "{$warehouse->name} (ID: {$warehouse->id})"],
        ['movement_type', $movementType . ($isCreditNote ? ' (Devolución Entrada)' : ' (Salida)')],
        ['movement_date', $document->fecha_de_emision],
        ['Comprobante', $document->full_number],
        ['reference_type', $referenceType],
        ['reference_id', $referenceId],
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

      // Detectar si es nota de crédito
      $isCreditNote = $document->sunat_concept_document_type_id === ElectronicDocument::TYPE_NOTA_CREDITO;

      $this->info("DEBUG: Iniciando creación del movimiento...");
      $this->info("DEBUG: Es nota de crédito: " . ($isCreditNote ? 'SÍ' : 'NO'));

      if ($previewData['type'] === 'work_order') {
        $workOrder = $previewData['workOrder'];
        $productParts = $previewData['productParts'];

        $this->info("DEBUG: Creando movimiento para OT {$workOrder->id}...");

        // Determinar descripción de NC si aplica
        $creditNoteDescription = 'Desconocido';
        if ($isCreditNote) {
          if ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_ANULACION) {
            $creditNoteDescription = 'Anulación de operación';
          } elseif ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL) {
            $creditNoteDescription = 'Devolución total';
          } elseif ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_ITEM) {
            $creditNoteDescription = 'Devolución por ítem';
          }
        }

        // Configurar según sea NC o factura normal
        $movementType = $isCreditNote ? InventoryMovement::TYPE_RETURN_IN : InventoryMovement::TYPE_SALE;
        $referenceType = $isCreditNote ? ElectronicDocument::class : ApWorkOrder::class;
        $referenceId = $isCreditNote ? $document->id : $workOrder->id;
        $notes = $isCreditNote
          ? "Devolución por NC {$document->full_number} - {$creditNoteDescription} - {$workOrder->correlative}"
          : "Salida por venta - Orden de Trabajo {$workOrder->correlative}";

        // Crear movimiento
        $movement = InventoryMovement::create([
          'movement_number' => InventoryMovement::generateMovementNumber(),
          'movement_type' => $movementType,
          'movement_date' => $document->fecha_de_emision,
          'warehouse_id' => $warehouse->id,
          'currency_id' => 1,
          'exchange_rate' => 1.00,
          'reference_type' => $referenceType,
          'reference_id' => $referenceId,
          'electronic_document_id' => $document->id,
          'user_id' => $workOrder->created_by ?? 1,
          'status' => InventoryMovement::STATUS_APPROVED,
          'notes' => $notes,
          'total_items' => $totalItems,
          'total_quantity' => $totalQuantity,
        ]);

        $this->info("DEBUG: Movimiento creado con ID: {$movement->id}");
        $this->info("DEBUG: Creando {$productParts->count()} detalles...");

        // Crear detalles
        foreach ($productParts as $part) {
          $detailNotes = $isCreditNote
            ? "Devolución NC {$document->full_number} - {$part->product->name}"
            : "Venta orden de trabajo {$workOrder->correlative}";

          InventoryMovementDetail::create([
            'inventory_movement_id' => $movement->id,
            'product_id' => $part->product_id,
            'code' => $part->product->code,
            'description' => $part->product->name,
            'quantity' => $part->quantity_used,
            'unit_cost' => $part->unit_price,
            'total_cost' => $part->net_amount,
            'notes' => $detailNotes,
          ]);
        }

        $this->info("DEBUG: Detalles creados correctamente");

      } elseif ($previewData['type'] === 'quotation') {
        $quotation = $previewData['quotation'];
        $productDetails = $previewData['productDetails'];

        $this->info("DEBUG: Creando movimiento para Cotización {$quotation->id}...");

        // Determinar descripción de NC si aplica
        $creditNoteDescription = 'Desconocido';
        if ($isCreditNote) {
          if ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_ANULACION) {
            $creditNoteDescription = 'Anulación de operación';
          } elseif ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_TOTAL) {
            $creditNoteDescription = 'Devolución total';
          } elseif ($document->sunat_concept_credit_note_type_id == SunatConcepts::ID_CREDIT_NOTE_DEVOLUCION_ITEM) {
            $creditNoteDescription = 'Devolución por ítem';
          }
        }

        // Configurar según sea NC o factura normal
        $movementType = $isCreditNote ? InventoryMovement::TYPE_RETURN_IN : InventoryMovement::TYPE_SALE;
        $referenceType = $isCreditNote ? ElectronicDocument::class : ApOrderQuotations::class;
        $referenceId = $isCreditNote ? $document->id : $quotation->id;
        $notes = $isCreditNote
          ? "Devolución por NC {$document->full_number} - {$creditNoteDescription} - {$quotation->quotation_number}"
          : "Salida por venta - Cotización {$quotation->quotation_number}";

        // Crear movimiento
        $movement = InventoryMovement::create([
          'movement_number' => InventoryMovement::generateMovementNumber(),
          'movement_type' => $movementType,
          'movement_date' => $document->fecha_de_emision,
          'warehouse_id' => $warehouse->id,
          'currency_id' => 1,
          'exchange_rate' => 1.00,
          'reference_type' => $referenceType,
          'reference_id' => $referenceId,
          'electronic_document_id' => $document->id,
          'user_id' => $quotation->created_by ?? 1,
          'status' => InventoryMovement::STATUS_APPROVED,
          'notes' => $notes,
          'total_items' => $totalItems,
          'total_quantity' => $totalQuantity,
        ]);

        $this->info("DEBUG: Movimiento creado con ID: {$movement->id}");
        $this->info("DEBUG: Creando {$productDetails->count()} detalles...");

        // Crear detalles
        foreach ($productDetails as $detail) {
          $detailNotes = $isCreditNote
            ? "Devolución NC {$document->full_number} - {$detail->product->name}"
            : "Venta cotización {$quotation->quotation_number}";

          InventoryMovementDetail::create([
            'inventory_movement_id' => $movement->id,
            'product_id' => $detail->product_id,
            'code' => $detail->product->code,
            'description' => $detail->description,
            'quantity' => $detail->quantity,
            'unit_cost' => $detail->unit_price,
            'total_cost' => $detail->total_cost,
            'notes' => $detailNotes,
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