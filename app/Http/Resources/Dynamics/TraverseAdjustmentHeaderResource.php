<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TraverseAdjustmentHeaderResource extends JsonResource
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

    // Formato: TRV-{id} (salida) o TRV-{id}* (reversión/ingreso)
    $transactionId = "TRV-{$this->id}";
    if ($this->isReversal) {
      $transactionId .= '*';
    }

    // Retornamos el header base SIN las fechas específicas
    // Las fechas se asignarán en el controlador según la purchase_date
    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'TransaccionId' => $transactionId,
      // 'FechaEmision' y 'FechaContable' se asignarán dinámicamente por fecha de compra
      'Procesar' => 1,
      'ProcesoEstado' => 0,
      'ProcesoError' => '',
      'FechaProceso' => now()->format('Y-m-d H:i:s'),
    ];
  }
}