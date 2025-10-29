<?php

namespace App\Http\Resources\ap\compras;

use App\Models\ap\comercial\BusinessPartners;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderVehicleReceptionResource extends JsonResource
{
  /**
   * Transform the resource into an array for neInTbRecepcion (Reception Header)
   * Mapea datos de PurchaseOrder + Supplier para la cabecera de recepción
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $supplier = BusinessPartners::find($this->supplier_id);

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'RecepcionId' => $this->number_guide,
      'ProveedorId' => $supplier->num_doc,
      'FechaEmision' => $this->emission_date?->format('Y-m-d'),
      'FechaContable' => $this->emission_date?->format('Y-m-d'),
      'TipoComprobanteId' => 'GRM',
      'Serie' => $this->invoice_series,
      'Correlativo' => $this->invoice_number,
      'Procesar' => 1,
      'ProcesoEstado' => 0,
      'ProcesoError' => '',
      'FechaProceso' => now()->format('Y-m-d H:i:s'),
    ];
  }
}
