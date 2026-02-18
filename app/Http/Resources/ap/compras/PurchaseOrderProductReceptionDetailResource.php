<?php

namespace App\Http\Resources\ap\compras;

use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderProductReceptionDetailResource extends JsonResource
{
  /**
   * Transform the resource into an array for neInTbRecepcionDt (Reception Detail/Items)
   * Mapea los items de PurchaseOrder para el detalle de recepción de productos/repuestos.
   * Cada PurchaseOrderItem se convierte en una línea de recepción usando product->dyn_code.
   *
   * @return array<string, mixed>
   * @throws Exception
   */
  public function toArray(Request $request): array
  {
    $purchaseOrder = $this->resource;
    $items = $purchaseOrder->items;
    $warehouse = $purchaseOrder->warehouse;
    $code_reception = substr_replace($purchaseOrder->number, 'NI', 0, 2);

    if (!$items || $items->isEmpty()) {
      throw new Exception("No items found for purchase order {$purchaseOrder->number}");
    }

    $result = [];
    $lineNumber = 1;

    foreach ($items as $item) {
      $product = $item->product;
      $articleId = $product?->dyn_code;
      $siteId = $warehouse?->dyn_code;

      if (!$articleId) {
        throw new Exception("dyn_code not found for product {$item->product_id} in purchase order {$purchaseOrder->number}");
      }

      if (!$siteId) {
        throw new Exception("Site dyn_code not found for warehouse in purchase order {$purchaseOrder->number}");
      }

      $result[] = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'RecepcionId' => $code_reception,
        'Linea' => $lineNumber,
        'OrdenCompraId' => $purchaseOrder->number,
        'LineaOC' => $lineNumber,
        'ArticuloId' => $articleId,
        'SitioId' => $siteId,
        'UnidadMedidaId' => $item->unitMeasurement?->code ?? 'UND',
        'Cantidad' => $item->quantity,
      ];

      $lineNumber++;
    }

    return $result;
  }
}
