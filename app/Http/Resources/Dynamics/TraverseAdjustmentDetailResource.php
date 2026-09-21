<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class TraverseAdjustmentDetailResource extends JsonResource
{
  /**
   * Indica si es una reversión
   */
  public bool $isReversal;

  /**
   * Constructor
   */
  public function __construct($resource, bool $isReversal = false)
  {
    parent::__construct($resource);
    $this->isReversal = $isReversal;
  }

  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    /** @var ElectronicDocument $this */

    $transactionId = "TRV-{$this->id}";
    if ($this->isReversal) {
      $transactionId .= '*';
    }

    // Obtener todos los items en travesía con sus transacciones activas
    $traverseItems = $this->items()
      ->where('is_traverse', true)
      ->whereNotNull('product_id')
      ->with([
        'linkTransactions' => function ($query) {
          $query->where('status', 'active')
            ->with(['purchaseOrderItem.product.articleClass', 'purchaseOrderItem.purchaseOrder']);
        },
        'product.unitMeasurement'
      ])
      ->get();

    if ($traverseItems->isEmpty()) {
      throw new Exception("No se encontraron items en travesía con product_id válido.");
    }

    // Obtener la sede del documento electrónico
    if (!$this->seriesModel || !$this->seriesModel->sede_id) {
      throw new Exception("El documento electrónico no tiene una sede asociada.");
    }

    $sedeId = $this->seriesModel->sede_id;

    // Obtener el almacén físico de postventa para esta sede
    $warehouse = Warehouse::getPhysicalWarehouseForPostsale($sedeId);
    if (!$warehouse) {
      throw new Exception("No se pudo obtener el almacén físico de postventa para la sede ID {$sedeId}.");
    }

    if (!$warehouse->sede) {
      throw new Exception("El almacén físico de postventa no tiene sede asociada.");
    }

    $sede = $warehouse->sede;
    $allDetails = [];

    // PASO 1: Agrupar por producto (sin importar la fecha de compra)
    $groupedByProduct = [];

    foreach ($traverseItems as $item) {
      $product = $item->product;
      if (!$product) {
        throw new Exception("El item de travesía no tiene producto asociado.");
      }

      // Verificar que tenga transacciones activas
      if ($item->linkTransactions->isEmpty()) {
        throw new Exception("El producto '{$product->name}' no tiene transacciones de travesía activas.");
      }

      $articleClassId = $product->ap_class_article_id;
      if (!$articleClassId) {
        throw new Exception("El producto '{$product->name}' no tiene clase de artículo asignada.");
      }

      // Buscar el warehouse específico para este producto según su clase de artículo
      $productWarehouse = Warehouse::where('sede_id', $sede->id)
        ->where('type_operation_id', ApMasters::TIPO_OPERACION_POSTVENTA)
        ->where('article_class_id', $articleClassId)
        ->where('status', true)
        ->first();

      if (!$productWarehouse) {
        throw new Exception(
          "No se encontró warehouse para la sede {$sede->name}, tipo POSTVENTA y clase de artículo ID {$articleClassId} del producto '{$product->name}'."
        );
      }

      // Obtener las cuentas contables del warehouse específico del producto
      $cuentaInventario = $productWarehouse->inventory_account
        ? $productWarehouse->inventory_account . '-' . $sede->dyn_code
        : throw new Exception("El warehouse no tiene cuenta de inventario configurada para el producto '{$product->name}'.");

      $cuentaContrapartida = $productWarehouse->counterparty_account
        ? $productWarehouse->counterparty_account . '-' . $sede->dyn_code
        : throw new Exception("El warehouse no tiene cuenta contrapartida configurada para el producto '{$product->name}'.");

      // Agrupar solo por producto (acumular todas las cantidades sin importar fecha de compra)
      foreach ($item->linkTransactions as $transaction) {
        $quantity = (float)$transaction->cantidad;
        $unitCost = (float)$transaction->purchaseOrderItem->unit_price;

        $productId = $product->id;

        if (!isset($groupedByProduct[$productId])) {
          $groupedByProduct[$productId] = [
            'product' => $product,
            'warehouse' => $warehouse,
            'cuenta_inventario' => $cuentaInventario,
            'cuenta_contrapartida' => $cuentaContrapartida,
            'total_quantity' => 0,
            'total_cost' => 0,
          ];
        }

        $groupedByProduct[$productId]['total_quantity'] += $quantity;
        $groupedByProduct[$productId]['total_cost'] += $quantity * $unitCost;
      }
    }

    // PASO 2: Generar las líneas del ajuste
    $lineNumber = 1;

    foreach ($groupedByProduct as $productId => $data) {
      $product = $data['product'];
      $totalQuantity = $data['total_quantity'];
      $totalCost = $data['total_cost'];
      $averageCost = $totalQuantity > 0 ? $totalCost / $totalQuantity : 0;

      // Asegurar el signo correcto de la cantidad:
      // - Salida (normal): cantidad NEGATIVA (porque se está vendiendo/usando)
      // - Reversión (ingreso): cantidad POSITIVA (porque se está devolviendo al inventario)
      $cantidad = $this->isReversal ? $totalQuantity : -$totalQuantity;

      $allDetails[] = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransaccionId' => $transactionId,
        'Linea' => $lineNumber,
        'ArticuloId' => $product->dyn_code ?? 'N/A',
        'Motivo' => '',
        'UnidadMedidaId' => $product->unitMeasurement->dyn_code ?? 'UND',
        'Cantidad' => $cantidad,
        'AlmacenId' => $data['warehouse']->dyn_code ?? '',
        'CostoUnitario' => round($averageCost, 2),
        'CuentaInventario' => $data['cuenta_inventario'],
        'CuentaContrapartida' => $data['cuenta_contrapartida'],
      ];

      $lineNumber++;
    }

    return $allDetails;
  }
}
