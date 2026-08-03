<?php

namespace App\Console\Commands\ap\postVenta;

use App\Http\Services\ap\postventa\gestionProductos\InventoryMovementService;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateInternalNoteInventoryMovement extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'internal-notes:generate-inventory-movement
                          {internal_note_id : ID de la nota interna}
                          {--force : Forzar generación incluso si ya tiene movimiento}
                          {--dry-run : Mostrar lo que se haría sin ejecutar}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Genera el movimiento de inventario faltante para una nota interna específica';

  private InventoryMovementService $inventoryMovementService;

  public function __construct(InventoryMovementService $inventoryMovementService)
  {
    parent::__construct();
    $this->inventoryMovementService = $inventoryMovementService;
  }

  /**
   * Execute the console command.
   */
  public function handle()
  {
    $internalNoteId = $this->argument('internal_note_id');
    $force = $this->option('force');
    $dryRun = $this->option('dry-run');

    $this->info("🔍 Analizando nota interna ID: {$internalNoteId}");
    $this->newLine();

    // Buscar la nota interna con sus relaciones
    $internalNote = ApInternalNote::with([
      'workOrder.items.typePlanning',
      'workOrder.parts.product',
      'inventoryMovements',
      'electronicDocuments'
    ])->find($internalNoteId);

    if (!$internalNote) {
      $this->error("✗ Nota interna con ID {$internalNoteId} no encontrada.");
      return 1;
    }

    $this->info("✓ Nota interna encontrada: {$internalNote->number}");

    // Validar que tiene orden de trabajo
    $workOrder = $internalNote->workOrder;
    if (!$workOrder) {
      $this->error("✗ La nota interna no tiene una orden de trabajo asociada.");
      return 1;
    }

    $this->info("✓ Orden de trabajo: {$workOrder->correlative}");

    // Obtener el tipo de documento
    $item = $workOrder->items()->whereNull('deleted_at')->first();
    if (!$item || !$item->typePlanning) {
      $this->error("✗ No se encontró el tipo de planificación de la orden de trabajo.");
      return 1;
    }

    $typeDocument = $item->typePlanning->type_document;
    $this->info("✓ Tipo de documento: {$typeDocument}");

    // Validar que es INTERNA_SC o INTERNA_CC
    if (!in_array($typeDocument, [TypePlanningWorkOrder::INTERNA_SC, TypePlanningWorkOrder::INTERNA_CC])) {
      $this->error("✗ El tipo de documento debe ser INTERNA_SC o INTERNA_CC.");
      return 1;
    }

    // Verificar si tiene repuestos
    $productParts = $workOrder->parts->filter(fn($part) => $part->product_id !== null);
    if ($productParts->isEmpty()) {
      $this->warn("⚠️  La orden de trabajo NO tiene repuestos. No es necesario generar salida de inventario.");
      return 0;
    }

    $this->info("✓ Repuestos encontrados: {$productParts->count()}");
    $this->newLine();

    // Mostrar detalles de los repuestos
    $this->displayPartDetails($productParts);

    // Verificar si ya tiene movimiento de inventario
    $existingMovements = $internalNote->inventoryMovements;
    if ($existingMovements->isNotEmpty() && !$force) {
      $this->warn("⚠️  La nota interna YA tiene movimientos de inventario:");
      $this->newLine();

      $headers = ['ID', 'Número', 'Tipo', 'Fecha', 'Almacén'];
      $rows = $existingMovements->map(function ($movement) {
        return [
          $movement->id,
          $movement->movement_number,
          $this->getMovementTypeShort($movement->movement_type),
          $movement->movement_date->format('Y-m-d'),
          $movement->warehouse?->dyn_code ?? 'N/A',
        ];
      })->toArray();

      $this->table($headers, $rows);
      $this->newLine();
      $this->line("Usa --force si deseas generar un movimiento adicional de todas formas.");
      return 0;
    }

    // Para INTERNA_CC, validar que esté facturada
    if ($typeDocument === TypePlanningWorkOrder::INTERNA_CC) {
      if ($internalNote->status !== ApInternalNote::STATUS_INVOICED) {
        $this->warn("⚠️  INTERNA_CC en estado '{$internalNote->status}'. Solo se genera salida cuando está facturada.");
        return 0;
      }

      if ($internalNote->electronicDocuments->isEmpty()) {
        $this->error("✗ INTERNA_CC facturada pero NO tiene comprobante electrónico asociado.");
        return 1;
      }

      $electronicDocument = $internalNote->electronicDocuments->first();
      $this->info("✓ Comprobante electrónico: {$electronicDocument->full_number}");
    }

    // Obtener almacén físico de la sede
    $warehouse = Warehouse::where('sede_id', $workOrder->sede_id)
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("✗ No se encontró almacén físico activo para la sede de la orden de trabajo.");
      return 1;
    }

    $this->info("✓ Almacén: {$warehouse->dyn_code} - {$warehouse->name}");
    $this->newLine();

    // Mostrar lo que se va a hacer
    $this->displayMovementSummary($typeDocument, $internalNote, $workOrder, $warehouse, $productParts);

    // Si es dry-run, terminar aquí
    if ($dryRun) {
      $this->newLine();
      $this->warn("🔍 DRY-RUN - No se realizaron cambios.");
      $this->info("Ejecuta sin --dry-run para generar el movimiento.");
      return 0;
    }

    // Pedir confirmación
    if (!$this->confirm("¿Deseas generar el movimiento de inventario?", false)) {
      $this->info("Operación cancelada.");
      return 0;
    }

    // Generar el movimiento
    return $this->generateMovement($typeDocument, $internalNote, $workOrder, $warehouse, $productParts);
  }

  /**
   * Muestra los detalles de los repuestos
   */
  private function displayPartDetails($productParts): void
  {
    $this->info("📦 REPUESTOS:");
    $this->newLine();

    $headers = ['Código', 'Nombre', 'Cantidad', 'Precio Unit.', 'Total'];
    $rows = $productParts->map(function ($part) {
      return [
        $part->product->code ?? 'N/A',
        $part->product->name ?? 'N/A',
        $part->quantity_used,
        number_format($part->unit_price, 2),
        number_format($part->quantity_used * $part->unit_price, 2),
      ];
    })->toArray();

    $this->table($headers, $rows);

    $total = $productParts->sum(fn($part) => $part->quantity_used * $part->unit_price);
    $this->line("Total: S/ " . number_format($total, 2));
    $this->newLine();
  }

  /**
   * Muestra el resumen del movimiento que se va a generar
   */
  private function displayMovementSummary(
    string         $typeDocument,
    ApInternalNote $internalNote,
                   $workOrder,
    Warehouse      $warehouse,
                   $productParts
  ): void
  {
    $this->info("📋 MOVIMIENTO A GENERAR:");
    $this->newLine();

    if ($typeDocument === TypePlanningWorkOrder::INTERNA_SC) {
      $this->line("Tipo de movimiento: AJUSTE DE SALIDA (ADJUSTMENT_OUT)");
      $this->line("Fecha: " . ($internalNote->created_date ?? $internalNote->created_at)->format('Y-m-d'));
      $this->line("Notas: Ajuste de salida por generación de Nota Interna {$internalNote->number} - {$workOrder->correlative}");
    } else {
      $electronicDocument = $internalNote->electronicDocuments->first();
      $this->line("Tipo de movimiento: VENTA (SALE)");
      $this->line("Fecha: " . ($internalNote->created_date ?? $internalNote->created_at)->format('Y-m-d'));
      $this->line("Comprobante: {$electronicDocument->full_number}");
      $this->line("Notas: Venta por facturación de Nota Interna {$internalNote->number} - {$workOrder->correlative}");
    }

    $this->line("Almacén: {$warehouse->dyn_code} - {$warehouse->name}");
    $this->line("Productos: {$productParts->count()}");
    $this->line("Cantidad total: {$productParts->sum('quantity_used')}");
    $this->newLine();
  }

  /**
   * Genera el movimiento de inventario
   */
  private function generateMovement(
    string         $typeDocument,
    ApInternalNote $internalNote,
                   $workOrder,
    Warehouse      $warehouse,
                   $productParts
  ): int
  {
    $this->info("🔄 Generando movimiento de inventario...");
    $this->newLine();

    DB::beginTransaction();

    try {
      if ($typeDocument === TypePlanningWorkOrder::INTERNA_SC) {
        // Para INTERNA_SC: AJUSTE DE SALIDA
        $movementData = [
          'movement_type' => InventoryMovement::TYPE_ADJUSTMENT_OUT,
          'warehouse_id' => $warehouse->id,
          'notes' => "Ajuste de salida por generación de Nota Interna {$internalNote->number} - {$workOrder->correlative}",
          'movement_date' => $internalNote->created_date ?? $internalNote->created_at,
          'reference_type' => ApInternalNote::class,
          'reference_id' => $internalNote->id,
        ];
      } else {
        // Para INTERNA_CC: VENTA
        $electronicDocument = $internalNote->electronicDocuments->first();
        $movementData = [
          'movement_type' => InventoryMovement::TYPE_SALE,
          'warehouse_id' => $warehouse->id,
          'notes' => "Venta por facturación de Nota Interna {$internalNote->number} - {$workOrder->correlative}",
          'movement_date' => $internalNote->created_date ?? $internalNote->created_at,
          'reference_type' => ApInternalNote::class,
          'reference_id' => $internalNote->id,
          'electronic_document_id' => $electronicDocument->id,
        ];
      }

      // Preparar detalles
      $details = [];
      foreach ($productParts as $part) {
        $details[] = [
          'product_id' => $part->product_id,
          'quantity' => $part->quantity_used,
          'unit_cost' => $part->unit_price,
          'notes' => "NI {$internalNote->number} - {$part->product->name}",
        ];
      }

      // Crear el movimiento usando el servicio
      if ($typeDocument === TypePlanningWorkOrder::INTERNA_SC) {
        $movement = $this->inventoryMovementService->createAdjustment($movementData, $details);
      } else {
        $movement = $this->inventoryMovementService->createSale($movementData, $details);
      }

      DB::commit();

      $this->newLine();
      $this->info("✓ Movimiento de inventario generado exitosamente:");
      $this->line("  - ID: {$movement->id}");
      $this->line("  - Número: {$movement->movement_number}");
      $this->line("  - Tipo: {$this->getMovementTypeShort($movement->movement_type)}");
      $this->line("  - Fecha: {$movement->movement_date->format('Y-m-d')}");
      $this->newLine();

      return 0;
    } catch (\Exception $e) {
      DB::rollBack();

      $this->newLine();
      $this->error("✗ Error al generar el movimiento:");
      $this->error("  {$e->getMessage()}");
      $this->newLine();

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
}
