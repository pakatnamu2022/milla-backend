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
      'correlative' => $this->correlative,
      'type_document' => $this->type_document,
      'type_vehicle' => $this->type_vehicle,
      'quote_deadline' => $this->quote_deadline,
      'exchange_rate_id' => $this->exchange_rate_id,
      'exchange_rate' => round($this->exchangeRate->rate ?? 1, 4) ?? null,
      'base_selling_price' => round($this->base_selling_price, 2),
      'sale_price' => round($this->sale_price, 2),
      'doc_sale_price' => round($this->doc_sale_price, 2),
      'type_currency_id' => $this->type_currency_id,
      'type_currency' => $this->typeCurrency->code ?? null,
      'type_currency_symbol' => $this->typeCurrency->code . ' ' . $this->typeCurrency->symbol ?? null,
      'comment' => $this->comment,
      'is_invoiced' => $this->is_invoiced,
      'is_approved' => $this->is_approved,
      'opportunity_id' => $this->opportunity_id,
      'holder_id' => $this->holder_id,
      'holder' => $this->holder->full_name ?? null,
      'client_name' => $this->oportunity->client->full_name ?? null,
      'vehicle_color_id' => $this->vehicle_color_id,
      'vehicle_color' => $this->vehicleColor->description ?? null,
      'ap_models_vn_id' => $this->ap_models_vn_id,
      'ap_model_vn' => $this->apModelsVn->code ?? null,
      'ap_vehicle_purchase_order_id' => $this->ap_vehicle_purchase_order_id,
      'ap_vehicle_purchase_order' => $this->vehiclePurchaseOrders->vin ?? null,
      'doc_type_currency_id' => $this->doc_type_currency_id ?? null,
      'doc_type_currency' => $this->docTypeCurrency->code ?? null,
      'advisor_name' => $this->oportunity->worker->nombre_completo ?? null,
      'warranty' => $this->warranty,
      'bonus_discounts' => $this->discountCoupons->map(function ($discount) {
        return [
          'id' => $discount->id,
          'description' => $discount->description,
          'type' => $discount->type,
          'percentage' => $discount->percentage,
          'amount' => $discount->amount,
          'concept_code_id' => $discount->concept_code_id,
          'concept_code' => $discount->conceptCode->description ?? null,
        ];
      }),
      'accessories' => $this->accessories->map(function ($accessory) {
        return [
          'id' => $accessory->id,
          'approved_accessory_id' => $accessory->approved_accessory_id,
          'description' => $accessory->approvedAccessory->description ?? null,
          'type' => $accessory->type,
          'quantity' => $accessory->quantity,
          'price' => $accessory->price,
          'total' => $accessory->total,
        ];
      }),
      'sede_id' => $this->sede_id ?? null,
      'sede' => $this->sede->abreviatura ?? null,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
    ];
  }
}
