<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseRequestQuoteDiscountResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'              => $this->id,
      'type'            => $this->type,
      'percentage'      => $this->percentage,
      'amount'          => $this->amount,
      'igv'             => $this->igv,
      'valor_unitario'  => $this->valor_unitario,
      'precio_unitario' => $this->precio_unitario,
      'is_negative'     => $this->is_negative,
      'has_retention'   => $this->has_retention,
      'concept_code_id'        => $this->concept_code_id,
      'concept_code'           => $this->conceptCode->description ?? null,
      'concept_code_parent_id' => $this->conceptCode->parent_id ?? null,
    ];
  }
}