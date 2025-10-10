<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'type_document' => $this->type_document,
      'type_vehicle' => $this->type_vehicle,
      'quote_deadline' => $this->quote_deadline,
      'exchange_rate_id' => $this->exchange_rate_id,
      'exchange_rate' => $this->exchangeRate->rate ?? null,
      'subtotal' => $this->subtotal,
      'total' => $this->total,
      'comment' => $this->comment,
      'opportunity_id' => $this->opportunity_id,
      'holder_id' => $this->holder_id,
      'holder' => $this->holder->full_name ?? null,
      'vehicle_color_id' => $this->vehicle_color_id,
      'vehicle_color' => $this->vehicleColor->description ?? null,
      'ap_models_vn_id' => $this->ap_models_vn_id,
      'ap_model_vn' => $this->apModelsVn->code ?? null,
      'vehicle_vn_id' => $this->vehicle_vn_id,
      'vehicle_vn' => $this->vehicleVn->license_plate ?? null,
      'doc_type_currency_id' => $this->doc_type_currency_id,
      'doc_type_currency' => $this->docTypeCurrency->code ?? null,
    ];
  }
}
