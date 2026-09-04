<?php

namespace App\Http\Resources\ap\postventa\taller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApOrderQuotationsSimpleResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'quotation_number' => $this->quotation_number,
      'quotation_date' => $this->quotation_date,
      'expiration_date' => $this->expiration_date,
      'observations' => $this->observations,
      'currency_id' => $this->currency_id,
      'exchange_rate' => (float)$this->exchange_rate,
      'subtotal' => (float)$this->subtotal,
      'discount_amount' => (float)$this->discount_amount,
      'tax_amount' => (float)$this->tax_amount,
      'total_amount' => (float)$this->total_amount,
      'type_currency' => [
        'id' => $this->typeCurrency->id,
        'name' => $this->typeCurrency->name,
        'symbol' => $this->typeCurrency->symbol,
      ],
      'vehicle' => $this->when($this->vehicle, [
        'id' => $this->vehicle?->id,
        'plate' => $this->vehicle?->plate,
        'vin' => $this->vehicle?->vin,
        'year' => $this->vehicle?->year,
        'model' => $this->when($this->vehicle?->model, [
          'brand' => $this->vehicle?->model?->family?->brand?->name,
          'version' => $this->vehicle?->model?->version,
        ]),
        'owner' => $this->when($this->vehicle?->customer, [
          'full_name' => $this->vehicle?->customer?->full_name,
          'num_doc' => $this->vehicle?->customer?->num_doc,
          'phone' => $this->vehicle?->customer?->phone,
        ]),
      ]),
      'client' => $this->when($this->client, [
        'id' => $this->client?->id,
        'full_name' => $this->client?->full_name,
        'num_doc' => $this->client?->num_doc,
        'phone' => $this->client?->phone,
        'email' => $this->client?->email,
      ]),
    ];
  }
}
