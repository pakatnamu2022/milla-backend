<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ApInternalNote;
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

    if (!empty($this->dyn_series)) {
      $transactionId = $this->dyn_series;
    } else {
      // Quitar el prefijo "IN-" y agregar "NIP-"
      $number = str_replace('IN-', '', $this->number);
      $transactionId = 'NIP-' . $number;
    }

    // Agregar asterisco si es reversión
    if ($this->isReversal && !str_ends_with($transactionId, '*')) {
      $transactionId .= '*';
    }

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
      // La cantidad ya viene con el signo correcto del movimiento
      // Si es reversión (ADJUSTMENT_IN), la cantidad es positiva
      // Si no es reversión (ADJUSTMENT_OUT), la cantidad es negativa
      $cantidad = $detail->quantity;

      $details[] = [
        'EmpresaId' => Company::AP_DYNAMICS,
        'TransaccionId' => $transactionId,
        'Linea' => $lineNumber,
        'ArticuloId' => $detail->code ?? 'N/A',
        'Motivo' => $this->isReversal ? 'REVERSION NOTA INTERNA' : 'NOTA INTERNA TALLER',
        'UnidadMedidaId' => $detail->product->unit_measurement_id ?? 'UND',
        'Cantidad' => $cantidad,
        'AlmacenId' => $warehouse->dyn_code ?? '',
        'CostoUnitario' => $detail->unit_cost ?? 0,
        'CuentaInventario' => $warehouse->inventory_account . '-' . $sede->dyn_code ?? '',
        'CuentaContrapartida' => $warehouse->counterparty_account . '-' . $sede->dyn_code ?? '',
      ];

      $lineNumber++;
    }

    return $details;
  }
}