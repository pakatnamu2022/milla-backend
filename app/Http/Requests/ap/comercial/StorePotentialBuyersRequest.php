<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StorePotentialBuyersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'registration_date' => [
        'sometimes',
        'date',
      ],
      'model' => [
        'sometimes',
        'string',
        'max:100',
      ],
      'version' => [
        'sometimes',
        'string',
        'max:100',
      ],
      'num_doc' => [
        'required',
        'string',
        'max:20',
      ],
      'full_name' => [
        'required',
        'string',
        'max:255',
      ],
      'phone' => [
        'nullable',
        'string',
        'max:20',
      ],
      'email' => [
        'nullable',
        'email:rfc,dns',
        'max:100',
      ],
      'campaign' => [
        'required',
        'string',
        'max:100',
      ],
      'worker_id' => [
        'required',
        'integer',
        'exists:rrhh_persona,id',
      ],
      'sede_id' => [
        'required',
        'integer',
        'exists:config_sede,id',
      ],
      'vehicle_brand_id' => [
        'required',
        'integer',
        'exists:ap_vehicle_brand,id',
      ],
      'document_type_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
      'income_sector_id' => [
        'required',
        'integer',
        'exists:ap_masters,id',
      ],
      'type' => 'required|string|max:50',
      'area_id' => 'required|integer|exists:ap_masters,id',
    ];
  }

  public function messages(): array
  {
    return [
      'registration_date.date' => 'La fecha de registro no es una fecha válida.',

      'model.string' => 'El modelo debe ser una cadena de texto.',
      'model.max' => 'El modelo no debe exceder los 100 caracteres.',

      'version.string' => 'La versión debe ser una cadena de texto.',
      'version.max' => 'La versión no debe exceder los 100 caracteres.',

      'num_doc.required' => 'El número de documento es obligatorio.',
      'num_doc.string' => 'El número de documento debe ser una cadena de texto.',
      'num_doc.max' => 'El número de documento no debe exceder los 20 caracteres.',

      'full_name.required' => 'El nombre es obligatorio.',
      'full_name.string' => 'El nombre debe ser una cadena de texto.',
      'full_name.max' => 'El nombre no debe exceder los 255 caracteres.',

      'phone.string' => 'El teléfono debe ser una cadena de texto.',
      'phone.max' => 'El teléfono no debe exceder los 20 caracteres.',

      'email.email' => 'El correo electrónico no es válido.',
      'email.max' => 'El correo electrónico no debe exceder los 100 caracteres.',

      'campaign.required' => 'La campaña es obligatoria.',
      'campaign.string' => 'La campaña debe ser una cadena de texto.',
      'campaign.max' => 'La campaña no debe exceder los 100 caracteres.',

      'worker_id.required' => 'El asesor es obligatorio.',
      'worker_id.integer' => 'El asesor debe ser un número entero.',
      'worker_id.exists' => 'El asesor seleccionado no existe.',

      'sede_id.required' => 'La sede es obligatoria.',
      'sede_id.integer' => 'La sede debe ser un número entero.',
      'sede_id.exists' => 'La sede seleccionada no existe.',

      'vehicle_brand_id.required' => 'La marca del vehículo es obligatoria.',
      'vehicle_brand_id.integer' => 'La marca del vehículo debe ser un número entero.',
      'vehicle_brand_id.exists' => 'La marca del vehículo seleccionada no existe.',

      'document_type_id.required' => 'El tipo de documento es obligatorio.',
      'document_type_id.integer' => 'El tipo de documento debe ser un número entero.',
      'document_type_id.exists' => 'El tipo de documento seleccionado no existe.',

      'income_sector_id.required' => 'El sector de ingresos es obligatorio.',
      'income_sector_id.integer' => 'El sector de ingresos debe ser un número entero.',
      'income_sector_id.exists' => 'El sector de ingresos seleccionado no existe.',

      'type.required' => 'El tipo es obligatorio.',
      'type.string' => 'El tipo debe ser una cadena de texto.',
      'type.max' => 'El tipo no debe exceder los 50 caracteres.',

      'area_id.required' => 'El área es obligatoria.',
      'area_id.integer' => 'El área debe ser un número entero.',
      'area_id.exists' => 'El área seleccionada no existe.',
    ];
  }
}
