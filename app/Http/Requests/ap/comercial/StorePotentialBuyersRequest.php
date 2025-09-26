<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StorePotentialBuyersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'registration_date' => [
        'required',
        'date',
      ],
      'model' => [
        'required',
        'string',
        'max:100',
      ],
      'version' => [
        'required',
        'string',
        'max:100',
      ],
      'num_doc' => [
        'required',
        'string',
        'max:20',
      ],
      'name' => [
        'required',
        'string',
        'max:100',
      ],
      'surnames' => [
        'required',
        'string',
        'max:100',
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
    ];
  }

  public function messages(): array
  {
    return [
      'registration_date.required' => 'La fecha de registro es obligatoria.',
      'registration_date.date' => 'La fecha de registro no es una fecha válida.',

      'model.required' => 'El modelo es obligatorio.',
      'model.string' => 'El modelo debe ser una cadena de texto.',
      'model.max' => 'El modelo no debe exceder los 100 caracteres.',

      'version.required' => 'La versión es obligatoria.',
      'version.string' => 'La versión debe ser una cadena de texto.',
      'version.max' => 'La versión no debe exceder los 100 caracteres.',

      'num_doc.required' => 'El número de documento es obligatorio.',
      'num_doc.string' => 'El número de documento debe ser una cadena de texto.',
      'num_doc.max' => 'El número de documento no debe exceder los 20 caracteres.',

      'name.required' => 'El nombre es obligatorio.',
      'name.string' => 'El nombre debe ser una cadena de texto.',
      'name.max' => 'El nombre no debe exceder los 100 caracteres.',

      'surnames.required' => 'Los apellidos son obligatorios.',
      'surnames.string' => 'Los apellidos deben ser una cadena de texto.',
      'surnames.max' => 'Los apellidos no deben exceder los 100 caracteres.',

      'phone.string' => 'El teléfono debe ser una cadena de texto.',
      'phone.max' => 'El teléfono no debe exceder los 20 caracteres.',

      'email.email' => 'El correo electrónico no es válido.',
      'email.max' => 'El correo electrónico no debe exceder los 100 caracteres.',

      'campaign.required' => 'La campaña es obligatoria.',
      'campaign.string' => 'La campaña debe ser una cadena de texto.',
      'campaign.max' => 'La campaña no debe exceder los 100 caracteres.',
    ];
  }
}
