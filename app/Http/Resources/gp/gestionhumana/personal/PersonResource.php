<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
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
      'name' => $this->nombre_completo,
      'document' => $this->vat,
      'sede' => $this->sede?->abreviatura,
      'position' => $this->position?->name,
      'hierarchical_category' => $this->position?->hierarchicalCategory?->name,
      'offerLetterConfirmationId' => $this->status_carta_oferta_id,
      'emailOfferLetterStatusId' => $this->status_envio_mail_carta_oferta,
      'offerLetterConfirmation' => $this->offerLetterStatus?->estado,
      'emailOfferLetterStatus' => $this->emailOfferLetterStatus?->estado,
    ];
  }
}
