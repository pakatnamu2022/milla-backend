<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class LinkEquipmentToPhoneLineWorkerRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'equipo_id' => 'nullable|integer|exists:help_equipos,id',
    ];
  }
}
