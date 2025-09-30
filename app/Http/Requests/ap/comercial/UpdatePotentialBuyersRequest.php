<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class UpdatePotentialBuyersRequest extends StoreRequest
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
        'sometimes',
        'string',
        'max:20',
      ],
      'full_name' => [
        'sometimes',
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
        'sometimes',
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
        'sometimes',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'income_sector_id' => [
        'sometimes',
        'integer',
        'exists:ap_commercial_masters,id',
      ],
      'type' => 'sometimes|string|max:50',
      'area_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
    ];
  }

  public function messages(): array
  {
    return [
      'registration_date.date' => 'La fecha de registro no es una fecha válida',

      'model.string' => 'El modelo debe ser una cadena de texto',
      'model.max' => 'El modelo no debe ser mayor a 100 caracteres',

      'version.string' => 'La versión debe ser una cadena de texto',
      'version.max' => 'La versión no debe ser mayor a 100 caracteres',

      'num_doc.string' => 'El número de documento debe ser una cadena de texto',
      'num_doc.max' => 'El número de documento no debe ser mayor a 20 caracteres',

      'full_name.string' => 'El nombre completo debe ser una cadena de texto',
      'full_name.max' => 'El nombre completo no debe ser mayor a 255 caracteres',

      'phone.string' => 'El teléfono debe ser una cadena de texto',
      'phone.max' => 'El teléfono no debe ser mayor a 20 caracteres',

      'email.email' => 'El correo electrónico no es válido',
      'email.max' => 'El correo electrónico no debe ser mayor a 100 caracteres',

      'campaign.string' => 'La campaña debe ser una cadena de texto',
      'campaign.max' => 'La campaña no debe ser mayor a 100 caracteres',

      'worker_id.required' => 'El asesor es obligatorio.',
      'worker_id.integer' => 'El asesor debe ser un número entero.',
      'worker_id.exists' => 'El asesor seleccionado no existe.',

      'sede_id.required' => 'El ID de sede es obligatorio',
      'sede_id.integer' => 'El ID de sede debe ser un número entero',
      'sede_id.exists' => 'El ID de sede no existe',

      'vehicle_brand_id.required' => 'El ID de marca del vehículo es obligatorio',
      'vehicle_brand_id.integer' => 'El ID de marca del vehículo debe ser un número entero',
      'vehicle_brand_id.exists' => 'El ID de marca del vehículo no existe',

      'document_type_id.integer' => 'El ID de tipo de documento debe ser un número entero',
      'document_type_id.exists' => 'El ID de tipo de documento no existe',

      'income_sector_id.integer' => 'El ID de sector de ingresos debe ser un número entero',
      'income_sector_id.exists' => 'El ID de sector de ingresos no existe',

      'type.string' => 'El tipo debe ser una cadena de texto',
      'type.max' => 'El tipo no debe ser mayor a 50 caracteres',

      'area_id.integer' => 'El ID de área debe ser un número entero',
      'area_id.exists' => 'El ID de área no existe',
    ];
  }

}
