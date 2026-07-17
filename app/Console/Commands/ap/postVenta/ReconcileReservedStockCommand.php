<?php

namespace App\Console\Commands\ap\postVenta;

use App\Models\ap\ApMasters;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileReservedStockCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ap:reconcile-reserved-stock
    {--product_id= : ID del producto a corregir}
    {--warehouse_id= : ID del almacén a corregir (requiere --product_id)}
    {--preview : Muestra cuántos productos se corregirían por almacén, sin modificar datos}
    {--all : Corrige todos los productos/almacenes (comando final)}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Recalcula reserved_quantity y available_quantity de product_warehouse_stock a partir de las reservas reales en OTs (ap_work_order_parts) y cotizaciones de repuestos (ap_order_quotation_details), sin modificar quantity';

  private const EPSILON = 0.005;

  public function handle(): int
  {
    $productId = $this->option('product_id');
    $warehouseId = $this->option('warehouse_id');
    $preview = (bool) $this->option('preview');
    $all = (bool) $this->option('all');

    if ($warehouseId && !$productId) {
      $this->error('--warehouse_id requiere que también indiques --product_id.');
      return 1;
    }

    if (!$productId && !$preview && !$all) {
      $this->error('Debes especificar una opción: --product_id=X (opcionalmente con --warehouse_id=Y), --preview (previsualizar) o --all (corregir todo).');
      return 1;
    }

    // Mapas de reserva real: clave "product_id-warehouse_id" => cantidad reservada
    [$otMap, $rpMap] = $this->buildReservationMaps($productId, $warehouseId);

    $computedMap = [];
    foreach ($otMap as $key => $qty) {
      $computedMap[$key] = ($computedMap[$key] ?? 0) + $qty;
    }
    foreach ($rpMap as $key => $qty) {
      $computedMap[$key] = ($computedMap[$key] ?? 0) + $qty;
    }

    $stockQuery = ProductWarehouseStock::query()->with(['product:id,name,code', 'warehouse:id,dyn_code']);
    if ($productId) {
      $stockQuery->where('product_id', $productId);
    }
    if ($warehouseId) {
      $stockQuery->where('warehouse_id', $warehouseId);
    }

    if ($preview) {
      return $this->runPreview($stockQuery, $computedMap);
    }

    return $this->runApply($stockQuery, $computedMap, (bool) $productId, $all);
  }

  /**
   * Calcula, vía SQL agregado, cuánto está reservado realmente por cada combinación
   * product_id + warehouse_id en OTs pendientes y cotizaciones de repuestos pendientes.
   * Replica exactamente los criterios usados en ProductWarehouseStockService::getReservedStockReport().
   *
   * @return array{0: array<string,float>, 1: array<string,float>}
   */
  private function buildReservationMaps(?string $productId, ?string $warehouseId): array
  {
    $otQuery = DB::table('ap_work_order_parts as wop')
      ->join('ap_work_orders as wo', 'wop.work_order_id', '=', 'wo.id')
      ->whereNull('wop.deleted_at')
      ->whereNull('wo.deleted_at')
      ->where(function ($q) {
        $q->where('wo.output_generation_warehouse', false)
          ->whereNotIn('wo.status_id', [ApMasters::CANCELED_WORK_ORDER_ID, ApMasters::CLOSED_WORK_ORDER_ID])
          ->orWhereNull('wo.output_generation_warehouse');
      })
      ->select('wop.product_id', 'wop.warehouse_id', DB::raw('SUM(wop.quantity_used) as total'))
      ->groupBy('wop.product_id', 'wop.warehouse_id');

    if ($productId) {
      $otQuery->where('wop.product_id', $productId);
    }
    if ($warehouseId) {
      $otQuery->where('wop.warehouse_id', $warehouseId);
    }

    $otMap = [];
    foreach ($otQuery->get() as $row) {
      $otMap["{$row->product_id}-{$row->warehouse_id}"] = (float) $row->total;
    }

    // El warehouse físico de postventa es único por sede (validado: is_physical_warehouse=1
    // + type_operation_id=POSTVENTA no tiene duplicados por sede_id, a diferencia de solo sede_id).
    $rpQuery = DB::table('ap_order_quotation_details as qd')
      ->join('ap_order_quotations as q', 'qd.order_quotation_id', '=', 'q.id')
      ->join('warehouse as w', function ($join) {
        $join->on('w.sede_id', '=', 'q.sede_id')
          ->where('w.is_physical_warehouse', 1)
          ->where('w.type_operation_id', ApMasters::TIPO_OPERACION_POSTVENTA)
          ->where('w.status', 1);
      })
      ->where('qd.supply_type', 'STOCK')
      // Las cotizaciones de Taller nunca reservan stock al confirmarse (ver
      // ApOrderQuotationsService::reserveStockForQuotation): su reserva real nace
      // en ap_work_order_parts cuando los repuestos se cargan a la OT, y esa ya
      // la cubre $otQuery. Incluirlas aquí también duplicaría la reserva esperada.
      ->where('q.area_id', '!=', ApMasters::AREA_TALLER)
      ->whereNull('qd.deleted_at')
      ->whereNull('q.deleted_at')
      ->whereNotIn('q.status', [
        ApOrderQuotations::STATUS_APERTURADO,
        ApOrderQuotations::STATUS_DESCARTADO,
        ApOrderQuotations::STATUS_SEGMENTADA,
        ApOrderQuotations::STATUS_FACTURADO,
      ])
      ->select('qd.product_id', 'w.id as warehouse_id', DB::raw('SUM(qd.quantity) as total'))
      ->groupBy('qd.product_id', 'w.id');

    if ($productId) {
      $rpQuery->where('qd.product_id', $productId);
    }
    if ($warehouseId) {
      $rpQuery->where('w.id', $warehouseId);
    }

    $rpMap = [];
    foreach ($rpQuery->get() as $row) {
      $rpMap["{$row->product_id}-{$row->warehouse_id}"] = (float) $row->total;
    }

    return [$otMap, $rpMap];
  }

  private function runPreview($stockQuery, array $computedMap): int
  {
    $mismatchesByWarehouse = [];
    $totalRows = 0;
    $totalMismatches = 0;

    $stockQuery->clone()->chunkById(500, function ($chunk) use ($computedMap, &$mismatchesByWarehouse, &$totalRows, &$totalMismatches) {
      foreach ($chunk as $stock) {
        $totalRows++;
        $key = "{$stock->product_id}-{$stock->warehouse_id}";
        $computed = round($computedMap[$key] ?? 0.0, 2);
        $current = round((float) $stock->reserved_quantity, 2);

        $warehouseName = $stock->warehouse->dyn_code ?? "#{$stock->warehouse_id}";
        $mismatchesByWarehouse[$stock->warehouse_id] ??= [
          'warehouse_id' => $stock->warehouse_id,
          'warehouse_name' => $warehouseName,
          'total_rows' => 0,
          'mismatches' => 0,
          'details' => [],
        ];
        $mismatchesByWarehouse[$stock->warehouse_id]['total_rows']++;

        if (abs($computed - $current) > self::EPSILON) {
          $mismatchesByWarehouse[$stock->warehouse_id]['mismatches']++;
          $mismatchesByWarehouse[$stock->warehouse_id]['details'][] = [
            'product_id' => $stock->product_id,
            'product_code' => $stock->product->code ?? 'N/A',
            'product_name' => $stock->product->name ?? 'N/A',
            'current_reserved' => $current,
            'computed_reserved' => $computed,
            'difference' => $computed - $current,
          ];
          $totalMismatches++;
        }
      }
    });

    $orphans = $this->findOrphanReservations($stockQuery, $computedMap);

    if (empty($mismatchesByWarehouse)) {
      $this->info('No se encontraron registros de stock en el alcance indicado.');
    } else {
      $this->info("Registros de stock evaluados: {$totalRows}");
      $this->info("Total a corregir: {$totalMismatches}");
      $this->newLine();
      $this->table(
        ['Almacén ID', 'Almacén', 'Filas evaluadas', 'A corregir'],
        collect($mismatchesByWarehouse)
          ->sortByDesc('mismatches')
          ->map(fn($row) => [$row['warehouse_id'], $row['warehouse_name'], $row['total_rows'], $row['mismatches']])
          ->toArray()
      );

      // Mostrar detalle de los primeros 10 productos por almacén
      foreach (collect($mismatchesByWarehouse)->sortByDesc('mismatches') as $warehouseData) {
        if ($warehouseData['mismatches'] > 0) {
          $this->newLine();
          $this->info("Detalle de productos a corregir en {$warehouseData['warehouse_name']} (Almacén ID: {$warehouseData['warehouse_id']}):");

          $details = collect($warehouseData['details'])->take(10)->map(function ($item) {
            return [
              $item['product_id'],
              substr($item['product_code'], 0, 20),
              substr($item['product_name'], 0, 35),
              $item['current_reserved'],
              $item['computed_reserved'],
              sprintf('%+.2f', $item['difference']),
            ];
          })->toArray();

          $this->table(
            ['Prod. ID', 'Código', 'Nombre', 'Reservado Actual', 'Reservado Calculado', 'Diferencia'],
            $details
          );

          $remaining = $warehouseData['mismatches'] - 10;
          if ($remaining > 0) {
            $this->comment("... y {$remaining} producto(s) más en este almacén.");
          }
        }
      }
    }

    if (!empty($orphans)) {
      $this->newLine();
      $this->warn('Reservas encontradas en OT/cotizaciones SIN fila en product_warehouse_stock (no se pueden corregir, requieren revisión manual):');
      $this->table(
        ['product_id', 'warehouse_id', 'cantidad reservada calculada'],
        $orphans
      );
    }

    return 0;
  }

  private function runApply($stockQuery, array $computedMap, bool $hasProductId, bool $all): int
  {
    if (!$hasProductId && $all) {
      $count = $stockQuery->clone()->count();
      if (!$this->confirm("Esto recalculará reserved_quantity y available_quantity de {$count} registros de product_warehouse_stock (toda la tabla). ¿Continuar?")) {
        $this->info('Operación cancelada.');
        return 0;
      }
    }

    $orphans = $this->findOrphanReservations($stockQuery, $computedMap);

    $updated = 0;
    $unchanged = 0;
    $negativeWarnings = [];

    $total = $stockQuery->clone()->count();
    if ($total === 0) {
      $this->info('No se encontraron registros de stock en el alcance indicado.');
    } else {
      $bar = $this->output->createProgressBar($total);
      $bar->start();

      $stockQuery->clone()->chunkById(200, function ($chunk) use ($computedMap, &$updated, &$unchanged, &$negativeWarnings, $bar) {
        foreach ($chunk as $stock) {
          $key = "{$stock->product_id}-{$stock->warehouse_id}";
          $computed = round($computedMap[$key] ?? 0.0, 2);
          $current = round((float) $stock->reserved_quantity, 2);

          if (abs($computed - $current) > self::EPSILON) {
            DB::transaction(function () use ($stock, $computed) {
              $stock->reserved_quantity = $computed;
              $stock->available_quantity = $stock->quantity - $computed;
              $stock->save();
            });
            $updated++;

            if ($stock->available_quantity < 0) {
              $negativeWarnings[] = [
                'product_id' => $stock->product_id,
                'product' => $stock->product->code ?? $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'quantity' => (float) $stock->quantity,
                'reserved_quantity' => $computed,
                'available_quantity' => (float) $stock->available_quantity,
              ];
            }
          } else {
            $unchanged++;
          }

          $bar->advance();
        }
      });

      $bar->finish();
      $this->newLine(2);
    }

    $this->info("Registros corregidos: {$updated}");
    $this->info("Registros ya correctos: {$unchanged}");

    if (!empty($negativeWarnings)) {
      $this->newLine();
      $this->warn('Registros con available_quantity NEGATIVO (reservado supera al stock físico, requieren revisión manual):');
      $this->table(
        ['product_id', 'producto', 'warehouse_id', 'quantity', 'reserved_quantity', 'available_quantity'],
        $negativeWarnings
      );
    }

    if (!empty($orphans)) {
      $this->newLine();
      $this->warn('Reservas encontradas en OT/cotizaciones SIN fila en product_warehouse_stock (no se pueden corregir, requieren revisión manual):');
      $this->table(
        ['product_id', 'warehouse_id', 'cantidad reservada calculada'],
        $orphans
      );
    }

    return 0;
  }

  /**
   * Combinaciones product_id+warehouse_id con reserva calculada > 0 que no tienen
   * fila correspondiente en product_warehouse_stock dentro del alcance consultado.
   */
  private function findOrphanReservations($stockQuery, array $computedMap): array
  {
    $existingKeys = $stockQuery->clone()
      ->get(['product_id', 'warehouse_id'])
      ->map(fn($s) => "{$s->product_id}-{$s->warehouse_id}")
      ->flip();

    $orphans = [];
    foreach ($computedMap as $key => $qty) {
      if (round($qty, 2) <= 0 || $existingKeys->has($key)) {
        continue;
      }
      [$productId, $warehouseId] = explode('-', $key, 2);
      $orphans[] = [$productId, $warehouseId, round($qty, 2)];
    }

    return $orphans;
  }
}