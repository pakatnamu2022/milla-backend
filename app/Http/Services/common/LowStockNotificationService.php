<?php

namespace App\Http\Services\common;

use App\Http\Traits\SendsNotifications;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\ProductWarehouseStock;
use App\Models\gp\gestionsistema\Position;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LowStockNotificationService
{
  use SendsNotifications;

  /**
   * Notificar a los encargados de almacén sobre productos con stock bajo
   *
   * @return array
   */
  public function notifyLowStock(): array
  {
    try {
      $results = [];
      $totalNotifications = 0;

      // Obtener productos con stock bajo agrupados por almacén
      $lowStockProducts = ProductWarehouseStock::lowStock()
        ->with(['product', 'warehouse.sede'])
        ->whereHas('warehouse', function ($query) {
          $query->where('status', true);
        })
        ->where('status', true)
        ->get()
        ->groupBy('warehouse_id');

      // Si no hay productos con stock bajo, retornar
      if ($lowStockProducts->isEmpty()) {
        return [
          'success' => true,
          'message' => 'No hay productos con stock bajo',
          'total_notifications' => 0,
          'results' => []
        ];
      }

      // Procesar cada almacén con stock bajo
      foreach ($lowStockProducts as $warehouseId => $products) {
        $warehouse = $products->first()->warehouse;
        $sedeId = $warehouse->sede_id;

        if (!$sedeId) {
          Log::warning("Warehouse ID {$warehouseId} no tiene sede asignada");
          continue;
        }

        // Obtener usuarios de almacén para esta sede
        $warehouseUsers = $this->getWarehouseUsers($sedeId);

        if ($warehouseUsers->isEmpty()) {
          Log::warning("No hay usuarios de almacén para la sede ID {$sedeId}");
          continue;
        }

        // Preparar el mensaje con la lista de productos
        $productList = $this->buildProductList($products);
        $sedeName = $warehouse->sede->suc_abrev ?? $warehouse->sede->razon_social ?? 'N/A';
        $warehouseName = $warehouse->description;

        $title = "⚠️ Stock Bajo - {$sedeName}";
        $body = "Hay {$products->count()} producto(s) con stock por debajo del mínimo en {$warehouseName}:\n\n{$productList}";

        // Enviar notificación a todos los usuarios de almacén de esta sede
        $userIds = $warehouseUsers->pluck('id')->toArray();

        $notification = $this->notify(
          title: $title,
          body: $body,
          type: 'warehouse.stock.low',
          userIds: $userIds,
          source: $warehouse,
          data: [
            'warehouse_id' => $warehouseId,
            'sede_id' => $sedeId,
            'products_count' => $products->count(),
            'warehouse_name' => $warehouseName,
            'sede_name' => $sedeName
          ],
          route: '/ap/post-venta/gestion-de-almacen/inventario' // Puedes agregar una ruta al frontend si existe
        );

        $totalNotifications++;
        $results[] = [
          'warehouse_id' => $warehouseId,
          'warehouse_name' => $warehouseName,
          'sede_id' => $sedeId,
          'sede_name' => $sedeName,
          'products_count' => $products->count(),
          'users_notified' => count($userIds),
          'notification_id' => $notification->id
        ];
      }

      return [
        'success' => true,
        'message' => "Se enviaron {$totalNotifications} notificaciones",
        'total_notifications' => $totalNotifications,
        'results' => $results
      ];
    } catch (\Exception $e) {
      Log::error('Error al notificar stock bajo: ' . $e->getMessage(), [
        'exception' => $e
      ]);

      return [
        'success' => false,
        'message' => 'Error al procesar notificaciones',
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * Obtener usuarios con cargo de almacén asignados a una sede
   *
   * @param int $sedeId
   * @return \Illuminate\Support\Collection
   */
  private function getWarehouseUsers(int $sedeId)
  {
    // Combinar los IDs de cargos de almacén (asistente y jefe)
    $warehousePositionIds = array_merge(
      Position::WAREHOUSE_ASSISTANT,
      Position::WAREHOUSE_MANAGER
    );

    // Obtener los usuarios con cargo de almacén asignados a la sede
    return User::whereHas('person', function ($query) use ($warehousePositionIds) {
      $query->whereIn('cargo_id', $warehousePositionIds)
        ->where('status_deleted', 1)
        ->where('status_id', 22);
    })
      ->whereHas('sedes', function ($query) use ($sedeId) {
        $query->where('config_sede.id', $sedeId)
          ->where('assigment_user_sede.status', true);
      })
      ->with('person')
      ->get();
  }

  /**
   * Construir lista de productos con stock bajo
   *
   * @param \Illuminate\Support\Collection $products
   * @return string
   */
  private function buildProductList($products): string
  {
    $list = [];
    foreach ($products as $product) {
      $productName = $product->product?->name ?? 'Producto sin nombre';
      $productCode = $product->product?->code ?? 'N/A';
      $currentStock = number_format($product->quantity, 2);
      $minimumStock = number_format($product->minimum_stock, 2);

      $list[] = "• {$productName} ({$productCode}): Stock actual {$currentStock} / Mínimo {$minimumStock}";
    }

    return implode("\n", $list);
  }

  /**
   * Obtener estadísticas de stock bajo por sede
   *
   * @return array
   */
  public function getLowStockStats(): array
  {
    $stats = ProductWarehouseStock::lowStock()
      ->with(['warehouse.sede'])
      ->whereHas('warehouse', function ($query) {
        $query->where('status', true);
      })
      ->where('status', true)
      ->get()
      ->groupBy('warehouse.sede_id')
      ->map(function ($products, $sedeId) {
        $warehouse = $products->first()->warehouse;
        $sedeName = $warehouse->sede->suc_abrev ?? $warehouse->sede->razon_social ?? 'N/A';

        return [
          'sede_id' => $sedeId,
          'sede_name' => $sedeName,
          'products_count' => $products->count(),
          'warehouses' => $products->groupBy('warehouse_id')->map(function ($warehouseProducts) {
            $wh = $warehouseProducts->first()->warehouse;
            return [
              'warehouse_id' => $wh->id,
              'warehouse_name' => $wh->description,
              'products_count' => $warehouseProducts->count()
            ];
          })->values()
        ];
      });

    return [
      'total_sedes' => $stats->count(),
      'total_products' => ProductWarehouseStock::lowStock()->count(),
      'details' => $stats->values()->toArray()
    ];
  }
}
