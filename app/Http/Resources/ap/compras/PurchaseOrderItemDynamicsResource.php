<?php

namespace App\Http\Resources\ap\compras;

use App\Models\ap\compras\PurchaseOrderItem;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemDynamicsResource extends JsonResource
{
  /**
   * Transform the resource collection into an array for Dynamics sync
   * @throws Exception
   */
  public function toArray(Request $request): array
  {
    // Este resource maneja una colección de items de la orden de compra
    $items = $this->resource;

    if (!$items || $items->isEmpty()) {
      throw new Exception("No items found for purchase order");
    }

    $result = [];
    $lineNumber = 1;

    foreach ($items as $item) {
      // Determinar el ArticuloId según el tipo de ítem
      $articleId = null;
      $siteId = $item->purchaseOrder->warehouse?->dyn_code;

      if ($item->is_vehicle && $item->vehicleInfo) {
        // Si es un vehículo, obtener el código del modelo
        $articleId = $item->vehicleInfo->model?->code;
      } else {
        // Para otros tipos de ítems, usar la descripción o un código específico
        // TODO: Implementar lógica para otros tipos de ítems cuando sea necesario
        $articleId = $item->product->dyn_code;
      }

      if (!$articleId) throw new Exception("Article ID not found for item {$item->id}");
      if (!$siteId) throw new Exception("Site ID not found for item {$item->id}");

      $result[] = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'OrdenCompraId' => $item->purchaseOrder->number,
        'Linea' => $lineNumber++,
        'ArticuloId' => $articleId,
        'SitioId' => $siteId,
        'UnidadMedidaId' => $item->unitMeasurement?->dyn_code ?? 'UND',
        'Cantidad' => $item->quantity,
        'CostoUnitario' => $item->unit_price,
        'CuentaNumeroInventario' => '',
        'CodigoDimension1' => '',
        'CodigoDimension2' => '',
        'CodigoDimension3' => '',
        'CodigoDimension4' => '',
        'CodigoDimension5' => '',
        'CodigoDimension6' => '',
        'CodigoDimension7' => '',
        'CodigoDimension8' => '',
        'CodigoDimension9' => '',
        'CodigoDimension10' => '',
      ];
    }

    return $result;
  }
}
