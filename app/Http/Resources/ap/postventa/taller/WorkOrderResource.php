<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\ApMastersResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'correlative' => $this->correlative,
      'order_quotation_id' => $this->order_quotation_id,
      'appointment_planning_id' => $this->appointment_planning_id,
      'vehicle_id' => $this->vehicle_id,
      'vehicle' => new VehiclesResource($this->vehicle),
      'vehicle_plate' => $this->vehicle_plate,
      'vehicle_vin' => $this->vehicle_vin,
      'currency_id' => $this->currency_id,
      'type_currency' => $this->typeCurrency,
      'mileage' => $this->vehicleInspection->mileage ?? null,
      'status_id' => $this->status_id,
      'status_name' => $this->status ? $this->status->description : null,
      'advisor_id' => $this->advisor_id,
      'advisor_name' => $this->advisor ? $this->advisor->nombre_completo : null,
      'sede_id' => $this->sede_id,
      'sede_name' => $this->sede ? $this->sede->abreviatura : null,
      'opening_date' => $this->opening_date?->format('Y-m-d H:i:s'),
      'estimated_delivery_date' => $this->estimated_delivery_date?->format('Y-m-d H:i:s'),
      'actual_delivery_date' => $this->actual_delivery_date?->format('Y-m-d H:i:s'),
      'diagnosis_date' => $this->diagnosis_date?->format('Y-m-d H:i:s'),
      'observations' => $this->observations,
      'total_labor_cost' => (float)$this->total_labor_cost,
      'total_parts_cost' => (float)$this->total_parts_cost,
      'subtotal' => (float)$this->subtotal,
      'discount_percentage' => (float)$this->discount_percentage,
      'discount_amount' => (float)$this->discount_amount,
      'tax_amount' => (float)$this->tax_amount,
      'final_amount' => (float)$this->final_amount,
      'is_invoiced' => (bool)$this->is_invoiced,
      'is_guarantee' => (bool)$this->is_guarantee,
      'is_recall' => (bool)$this->is_recall,
      'description_recall' => $this->description_recall,
      'type_recall' => $this->type_recall,
      'created_by' => $this->created_by,
      'creator_name' => $this->creator ? $this->creator->name : null,
      'is_inspection_completed' => !!$this->vehicleInspection,
      'labours' => WorkOrderLabourResource::collection($this->whenLoaded('labours')),
      'parts' => ApWorkOrderPartsResource::collection($this->whenLoaded('parts')),
      'vehicle_inspection' => new ApVehicleInspectionResource($this->whenLoaded('vehicleInspection')),
      'items' => WorkOrderItemResource::collection($this->whenLoaded('items')),
      'order_quotation' => new ApOrderQuotationsResource($this->whenLoaded('orderQuotation')),
      'advances' => ElectronicDocumentResource::collection(
        $this->whenLoaded('advancesWorkOrder', fn() => $this->advancesWorkOrder->filter(fn($advance) => $advance->aceptada_por_sunat == 1))
      ),
      'status' => new ApMastersResource($this->status)
    ];
  }
}
