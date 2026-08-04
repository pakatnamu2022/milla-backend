<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\ApMasters;
use App\Models\ap\facturacion\ApInternalNote;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\ap\postventa\gestionProductos\WeightedAverageCostHistory;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InternalNoteTransactionDetailResource extends JsonResource
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
    /** @var ApInternalNote $this */

    // SIEMPRE generar TransaccionId con el nuevo formato
    // Salida: PS-IN-00045
    // Reversión/Ingreso: PI-IN-00045
    $prefix = $this->isReversal ? 'PI-' : 'PS-';
    $transactionId = $prefix . $this->number;

    // Obtener el movimiento de inventario generado para esta nota interna
    $inventoryMovement = $this->inventoryMovements()
      ->with(['details.product', 'warehouse', 'warehouse.sede'])
      ->where('movement_type', $this->isReversal
        ? \App\Models\ap\postventa\gestionProductos\InventoryMovement::TYPE_ADJUSTMENT_IN
        : \App\Models\ap\postventa\gestionProductos\InventoryMovement::TYPE_ADJUSTMENT_OUT)
      ->first();

    if (!$inventoryMovement) {
      throw new Exception("No se encontró el movimiento de inventario para la nota interna.");
    }

    if ($inventoryMovement->details->isEmpty()) {
      throw new Exception("El movimiento de inventario no tiene detalles.");
    }

    $warehouse = $inventoryMovement->warehouse;
    if (!$warehouse) {
      throw new Exception("No se encontró el almacén asociado al movimiento de inventario.");
    }

    $sede = $warehouse->sede;
    if (!$sede) {
      throw new Exception("No se encontró la sede asociada al almacén.");
    }

    // Construir array de detalles (uno por cada detalle del movimiento)
    $details = [];
    $lineNumber = 1;

    foreach ($inventoryMovement->details as $detail) {
      // Asegurar el signo correcto de la cantidad:
      // - Salida (normal): cantidad NEGATIVA
      // - Reversión (ingreso): cantidad POSITIVA
      $cantidadAbsoluta = abs($detail->quantity);
      $cantidad = $this->isReversal ? $cantidadAbsoluta : -$cantidadAbsoluta;

      // Obtener el producto con su clase de artículo
      $product = $detail->product;
      if (!$product) {
        throw new Exception("El detalle del movimiento no tiene producto asociado.");
      }

      $articleClassId = $product->ap_class_article_id;
      if (!$articleClassId) {
        throw new Exception("El producto '{$product->name}' no tiene clase de artículo asignada.");
      }

      // Buscar el warehouse específico para este producto según su clase de artículo
      // Esto es similar a como lo hace TransferShippingGuideDynamicsService
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

      // Obtener el costo promedio ponderado desde el historial
      // Buscar el costo en la fecha del movimiento de inventario para este producto y almacén
      $costHistory = WeightedAverageCostHistory::where('product_id', $product->id)
        ->where('warehouse_id', $warehouse->id)
        ->where('movement_date', '<=', $inventoryMovement->movement_date)
        ->orderBy('movement_date', 'desc')
        ->orderBy('id', 'desc')
        ->first();

      // Si no hay historial, usar el unit_cost del detalle como fallback
      $costoUnitario = $costHistory ? $costHistory->average_cost_after_movement : ($detail->unit_cost ?? 0);

      $details[] = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransaccionId' => $transactionId,
        'Linea' => $lineNumber,
        'ArticuloId' => $product->dyn_code ?? 'N/A',
        'Motivo' => '',
        'UnidadMedidaId' => $product->unitMeasurement->dyn_code ?? 'UND',
        'Cantidad' => $cantidad,
        'AlmacenId' => $warehouse->dyn_code ?? '',
        'CostoUnitario' => $costoUnitario,
        'CuentaInventario' => $cuentaInventario,
        'CuentaContrapartida' => $cuentaContrapartida,
      ];

      $lineNumber++;
    }

    return $details;
  }
}
