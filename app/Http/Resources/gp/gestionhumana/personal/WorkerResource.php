<?php

namespace App\Http\Resources\gp\gestionhumana\personal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sede' => $this->sede->abreviatura,
            'name' => $this->nombre_completo,
            'document' => $this->vat,
            'position' => $this->position->name,
            'offerLetterConfirmation' => $this->offerLetterStatus?->estado,
            'emailStatus' => $this->emailDeliveryOfferLetterStatus?->estado,

        ];
    }
}
