<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'abbreviation' => $this->abbreviation,
      'description' => $this->description,
      'businessName' => $this->businessName,
      'email' => $this->email,
      'logo' => $this->logo,
      'website' => $this->website,
      'phone' => $this->phone,
      'address' => $this->address,
      'city' => $this->city,
      'detraction_amount' => round($this->detraction_amount, 2),
      'billing_detraction_type_id' => $this->billing_detraction_type_id,
    ];
  }
}
