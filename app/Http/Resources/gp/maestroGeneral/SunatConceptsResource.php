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
    /**
     * {
     * "id": 11,
     * "code_nubefact": "-",
     * "description": "Sin Documento",
     * "type": "TYPE_DOCUMENT",
     * "prefix": null,
     * "length": null,
     * "tribute_code": null,
     * "affects_total": null,
     * "iso_code": null,
     * "symbol": null,
     * "percentage": null,
     * "status": 1,
     * "created_at": "2025-10-30T19:57:47.000000Z",
     * "updated_at": "2025-10-30T19:57:47.000000Z",
     * "deleted_at": null
     * }
     */
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
    ];
  }
}
