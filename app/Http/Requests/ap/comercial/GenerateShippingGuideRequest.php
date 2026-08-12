<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\gp\maestroGeneral\SunatConcepts;

class GenerateShippingGuideRequest extends StoreRequest
{
  public function rules(): array
  {
    $isPrivate = (int)$this->input('transfer_modality_id') === SunatConcepts::TYPE_TRANSPORTATION_PRIVADO;
    $isPublic  = (int)$this->input('transfer_modality_id') === SunatConcepts::TYPE_TRANSPORTATION_PUBLICO;

    return [
      'transfer_modality_id' => [
        'required',
        'integer',
        'in:' . SunatConcepts::TYPE_TRANSPORTATION_PUBLICO . ',' . SunatConcepts::TYPE_TRANSPORTATION_PRIVADO,
        'exists:sunat_concepts,id',
      ],

      // Transporte privado
      'driver_doc'  => [
        $isPrivate ? 'required' : 'nullable',
        'string',
        'min_digits:8',
        'max_digits:11',
      ],
      'driver_name' => [
        $isPrivate ? 'required' : 'nullable',
        'string',
        'max:100',
      ],
      'license' => [
        'nullable',
        'string',
        'max:20',
      ],
      'plate' => [
        $isPrivate ? 'required' : 'nullable',
        'string',
        'max:20',
      ],

      // Transporte público
      'carrier_ruc' => [
        $isPublic ? 'required' : 'nullable',
        'digits:11',
      ],
      'company_name_transport' => [
        $isPublic ? 'required' : 'nullable',
        'string',
        'max:100',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'transfer_modality_id.required' => 'La modalidad de traslado es obligatoria.',
      'transfer_modality_id.integer'  => 'La modalidad de traslado debe ser un número entero.',
      'transfer_modality_id.in'       => 'La modalidad de traslado debe ser transporte público o privado.',
      'transfer_modality_id.exists'   => 'La modalidad de traslado no existe.',

      'driver_doc.required'    => 'El número de documento del conductor es obligatorio para transporte privado.',
      'driver_doc.min_digits'  => 'El número de documento del conductor debe tener al menos 8 dígitos.',
      'driver_doc.max_digits'  => 'El número de documento del conductor no debe superar los 11 dígitos.',

      'driver_name.required' => 'El nombre del conductor es obligatorio para transporte privado.',
      'driver_name.max'      => 'El nombre del conductor no debe superar los 100 caracteres.',

      'license.max' => 'La licencia no debe superar los 20 caracteres.',

      'plate.required' => 'La placa del vehículo es obligatoria para transporte privado.',
      'plate.max'      => 'La placa no debe superar los 20 caracteres.',

      'carrier_ruc.required' => 'El RUC del transportista es obligatorio para transporte público.',
      'carrier_ruc.digits'   => 'El RUC del transportista debe tener exactamente 11 dígitos.',

      'company_name_transport.required' => 'La razón social del transportista es obligatoria para transporte público.',
      'company_name_transport.max'      => 'La razón social del transportista no debe superar los 100 caracteres.',
    ];
  }
}
