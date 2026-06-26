<?php

namespace App\Jobs;

use App\Http\Services\ap\postventa\gestionProductos\ProductWarehouseStockService;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\InventoryMovementDetail;
use App\Models\ap\postventa\gestionProductos\Products;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizar ajustes de inventario desde Dynamics
 *
 * Ejecutar: php artisan queue:work --queue=inventory_adjustments --tries=3
 */
class SyncInventoryAdjustmentsDynamicsJob implements ShouldQueue
{
  use Queueable;

  public int $tries = 3;
  public int $timeout = 300;

  /**
   * Create a new job instance.
   */
  public function __construct()
  {
    $this->onQueue('inventory_adjustments');
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    try {
      // Ejecutar el PA neIvConsultarAjustesInventario
      $results = $this->consultAjustesInventario();

      if (empty($results)) {
        Log::info('No se encontraron ajustes de inventario en Dynamics');
        return;
      }

      // Filtrar solo registros de POSTVENTA y de los últimos 6 meses
      $filteredResults = $this->filterResults($results);

      if (empty($filteredResults)) {
        Log::info('No hay ajustes de inventario de POSTVENTA de los últimos 6 meses');
        return;
      }

      // Agrupar por Numero (movement_number_dyn)
      $groupedByNumber = collect($filteredResults)->groupBy('Numero');

      $processedCount = 0;
      $skippedCount = 0;
      $errorCount = 0;

      foreach ($groupedByNumber as $numero => $lines) {
        try {
          // Verificar si ya existe el movimiento con ese movement_number_dyn
          $existingMovement = InventoryMovement::where('movement_number_dyn', $numero)->first();

          if ($existingMovement) {
            $skippedCount++;
            continue;
          }

          // Crear el movimiento de inventario
          $this->createInventoryMovement($numero, $lines->toArray());
          $processedCount++;
        } catch (\Exception $e) {
          $errorCount++;
          Log::error('Error procesando ajuste de inventario', [
            'numero' => $numero,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
          ]);
          continue;
        }
      }
    } catch (\Exception $e) {
      Log::error('Error en SyncInventoryAdjustmentsDynamicsJob', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      throw $e;
    }
  }

  /**
   * Consulta el PA neIvConsultarAjustesInventario
   */
  protected function consultAjustesInventario(): array
  {
    try {
      return DB::connection('dbtest')
        ->select("EXEC neIvConsultarAjustesInventario");
    } catch (\Exception $e) {
      Log::error('Error ejecutando PA neIvConsultarAjustesInventario', [
        'error' => $e->getMessage()
      ]);
      throw $e;
    }
  }

  /**
   * Filtra los resultados por POSTVENTA y últimos 6 meses
   */
  protected function filterResults(array $results): array
  {
    $threeMonthsAgo = Carbon::now()->subMonths(6)->startOfDay();

    return array_filter($results, function ($row) use ($threeMonthsAgo) {
      // Filtrar solo POSTVENTA
      if (!isset($row->Modulo) || $row->Modulo !== 'POSTVENTA') {
        return false;
      }

      // Filtrar por fecha (últimos 6 meses)
      if (isset($row->Fecha)) {
        try {
          $fecha = Carbon::parse($row->Fecha);
          return $fecha->gte($threeMonthsAgo);
        } catch (\Exception $e) {
          Log::warning('Error parseando fecha', [
            'fecha' => $row->Fecha,
            'error' => $e->getMessage()
          ]);
          return false;
        }
      }

      return false;
    });
  }

  /**
   * Crea el movimiento de inventario y sus detalles
   */
  protected function createInventoryMovement(string $numero, array $lines): void
  {
    $productsToRecalculate = [];
    $movement = null;
    $stockService = app(ProductWarehouseStockService::class);

    DB::transaction(function () use ($numero, $lines, &$productsToRecalculate, &$movement, $stockService) {
      // Tomar la primera línea para obtener datos generales
      $firstLine = $lines[0];

      // Determinar el tipo de movimiento
      $movementType = $this->getMovementType($firstLine->Tipo_Movimiento);

      // Determinar usuario
      $numDocUser = $firstLine->Usuario;
      $existUser = Worker::where('vat', $numDocUser)->first();

      if ($existUser) {
        if ($existUser->user) {
          $userId = $existUser->user->id;
        } else {
          $notes = $firstLine->Motivo;
        }
      } else {
        $notes = $firstLine->Motivo . ' Usuario: ' . $numDocUser;
      }

      // Obtener el warehouse_id
      $warehouse = $this->getWarehouse($firstLine->Almacen);

      if (!$warehouse) {
        throw new \Exception("No se encontró el almacén con dyn_code: {$firstLine->Almacen}");
      }

      // Parsear la fecha
      $movementDate = Carbon::parse($firstLine->Fecha);

      // Generar movement_number
      $movementNumber = InventoryMovement::generateMovementNumber();

      // Crear el movimiento
      $movement = InventoryMovement::create([
        'movement_number' => $movementNumber,
        'movement_number_dyn' => $numero,
        'movement_type' => $movementType,
        'item_type' => 'PRODUCTO',
        'movement_date' => $movementDate,
        'warehouse_id' => $warehouse->id,
        'user_id' => $userId ?? null, // Sincronización automática desde Dynamics
        'status' => InventoryMovement::STATUS_APPROVED,
        'notes' => $notes ?? null,
        'total_items' => count($lines),
      ]);

      // Crear los detalles
      $totalQuantity = 0;

      foreach ($lines as $line) {
        // Buscar el producto
        $product = Products::where('dyn_code', $line->Codigo_Articulo)
          ->where('status', 1)
          ->first();

        if (!$product) {
          Log::warning('Producto no encontrado', [
            'dyn_code' => $line->Codigo_Articulo,
            'movement_number_dyn' => $numero
          ]);
          continue;
        }

        // Guardar la cantidad siempre como positiva (el tipo de movimiento indica si es entrada o salida)
        $quantity = abs((float)$line->Cantidad);
        $totalQuantity += $quantity;

        // Actualizar el stock según el tipo de movimiento
        if ($movementType === InventoryMovement::TYPE_ADJUSTMENT_IN) {
          // Ajuste de INGRESO: agregar stock
          $stockService->addStock($product->id, $warehouse->id, $quantity);
        } elseif ($movementType === InventoryMovement::TYPE_ADJUSTMENT_OUT) {
          // Ajuste de SALIDA: verificar que existe el stock y disminuirlo
          $productStock = ProductWarehouseStock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

          if (!$productStock) {
            throw new \Exception(
              "Producto no encontrado en almacén. Producto: {$product->name} (ID: {$product->id}), " .
              "Almacén: {$warehouse->description} (ID: {$warehouse->id}), " .
              "Movimiento Dynamics: {$numero}"
            );
          }

          // Disminuir el stock
          $stockService->removeStock($product->id, $warehouse->id, $quantity);
        }

        // Marcar producto para recalcular precios (tanto ingresos como salidas)
        $productsToRecalculate[] = [
          'product_id' => $product->id,
          'warehouse_id' => $warehouse->id,
        ];

        InventoryMovementDetail::create([
          'inventory_movement_id' => $movement->id,
          'product_id' => $product->id,
          'quantity' => $quantity,
          'unit_cost' => $line->CostoUnitario ?? null,
          'total_cost' => $line->CostoTotal ?? null,
        ]);
      }

      // Actualizar total_quantity
      $movement->update(['total_quantity' => $totalQuantity]);
    });

    // AFTER successful stock update, recalculate prices for all affected products
    // This is done OUTSIDE the transaction to avoid blocking
    // Usa el método centralizado del servicio (una sola fuente de verdad)
    $stockService->recalculatePricesAfterMovement($productsToRecalculate, $movement);
  }

  /**
   * Determina el tipo de movimiento basado en Tipo_Movimiento
   */
  protected function getMovementType(string $tipoMovimiento): string
  {
    return match (strtoupper($tipoMovimiento)) {
      'INGRESO' => InventoryMovement::TYPE_ADJUSTMENT_IN,
      'SALIDA' => InventoryMovement::TYPE_ADJUSTMENT_OUT,
      default => throw new \Exception("Tipo de movimiento no reconocido: {$tipoMovimiento}")
    };
  }

  /**
   * Determina las notas basado en Tipo_Movimiento
   */
  protected function getNotes(string $tipoMovimiento, string $motivo = ''): string
  {
    $baseNote = match (strtoupper($tipoMovimiento)) {
      'INGRESO' => 'AJUSTE POSITIVO DE INVENTARIO',
      'SALIDA' => 'AJUSTE NEGATIVO DE INVENTARIO',
      default => 'AJUSTE DE INVENTARIO'
    };

    // Si hay motivo, agregarlo
    if (!empty($motivo)) {
      return $baseNote . ' - ' . strtoupper($motivo);
    }

    return $baseNote;
  }

  /**
   * Obtiene el warehouse por dyn_code
   */
  protected function getWarehouse(string $dynCode): ?Warehouse
  {
    return Warehouse::where('dyn_code', $dynCode)
      ->where('is_physical_warehouse', 1)
      ->where('type_operation_id', ApMasters::TIPO_OPERACION_POSTVENTA)
      ->where('status', 1)
      ->first();
  }

  /**
   * Handle job failure
   */
  public function failed(\Throwable $exception): void
  {
    Log::error('SyncInventoryAdjustmentsDynamicsJob falló definitivamente', [
      'error' => $exception->getMessage(),
      'trace' => $exception->getTraceAsString()
    ]);
  }
}
