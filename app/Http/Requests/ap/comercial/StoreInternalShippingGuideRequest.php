<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreInternalShippingGuideRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'sede_receiver_id'     => 'required|integer|exists:config_sede,id',
      'ap_vehicle_id'        => 'required|integer|exists:ap_vehicles,id',
      'transfer_modality_id' => 'nullable|integer|exists:sunat_concepts,id',
      'driver_doc'           => 'nullable|string|max:255',
      'license'              => 'nullable|string|max:255',
      'plate'                => 'nullable|string|max:255',
      'driver_name'          => 'nullable|string|max:255',
      'notes'                => 'nullable|string',
    ];
  }

  public function attributes(): array
  {
    return [
      'sede_receiver_id' => 'sede destinataria',
      'ap_vehicle_id'    => 'vehículo',
    ];
  }
}
