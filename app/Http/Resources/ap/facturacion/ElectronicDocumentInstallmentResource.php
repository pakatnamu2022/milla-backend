<?php

namespace App\Http\Resources\ap\facturacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElectronicDocumentInstallmentResource extends JsonResource
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
      'ap_billing_electronic_document_id' => $this->ap_billing_electronic_document_id,
      'cuota' => $this->cuota,
      'fecha_de_pago' => $this->fecha_de_pago?->format('Y-m-d'),
      'importe' => $this->importe,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
