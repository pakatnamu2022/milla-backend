<?php

namespace App\Console\Commands;

use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreateHistoricalInventoryMovementCommand extends Command
{
  protected $signature = 'inventory:create-historical-movement';

  protected $description = 'Crea un movimiento de inventario histórico (solo registro, sin mover stock)';

  public function handle(): int
  {
    $this->info("╔═══════════════════════════════════════════════════════════════════════════╗");
    $this->info("║     CREAR MOVIMIENTO DE INVENTARIO HISTÓRICO (SOLO REGISTRO)             ║");
    $this->info("╚═══════════════════════════════════════════════════════════════════════════╝");
    $this->newLine();

    try {
      DB::beginTransaction();

      // 1. Tipo de movimiento
      $movementType = $this->choice(
        '¿Qué tipo de movimiento deseas crear?',
        [
          InventoryMovement::TYPE_PURCHASE_RECEPTION => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_PURCHASE_RECEPTION),
          InventoryMovement::TYPE_SALE => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_SALE),
          InventoryMovement::TYPE_ADJUSTMENT_IN => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_ADJUSTMENT_IN),
          InventoryMovement::TYPE_ADJUSTMENT_OUT => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_ADJUSTMENT_OUT),
          InventoryMovement::TYPE_TRANSFER_OUT => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_TRANSFER_OUT),
          InventoryMovement::TYPE_TRANSFER_IN => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_TRANSFER_IN),
          InventoryMovement::TYPE_RETURN_IN => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_RETURN_IN),
          InventoryMovement::TYPE_RETURN_OUT => InventoryMovement::getMovementTypeLabel(InventoryMovement::TYPE_RETURN_OUT),
        ]
      );

      // 2. Fecha del movimiento
      $movementDate = $this->ask('Fecha del movimiento (YYYY-MM-DD)');

      // 3. Almacén origen
      $warehouseId = $this->ask('ID del almacén origen (warehouse_id)');
      $warehouse = Warehouse::find($warehouseId);
      if (!$warehouse) {
        $this->error("❌ No se encontró el almacén con ID {$warehouseId}");
        return 1;
      }
      $this->info("✓ Almacén: {$warehouse->name}");

      // 4. Almacén destino (solo para transferencias)
      $warehouseDestinationId = null;
      if (in_array($movementType, [InventoryMovement::TYPE_TRANSFER_OUT, InventoryMovement::TYPE_TRANSFER_IN])) {
        $warehouseDestinationId = $this->ask('ID del almacén destino (warehouse_destination_id)');
        $warehouseDestination = Warehouse::find($warehouseDestinationId);
        if (!$warehouseDestination) {
          $this->error("❌ No se encontró el almacén destino con ID {$warehouseDestinationId}");
          return 1;
        }
        $this->info("✓ Almacén destino: {$warehouseDestination->name}");
      }

      // 5. Moneda
      $currencyId = $this->ask('ID de la moneda (currency_id)', '1');
      $currency = TypeCurrency::find($currencyId);
      if (!$currency) {
        $this->error("❌ No se encontró la moneda con ID {$currencyId}");
        return 1;
      }
      $this->info("✓ Moneda: {$currency->description}");

      // 6. Tipo de cambio
      $exchangeRate = $this->ask('Tipo de cambio (exchange_rate)', '1.00');

      // 7. Estado
      $status = $this->choice(
        'Estado del movimiento',
        [
          InventoryMovement::STATUS_DRAFT => InventoryMovement::getStatusLabel(InventoryMovement::STATUS_DRAFT),
          InventoryMovement::STATUS_APPROVED => InventoryMovement::getStatusLabel(InventoryMovement::STATUS_APPROVED),
          InventoryMovement::STATUS_IN_TRANSIT => InventoryMovement::getStatusLabel(InventoryMovement::STATUS_IN_TRANSIT),
          InventoryMovement::STATUS_CANCELLED => InventoryMovement::getStatusLabel(InventoryMovement::STATUS_CANCELLED),
        ],
        1 // Por defecto APPROVED
      );

      // 8. Campos opcionales
      $movementNumberDyn = $this->ask('Número de movimiento Dynamics (movement_number_dyn) [opcional]') ?: null;
      $referenceType = $this->ask('Tipo de referencia (reference_type) [opcional]') ?: null;
      $referenceId = $this->ask('ID de referencia (reference_id) [opcional]') ?: null;
      $electronicDocumentId = $this->ask('ID de documento electrónico (electronic_document_id) [opcional]') ?: null;
      $reasonInOutId = $this->ask('ID del motivo (reason_in_out_id) [opcional]') ?: null;
      $itemType = $this->ask('Tipo de ítem (item_type) [opcional]') ?: null;
      $notes = $this->ask('Notas del movimiento [opcional]') ?: null;

      // 9. Usuario
      $userId = $this->ask('ID del usuario (user_id)', '1');

      $this->newLine();
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("📦 DETALLES DE PRODUCTOS");
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

      $details = [];
      $addMore = true;

      while ($addMore) {
        $this->newLine();
        $this->info("Producto #" . (count($details) + 1));

        // ID del producto
        $productId = $this->ask('ID del producto (product_id)');
        $product = Products::find($productId);
        if (!$product) {
          $this->error("❌ No se encontró el producto con ID {$productId}");
          if (!$this->confirm('¿Deseas intentar con otro producto?', true)) {
            return 1;
          }
          continue;
        }
        $this->info("✓ Producto: {$product->name}");

        // Cantidad
        $quantity = $this->ask('Cantidad');

        // Costo unitario
        $unitCost = $this->ask('Costo unitario (unit_cost)', '0.00');

        // Calcular costo total
        $totalCost = abs($quantity) * $unitCost;

        // Campos opcionales del detalle
        $code = $this->ask('Código del producto [opcional, Enter para usar código del producto]') ?: $product->code;
        $description = $this->ask('Descripción [opcional, Enter para usar nombre del producto]') ?: $product->name;
        $batchNumber = $this->ask('Número de lote (batch_number) [opcional]') ?: null;
        $expirationDate = $this->ask('Fecha de vencimiento (expiration_date) YYYY-MM-DD [opcional]') ?: null;
        $detailNotes = $this->ask('Notas del detalle [opcional]') ?: null;

        $details[] = [
          'product_id' => $productId,
          'code' => $code,
          'description' => $description,
          'quantity' => $quantity,
          'unit_cost' => $unitCost,
          'total_cost' => $totalCost,
          'batch_number' => $batchNumber,
          'expiration_date' => $expirationDate,
          'notes' => $detailNotes,
        ];

        $this->info("✓ Producto agregado");

        $addMore = $this->confirm('¿Deseas agregar otro producto?', true);
      }

      if (empty($details)) {
        $this->error("❌ Debes agregar al menos un producto");
        return 1;
      }

      // Resumen antes de crear
      $this->newLine();
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->info("📋 RESUMEN DEL MOVIMIENTO");
      $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
      $this->table(
        ['Campo', 'Valor'],
        [
          ['Tipo de movimiento', InventoryMovement::getMovementTypeLabel($movementType)],
          ['Fecha', $movementDate],
          ['Almacén origen', $warehouse->name . " (ID: {$warehouseId})"],
          ['Almacén destino', $warehouseDestinationId ? "ID: {$warehouseDestinationId}" : 'N/A'],
          ['Moneda', $currency->description . " (ID: {$currencyId})"],
          ['Tipo de cambio', $exchangeRate],
          ['Estado', InventoryMovement::getStatusLabel($status)],
          ['Usuario', "ID: {$userId}"],
          ['Total productos', count($details)],
        ]
      );

      $this->newLine();
      $this->info("Productos:");
      $productRows = [];
      foreach ($details as $index => $detail) {
        $productRows[] = [
          $index + 1,
          $detail['description'],
          $detail['quantity'],
          number_format($detail['unit_cost'], 2),
          number_format($detail['total_cost'], 2),
        ];
      }
      $this->table(
        ['#', 'Descripción', 'Cantidad', 'Costo Unit.', 'Costo Total'],
        $productRows
      );

      if (!$this->confirm('¿Deseas crear este movimiento histórico?', true)) {
        $this->warn('Operación cancelada');
        return 0;
      }

      // Crear el movimiento
      $movement = InventoryMovement::create([
        'movement_number' => InventoryMovement::generateMovementNumber(),
        'movement_number_dyn' => $movementNumberDyn,
        'movement_type' => $movementType,
        'movement_date' => $movementDate,
        'warehouse_id' => $warehouseId,
        'warehouse_destination_id' => $warehouseDestinationId,
        'currency_id' => $currencyId,
        'exchange_rate' => $exchangeRate,
        'reference_type' => $referenceType,
        'reference_id' => $referenceId,
        'electronic_document_id' => $electronicDocumentId,
        'user_id' => $userId,
        'status' => $status,
        'notes' => $notes,
        'reason_in_out_id' => $reasonInOutId,
        'item_type' => $itemType,
      ]);

      // Crear los detalles
      foreach ($details as $detail) {
        InventoryMovementDetail::create([
          'inventory_movement_id' => $movement->id,
          'product_id' => $detail['product_id'],
          'code' => $detail['code'],
          'description' => $detail['description'],
          'quantity' => $detail['quantity'],
          'unit_cost' => $detail['unit_cost'],
          'total_cost' => $detail['total_cost'],
          'batch_number' => $detail['batch_number'],
          'expiration_date' => $detail['expiration_date'],
          'notes' => $detail['notes'],
        ]);
      }

      // Calcular totales
      $movement->calculateTotals();

      DB::commit();

      $this->newLine();
      $this->info("╔═══════════════════════════════════════════════════════════════════════════╗");
      $this->info("║                          ✓ MOVIMIENTO CREADO                              ║");
      $this->info("╚═══════════════════════════════════════════════════════════════════════════╝");
      $this->newLine();
      $this->info("ID del movimiento: {$movement->id}");
      $this->info("Número de movimiento: {$movement->movement_number}");
      $this->info("Total de productos: {$movement->total_items}");
      $this->info("Cantidad total: {$movement->total_quantity}");
      $this->newLine();
      $this->warn("⚠️  IMPORTANTE: Este comando NO actualizó el stock físico en product_warehouse_stock");
      $this->warn("   Solo creó el registro del movimiento para regularización histórica");

      return 0;
    } catch (\Exception $e) {
      DB::rollBack();
      $this->error("❌ Error al crear el movimiento: {$e->getMessage()}");
      return 1;
    }
  }
}