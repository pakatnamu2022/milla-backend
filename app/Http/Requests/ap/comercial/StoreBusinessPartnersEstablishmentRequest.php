<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreBusinessPartnersEstablishmentRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'required',
        'string',
        'max:20',
      ],
      'description' => [
        'required',
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
        'required',
        'string',
        'max:255',
      ],
      'district_id' => [
        'required',
        'integer',
        'exists:district,id',
      ],
      'business_partner_id' => [
        'required',
        'integer',
        'exists:business_partners,id',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El código es obligatorio',
      'code.string' => 'El código debe ser texto',
      'code.max' => 'El código no puede exceder 20 caracteres',
      'description.required' => 'La descripción es obligatoria',
      'description.string' => 'La descripción debe ser texto',
      'description.max' => 'La descripción no puede exceder 255 caracteres',
      'type.string' => 'El tipo debe ser texto',
      'type.max' => 'El tipo no puede exceder 100 caracteres',
      'activity_economic.string' => 'La actividad económica debe ser texto',
      'activity_economic.max' => 'La actividad económica no puede exceder 100 caracteres',
      'address.required' => 'La dirección es obligatoria',
      'address.string' => 'La dirección debe ser texto',
      'address.max' => 'La dirección no puede exceder 255 caracteres',
      'district_id.required' => 'El distrito es obligatorio',
      'district_id.integer' => 'El distrito debe ser un entero',
      'district_id.exists' => 'El distrito no existe',
      'business_partner_id.required' => 'El socio de negocio es obligatorio',
      'business_partner_id.integer' => 'El socio de negocio debe ser un entero',
      'business_partner_id.exists' => 'El socio de negocio no existe',
    ];
  }
}
