<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreHistoricalShippingGuideRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'vin'                 => 'required|string|exists:ap_vehicles,vin',
      'series'              => 'required|string|max:10',
      'correlativo'         => 'required|string|max:20',
      'issue_date'          => 'required|date|before_or_equal:today',
      'sede_transmitter_id' => 'required|integer|exists:config_sede,id',
      'advisor_id'          => 'required|integer|exists:rrhh_persona,id',
      'client_id'           => 'required|integer|exists:business_partners,id',
      'notes'               => 'nullable|string|max:500',
    ];
  }

  public function attributes(): array
  {
    return [
      'vin'                 => 'VIN del vehículo',
      'series'              => 'serie de la guía',
      'correlativo'         => 'correlativo de la guía',
      'issue_date'          => 'fecha de emisión',
      'sede_transmitter_id' => 'sede de origen',
      'advisor_id'          => 'asesor de entrega',
      'client_id'           => 'cliente',
    ];
  }
}
