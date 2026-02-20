<?php

namespace App\Http\Resources\Dynamics;

use App\Models\ap\comercial\ShippingGuides;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingGuideHeaderDynamicsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Usar dyn_series si existe, sino generar uno nuevo

    throw new \Exception($this->dyn_series);
    if (!empty($this->dyn_series)) {
      $transactionId = $this->dyn_series;
    } else {
      // Determinar el prefijo del TransaccionId para ventas
      $prefix = $this->transfer_reason_id === SunatConcepts::TRANSFER_REASON_VENTA
        ? 'TVEN-'
        : 'TSAL-';

      // Preparar TransaccionId
      $transactionId = $prefix . str_pad($this->correlative, 8, '0', STR_PAD_LEFT);
    }

    // Agregar asterisco si está cancelada
    $isCancelled = $this->status === false || $this->cancelled_at !== null;
    if ($isCancelled && !str_ends_with($transactionId, '*')) {
      $transactionId .= '*';
    }

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'TransaccionId' => $transactionId,
      'FechaEmision' => $this->issue_date->format('Y-m-d'),
      'FechaContable' => $this->issue_date->format('Y-m-d'),
      'Procesar' => 1,
      'ProcesoEstado' => 0,
      'ProcesoError' => '',
      'FechaProceso' => now()->format('Y-m-d H:i:s'),
    ];
  }
}
