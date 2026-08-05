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

    // SIEMPRE generar TransaccionId con el nuevo formato
    // Salida: PS-IN-00045
    // Reversión/Ingreso: PI-IN-00045
    $prefix = $this->isReversal ? 'PI-' : 'PS-';
    $transactionId = $prefix . $this->number;

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