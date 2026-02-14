<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdateHotelAgreementRequest extends StoreRequest
{

  public function rules(): array
  {
    return [
      'ruc' => ['sometimes', 'required', 'string', 'max:11'],
      'city' => ['sometimes', 'required', 'string', 'max:255'],
      'name' => ['sometimes', 'required', 'string', 'max:255'],
      'corporate_rate' => ['sometimes', 'required', 'numeric', 'min:0'],
      'features' => ['nullable', 'string'],
      'includes_breakfast' => ['sometimes', 'required', 'boolean'],
      'includes_lunch' => ['nullable', 'boolean'],
      'includes_dinner' => ['nullable', 'boolean'],
      'includes_parking' => ['sometimes', 'required', 'boolean'],
      'contact' => ['nullable', 'string', 'max:255'],
      'address' => ['nullable', 'string', 'max:500'],
      'website' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:50'],
      'email' => ['nullable', 'email', 'max:255'],
      'active' => ['sometimes', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'ruc.max' => 'El RUC no debe exceder 11 caracteres.',
      'city.required' => 'La ciudad es requerida.',
      'city.max' => 'La ciudad no debe exceder 255 caracteres.',
      'name.required' => 'El nombre del hotel es requerido.',
      'name.max' => 'El nombre no debe exceder 255 caracteres.',
      'corporate_rate.required' => 'La tarifa corporativa es requerida.',
      'corporate_rate.numeric' => 'La tarifa corporativa debe ser un número.',
      'corporate_rate.min' => 'La tarifa corporativa debe ser mayor o igual a 0.',
      'includes_breakfast.required' => 'Debe indicar si incluye desayuno.',
      'includes_breakfast.boolean' => 'El campo incluye desayuno debe ser verdadero o falso.',
      'includes_parking.required' => 'Debe indicar si incluye estacionamiento.',
      'includes_parking.boolean' => 'El campo incluye estacionamiento debe ser verdadero o falso.',
      'contact.max' => 'El contacto no debe exceder 255 caracteres.',
      'address.max' => 'La dirección no debe exceder 500 caracteres.',
      'website.string' => 'El sitio web debe ser una cadena de texto.',
      'website.max' => 'El sitio web no debe exceder 255 caracteres.',
      'active.required' => 'El estado es requerido.',
      'active.boolean' => 'El estado debe ser verdadero o falso.',
    ];
  }

  public function attributes(): array
  {
    return [
      'city' => 'Ciudad',
      'name' => 'Nombre del hotel',
      'corporate_rate' => 'Tarifa corporativa',
      'features' => 'Características',
      'includes_breakfast' => 'Incluye desayuno',
      'includes_parking' => 'Incluye estacionamiento',
      'contact' => 'Contacto',
      'address' => 'Dirección',
      'website' => 'Sitio web',
      'active' => 'Estado',
    ];
  }
}
