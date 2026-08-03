<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ApInternalNote;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InternalNoteTransactionHeaderResource extends JsonResource
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

    // Obtener el movimiento de inventario para usar su fecha
    $inventoryMovement = $this->inventoryMovements()
      ->where('movement_type', $this->isReversal
        ? \App\Models\ap\postventa\gestionProductos\InventoryMovement::TYPE_ADJUSTMENT_IN
        : \App\Models\ap\postventa\gestionProductos\InventoryMovement::TYPE_ADJUSTMENT_OUT)
      ->first();

    // Si existe el movimiento, usar su fecha; sino, usar la fecha de creación de la nota
    $movementDate = $inventoryMovement
      ? $inventoryMovement->movement_date->format('Y-m-d')
      : $this->created_date->format('Y-m-d');

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'TransaccionId' => $transactionId,
      'FechaEmision' => $movementDate,
      'FechaContable' => $movementDate,
      'Procesar' => 1,
      'ProcesoEstado' => 0,
      'ProcesoError' => '',
      'FechaProceso' => now()->format('Y-m-d H:i:s'),
    ];
  }
}