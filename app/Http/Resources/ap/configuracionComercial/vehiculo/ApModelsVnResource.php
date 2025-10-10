<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApModelsVnResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'version' => $this->version,
      'power' => $this->power,
      'model_year' => $this->model_year,
      'wheelbase' => $this->wheelbase,
      'axles_number' => $this->axles_number,
      'width' => $this->width,
      'length' => $this->length,
      'height' => $this->height,
      'seats_number' => $this->seats_number,
      'doors_number' => $this->doors_number,
      'net_weight' => $this->net_weight,
      'gross_weight' => $this->gross_weight,
      'payload' => $this->payload,
      'displacement' => $this->displacement,
      'cylinders_number' => $this->cylinders_number,
      'passengers_number' => $this->passengers_number,
      'wheels_number' => $this->wheels_number,
      'distributor_price' => $this->distributor_price,
      'transport_cost' => $this->transport_cost,
      'other_amounts' => $this->other_amounts,
      'purchase_discount' => $this->purchase_discount,
      'igv_amount' => $this->igv_amount,
      'total_purchase_excl_igv' => $this->total_purchase_excl_igv,
      'total_purchase_incl_igv' => $this->total_purchase_incl_igv,
      'sale_price' => $this->sale_price,
      'margin' => $this->margin,
      'brand_id' => $this->family->brand_id,
      'brand' => $this->family->brand->name,
      'family_id' => $this->family_id,
      'family' => $this->family->description,
      'class_id' => $this->class_id,
      'class' => $this->classArticle->description,
      'fuel_id' => $this->fuel_id,
      'fuel' => $this->fuelType->description,
      'vehicle_type_id' => $this->vehicle_type_id,
      'vehicle_type' => $this->vehicleType->description,
      'body_type_id' => $this->body_type_id,
      'body_type' => $this->bodyType->description,
      'traction_type_id' => $this->traction_type_id,
      'traction_type' => $this->tractionType->description,
      'transmission_id' => $this->transmission_id,
      'transmission' => $this->vehicleTransmission->description,
      'currency_type_id' => $this->currency_type_id,
      'currency_type' => $this->typeCurrency->name,
      'currency_symbol' => $this->typeCurrency->symbol,
      'status' => $this->status,
    ];
  }
}
