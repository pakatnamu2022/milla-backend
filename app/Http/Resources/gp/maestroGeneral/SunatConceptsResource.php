<?php

namespace App\Http\Resources\gp\maestroGeneral;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SunatConceptsResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code_nubefact' => $this->code_nubefact,
      'description' => $this->description,
      'type' => $this->type,
      'prefix' => $this->prefix,
      'length' => $this->length,
      'tribute_code' => $this->tribute_code,
      'affects_total' => $this->affects_total,
      'iso_code' => $this->iso_code,
      'symbol' => $this->symbol,
      'percentage' => $this->percentage,
      'document_type' => $this->when($this->type === 'TYPE_DOCUMENT', $this->documentType?->id),
      'currency_type' => $this->when($this->type === 'BILLING_CURRENCY', $this->currencyType?->id),
    ];
  }
}
