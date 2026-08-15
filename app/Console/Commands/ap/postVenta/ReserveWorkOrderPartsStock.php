<?php

namespace App\Console\Commands\ap\postVenta;

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApWorkOrder;
use Illuminate\Console\Command;

class ReserveWorkOrderPartsStock extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'reserve:work-order-parts {work_order_id : ID de la orden de trabajo}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Reserva el stock de todos los repuestos de una orden de trabajo';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $workOrderId = $this->argument('work_order_id');

    $this->info("🔍 Buscando orden de trabajo ID: {$workOrderId}");

    // Buscar la orden de trabajo con sus repuestos
    $workOrder = ApWorkOrder::with(['parts.product', 'sede.warehouses'])->find($workOrderId);

    if (!$workOrder) {
      $this->error("❌ No se encontró la orden de trabajo con ID: {$workOrderId}");
      return 1;
    }

    $this->info("✅ Orden de trabajo encontrada: {$workOrder->correlative}");

    // Buscar el almacén de la sede
    $warehouse = $workOrder->sede->warehouses()
      ->where('is_physical_warehouse', true)
      ->where('status', true)
      ->first();

    if (!$warehouse) {
      $this->error("❌ No se encontró almacén activo para la sede de la OT");
      return 1;
    }

    $this->info("   Almacén: {$warehouse->name}");
    $this->newLine();

    // Filtrar solo repuestos (products) que NO sean travesía
    $parts = $workOrder->parts
      ->where('product_id', '!=', null)
      ->where('is_traverse', false);

    if ($parts->isEmpty()) {
      $this->warn("⚠️  Esta orden de trabajo NO tiene repuestos para reservar");
      $this->info("   Solo tiene mano de obra o repuestos de travesía");
      return 0;
    }

    $this->info("📦 Repuestos encontrados: {$parts->count()}");
    $this->newLine();

    // ==========================================
    // PASO 1: PREVISUALIZACIÓN
    // ==========================================
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("🔍 PREVISUALIZACIÓN DE RESERVAS");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->newLine();

    $previewData = [];
    $totalToReserve = 0;
    $hasErrors = false;

    foreach ($parts as $part) {
      // Buscar el stock actual
      $stock = ProductWarehouseStock::where('product_id', $part->product_id)
        ->where('warehouse_id', $warehouse->id)
        ->first();

      if (!$stock) {
        $previewData[] = [
          'Producto' => $part->product->name ?? 'N/A',
          'Código' => $part->product->code ?? 'N/A',
          'Cantidad a Reservar' => number_format($part->quantity_used, 2),
          'Stock Actual' => '0.00',
          'Disponible Actual' => '0.00',
          'Disponible Final' => '0.00',
          'Estado' => '❌ Sin stock',
        ];
        $hasErrors = true;
        continue;
      }

      $quantityToReserve = $part->quantity_used;
      $currentAvailable = $stock->available_quantity;
      $finalAvailable = $currentAvailable - $quantityToReserve;

      $status = '✅ OK';
      if ($finalAvailable < 0) {
        $status = '❌ Stock insuficiente';
        $hasErrors = true;
      } elseif ($currentAvailable < $quantityToReserve) {
        $status = '⚠️ Stock insuficiente';
        $hasErrors = true;
      }

      $previewData[] = [
        'Producto' => $part->product->name ?? 'N/A',
        'Código' => $part->product->code ?? 'N/A',
        'Cantidad a Reservar' => number_format($quantityToReserve, 2),
        'Stock Actual' => number_format($stock->quantity, 2),
        'Disponible Actual' => number_format($currentAvailable, 2),
        'Disponible Final' => number_format($finalAvailable, 2),
        'Estado' => $status,
      ];

      $totalToReserve += $quantityToReserve;
    }

    $this->table(
      ['Producto', 'Código', 'Cantidad a Reservar', 'Stock Actual', 'Disponible Actual', 'Disponible Final', 'Estado'],
      $previewData
    );

    $this->newLine();

    // Mostrar resumen de la previsualización
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📊 RESUMEN DE PREVISUALIZACIÓN");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📦 Total de repuestos: {$parts->count()}");
    $this->info("📈 Cantidad total a reservar: " . number_format($totalToReserve, 2));
    $this->info("🏢 Almacén: {$warehouse->name}");

    if ($hasErrors) {
      $this->newLine();
      $this->error("⚠️  ADVERTENCIA: Algunos productos no tienen stock suficiente");
      $this->error("   La reserva fallará para esos productos");
    }

    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->newLine();

    // ==========================================
    // PASO 2: CONFIRMAR EJECUCIÓN
    // ==========================================
    if (!$this->confirm("¿Confirmas que deseas RESERVAR estos repuestos?")) {
      $this->warn("❌ Operación cancelada por el usuario");
      $this->info("   No se realizó ninguna reserva");
      return 0;
    }

    $this->newLine();
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("⚙️  EJECUTANDO RESERVAS");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->newLine();

    // ==========================================
    // PASO 3: EJECUTAR RESERVAS
    // ==========================================
    $stockService = app(ProductWarehouseStockService::class);
    $successCount = 0;
    $errorCount = 0;
    $totalReserved = 0;

    foreach ($parts as $part) {
      $productCode = $part->product->code ?? 'N/A';
      $productName = $part->product->name ?? 'N/A';
      $quantityToReserve = $part->quantity_used;

      $this->info("🔄 Reservando: {$productCode} - {$productName}");
      $this->info("   Cantidad: " . number_format($quantityToReserve, 2));

      try {
        $stock = $stockService->reserveStock(
          $part->product_id,
          $warehouse->id,
          $quantityToReserve
        );

        $this->info("✅ Reservado exitosamente");
        $this->info("   Stock total: {$stock->quantity} | Reservado: {$stock->reserved_quantity} | Disponible: {$stock->available_quantity}");

        $successCount++;
        $totalReserved += $quantityToReserve;

      } catch (\Exception $e) {
        $this->error("❌ Error al reservar:");
        $this->error("   {$e->getMessage()}");
        $errorCount++;
      }

      $this->newLine();
    }

    // Resumen final
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("📊 RESUMEN FINAL");
    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
    $this->info("✅ Repuestos reservados exitosamente: {$successCount}/{$parts->count()}");
    $this->info("📦 Cantidad total reservada: " . number_format($totalReserved, 2));

    if ($errorCount > 0) {
      $this->error("❌ Errores encontrados: {$errorCount}");
      $this->error("   Verifica el stock disponible de los productos con error");
    }

    $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

    return $errorCount > 0 ? 1 : 0;
  }
}
