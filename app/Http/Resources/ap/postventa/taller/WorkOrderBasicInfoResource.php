<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\ApMastersResource;
use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Simplified WorkOrder resource for contexts that need basic info only (e.g., inventory movements)
 * Contains core WorkOrder fields and advances, without heavy relations like parts, labours, etc.
 */
class WorkOrderBasicInfoResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'correlative' => $this->correlative,
      'total_labor_cost' => (float)$this->total_labor_cost,
      'total_parts_cost' => (float)$this->total_parts_cost,
      'subtotal' => (float)$this->subtotal,
      'discount_percentage' => (float)$this->discount_percentage,
      'discount_amount' => (float)$this->discount_amount,
      'tax_amount' => (float)$this->tax_amount,
      'final_amount' => (float)$this->final_amount,
      'vehicle_plate' => $this->vehicle_plate,
      'opening_date' => $this->opening_date?->format('Y-m-d H:i:s'),
      'observations' => $this->observations,

      // Relaciones simplificadas para evitar sobrecarga de datos
      'invoice_to_client' => BusinessPartnersResource::make($this->invoiceTo),
      'valid_documents' => ElectronicDocumentResource::collection(
        $this->whenLoaded('advancesWorkOrder', fn() => $this->getValidDocuments())
      ),
    ];
  }
}
