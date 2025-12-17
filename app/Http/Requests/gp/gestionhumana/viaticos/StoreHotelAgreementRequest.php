<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class StoreHotelAgreementRequest extends StoreRequest
{

  public function rules(): array
  {
    return [
      'city' => ['required', 'string', 'max:255'],
      'name' => ['required', 'string', 'max:255'],
      'corporate_rate' => ['required', 'numeric', 'min:0'],
      'features' => ['nullable', 'string'],
      'includes_breakfast' => ['required', 'boolean'],
      'includes_lunch' => ['nullable', 'boolean'],
      'includes_dinner' => ['nullable', 'boolean'],
      'includes_parking' => ['required', 'boolean'],
      'contact' => ['nullable', 'string', 'max:255'],
      'address' => ['nullable', 'string', 'max:500'],
      'phone' => ['nullable', 'string', 'max:50'],
      'email' => ['nullable', 'email', 'max:255'],
      'website' => ['nullable', 'url', 'max:255'],
    ];
  }

  public function messages(): array
  {
    return [
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
      'website.url' => 'El sitio web debe ser una URL válida.',
      'website.max' => 'El sitio web no debe exceder 255 caracteres.',
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
    ];
  }
}
