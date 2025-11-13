<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdateBusinessPartnersEstablishmentRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'nullable',
        'string',
        'max:20',
      ],
      'description' => [
        'nullable',
        'string',
        'max:255',
      ],
      'type' => [
        'nullable',
        'string',
        'max:100',
      ],
      'activity_economic' => [
        'nullable',
        'string',
        'max:100',
      ],
      'address' => [
        'nullable',
        'string',
        'max:255',
      ],
      'district_id' => [
        'nullable',
        'integer',
        'exists:district,id',
      ],
      'business_partner_id' => [
        'nullable',
        'integer',
        'exists:business_partners,id',
      ],
      'status' => [
        'nullable',
        'boolean',
      ],
      'sede_id' => [
        'nullable',
        'integer',
        'exists:config_sede,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'code.string' => 'El código debe ser texto',
      'code.max' => 'El código no puede exceder 20 caracteres',
      'description.string' => 'La descripción debe ser texto',
      'description.max' => 'La descripción no puede exceder 255 caracteres',
      'type.string' => 'El tipo debe ser texto',
      'type.max' => 'El tipo no puede exceder 100 caracteres',
      'activity_economic.string' => 'La actividad económica debe ser texto',
      'activity_economic.max' => 'La actividad económica no puede exceder 100 caracteres',
      'address.string' => 'La dirección debe ser texto',
      'address.max' => 'La dirección no puede exceder 255 caracteres',
      'district_id.integer' => 'El ID del distrito debe ser un número entero',
      'district_id.exists' => 'El ID del distrito no existe',
      'business_partner_id.integer' => 'El ID del socio de negocios debe ser un número entero',
      'business_partner_id.exists' => 'El ID del socio de negocios no existe',
      'status.boolean' => 'El estado debe ser verdadero o falso',
      'sede_id.integer' => 'El ID de la sede debe ser un número entero',
      'sede_id.exists' => 'El ID de la sede no existe',
    ];
  }
}
