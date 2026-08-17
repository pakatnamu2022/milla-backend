<?php

namespace App\Http\Resources\ap\comercial;

use App\Http\Resources\ap\comercial\PurchaseRequestQuoteAccessoryResource;
use App\Http\Resources\ap\comercial\PurchaseRequestQuoteDiscountResource;
use App\Http\Resources\ap\comercial\PurchaseRequestQuoteOtherResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Resources\ap\facturacion\ElectronicDocumentResource;
use App\Http\Resources\gp\gestionhumana\personal\WorkerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteResource extends JsonResource
{
  protected bool $showExtra = false;

  public function showExtra($show = true): static
  {
    $this->showExtra = $show;
    return $this;
  }

  public function toArray(Request $request): array
  {
    $response = [
      'id'                           => $this->id,
      'is_paid'                      => $this->is_paid,
      'correlative'                  => $this->correlative,
      'type_document'                => $this->type_document,
      'type_vehicle'                 => $this->type_vehicle,
      'quote_deadline'               => $this->quote_deadline,
      'exchange_rate_id'             => $this->exchange_rate_id,
      'exchange_rate'                => round($this->exchangeRate->rate ?? 1, 4) ?? null,
      'base_selling_price'           => round($this->base_selling_price, 2),
      'sale_price'                   => round($this->sale_price, 2),
      'doc_sale_price'               => round($this->doc_sale_price, 2),
      'down_payment'                 => $this->down_payment ? round($this->down_payment, 2) : null,
      'margin_amount'                => $this->margin_amount ? round($this->margin_amount, 2) : null,
      'margin_pct'                   => $this->margin_pct ? round($this->margin_pct, 4) : null,
      'type_currency_id'             => $this->type_currency_id,
      'type_currency'                => $this->typeCurrency->code ?? null,
      'type_currency_symbol'         => $this->typeCurrency->code . ' ' . $this->typeCurrency->symbol ?? null,
      'comment'                      => $this->comment,
      'is_invoiced'                  => $this->is_invoiced,
      'is_approved'                  => $this->is_approved,
      'credit_type_id'               => $this->credit_type_id,
      'credit_entity_id'             => $this->credit_entity_id,
      'insurance_entity_id'          => $this->insurance_entity_id,
      'gps_hunter_years'             => $this->gps_hunter_years,
      'opportunity_id'               => $this->opportunity_id,
      'holder_id'                    => $this->holder_id,
      'holder'                       => $this->holder->full_name,
      'holder_document_number'       => $this->holder->num_doc,
      'holder_document_type'         => $this->holder->documentType->id,
      'holder_address'               => $this->holder->direction,
      'holder_email'                 => $this->holder->email,
      'holder_phone'                 => $this->holder->phone,
      'client_name'                  => $this->opportunity?->client->full_name ?? null,
      'ap_vehicle_id'                => $this->ap_vehicle_id,
      'vehicle_color_id'             => $this->vehicle_color_id,
      'vehicle_color'                => $this->vehicleColor->description ?? null,
      'ap_models_vn_id'              => $this->ap_models_vn_id,
      'ap_model_vn'                  => $this->apModelsVn->code ?? null,
      'brand_id'                     => $this->apModelsVn->family->brand_id ?? null,
      'ap_vehicle_purchase_order_id' => $this->ap_vehicle_purchase_order_id,
      'ap_vehicle_purchase_order'    => $this->vehiclePurchaseOrders->vin ?? null,
      'doc_type_currency_id'         => $this->doc_type_currency_id ?? null,
      'doc_type_currency'            => $this->docTypeCurrency->code ?? null,
      'doc_type_currency_symbol'     => $this->docTypeCurrency->symbol ?? null,
      'advisor_name'                 => $this->opportunity?->worker?->nombre_completo ?? null,
      'warranty_years'               => $this->warranty_years,
      'warranty_km'                  => $this->warranty_km,
      'consultant'                   => $this->opportunity?->worker ? WorkerResource::make($this->opportunity->worker) : null,
      'bonus_discounts'              => PurchaseRequestQuoteDiscountResource::collection($this->discountCoupons),
      'accessories'                  => PurchaseRequestQuoteAccessoryResource::collection($this->accessories),
      'others'                       => $this->others
        ? PurchaseRequestQuoteOtherResource::collection($this->others)
        : [],
      'sede_id'                      => $this->sede_id ?? null,
      'sede'                         => $this->sede->abreviatura ?? null,
      'kyc_declaration_id'           => $this->kycDeclaration ? $this->kycDeclaration->id : null,
      'kyc_status'                   => $this->kycDeclaration ? ($this->kycDeclaration->status ?? 'PENDIENTE') : null,
      'created_at'                   => $this->created_at,
      'updated_at'                   => $this->updated_at,
    ];

    /**
     * Attempt to read property "full_name" on null
     */
    if ($this->showExtra) {
      $response['ap_vehicle'] = $this->ap_vehicle_id ? VehiclesResource::make($this->vehicle) : null;
      $response['model'] = $this->apModelsVn ? ApModelsVnResource::make($this->apModelsVn) : null;
      $response['electronic_documents'] = $this->electronicDocuments
        ? ElectronicDocumentResource::collection($this->electronicDocuments)
        : [];
    } else {
      $response['ap_vehicle'] = $this->ap_vehicle_id ? ($this->vehicle->model->version . ' - ' . $this->vehicle->vin) : null;
    }

    return $response;
  }
}
