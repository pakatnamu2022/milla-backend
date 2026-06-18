<?php

namespace App\Http\Resources\ap\postventa\taller;

use App\Http\Resources\ap\ApMastersResource;
use App\Http\Resources\ap\comercial\BusinessPartnersResource;
use App\Http\Resources\ap\comercial\VehiclesResource;
use App\Models\ap\postventa\DiscountRequestsWorkOrder;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\GeneralMaster;
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
      'vehicle_inspection_id' => $this->vehicle_inspection_id,
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
      'invoice_to' => $this->invoice_to,
      'invoice_to_client' => BusinessPartnersResource::make($this->invoiceTo),
      'sede_id' => $this->sede_id,
      'sede_name' => $this->sede ? $this->sede->abreviatura : null,
      'opening_date' => $this->opening_date?->format('Y-m-d H:i:s'),
      'estimated_delivery_date' => $this->estimated_delivery_date?->format('Y-m-d H:i:s'),
      'estimated_delivery_time' => $this->estimated_delivery_time?->format('H:i:s'),
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
      'created_by_name' => $this->creator ? $this->creator->name : null,
      'is_inspection_completed' => $this->vehicleInspection && !$this->vehicleInspection->is_cancelled,
      'allow_remove_associated_quote' => (bool)$this->allow_remove_associated_quote,
      'allow_editing_inspection' => (bool)$this->allow_editing_inspection,
      'is_delivery' => (bool)$this->is_delivery,
      'delivery_by_name' => $this->deliveryBy ? $this->deliveryBy->name : null,
      'status' => new ApMastersResource($this->status),
      'has_management_discount' => $this->discountRequests && $this->discountRequests->where('status', DiscountRequestsWorkOrder::STATUS_APPROVED)->isNotEmpty(),
      'cost_man_hours' => $this->when(
        isset($this->additional['includeCostManHours']) && $this->additional['includeCostManHours'],
        fn() => $this->vehicle->is_heavy
          ? GeneralMaster::find(GeneralMaster::COST_PER_MAN_HOUR_VP_ID)->value
          : GeneralMaster::find(GeneralMaster::COST_PER_MAN_HOUR_VL_ID)->value
      ),
      'is_invalid_with_quote' => $this->orderQuotation
        ? $this->orderQuotation->details->contains('status', ApOrderQuotationDetails::STATUS_PENDING)
        : false,
      'num_doc_contact' => $this->num_doc_contact,
      'full_contact_name' => $this->full_contact_name,
      'phone_contact' => $this->phone_contact,
      'num_doc_pickup' => $this->num_doc_pickup,
      'full_pickup_name' => $this->full_pickup_name,
      'phone_pickup' => $this->phone_pickup,
      'discard_reason' => $this->discardReason?->description,
      'discarded_note' => $this->discarded_note,
      'discarded_by_name' => $this->discardedBy?->name,
      'discarded_at' => $this->discarded_at,
      'exchange_rate' => (float)$this->exchange_rate,

      // Loaded Relationships
      'labours' => WorkOrderLabourResource::collection($this->whenLoaded('labours')),
      'parts' => ApWorkOrderPartsResource::collection($this->whenLoaded('parts')),
      'vehicle_inspection' => new ApVehicleInspectionResource($this->whenLoaded('vehicleInspection')),
      'items' => WorkOrderItemResource::collection($this->whenLoaded('items')),
      'order_quotation' => new ApOrderQuotationsResource($this->whenLoaded('orderQuotation')),
      'vouchers' => $this->when(
        $this->relationLoaded('advancesWorkOrder'),
        fn() => $this->getDocumentsTree()
      ),
      'payment_summary' => $this->when(
        $this->relationLoaded('advancesWorkOrder'),
        fn() => $this->getPaymentSummary()
      ),
      'internal_note' => new InternalNoteResource($this->whenLoaded('internalNote')),
    ];
  }
}
