<?php

namespace App\Http\Resources\ap\facturacion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ElectronicDocumentGuideResource extends JsonResource
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
      'guia_tipo' => $this->guia_tipo,
      'guia_tipo_descripcion' => $this->guia_tipo == 1 ? 'GR Remitente' : 'GR Transportista',
      'guia_serie_numero' => $this->guia_serie_numero,
      'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
    ];
  }
}
