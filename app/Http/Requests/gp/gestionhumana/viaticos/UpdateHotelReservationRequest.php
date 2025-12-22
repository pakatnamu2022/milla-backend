<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdateHotelReservationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'hotel_agreement_id' => ['sometimes', 'nullable', 'integer', 'exists:gh_hotel_agreement,id'],
      'hotel_name' => ['sometimes', 'required', 'string', 'max:255'],
      'address' => ['sometimes', 'required', 'string', 'max:500'],
      'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
      'checkin_date' => ['sometimes', 'required', 'date'],
      'checkout_date' => ['sometimes', 'required', 'date', 'after:checkin_date'],
      'total_cost' => ['sometimes', 'required', 'numeric', 'min:0'],
      'receipt_file' => ['sometimes', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
      'notes' => ['sometimes', 'nullable', 'string'],
      'force_update' => ['sometimes', 'boolean'],
    ];
  }

  /**
   * Get custom messages for validator errors.
   *
   * @return array<string, string>
   */
  public function messages(): array
  {
    return [
      'hotel_name.required' => 'El nombre del hotel es requerido.',
      'address.required' => 'La dirección del hotel es requerida.',
      'checkin_date.required' => 'La fecha de check-in es requerida.',
      'checkout_date.required' => 'La fecha de check-out es requerida.',
      'checkout_date.after' => 'La fecha de check-out debe ser posterior a la fecha de check-in.',
      'total_cost.required' => 'El costo total es requerido.',
      'total_cost.min' => 'El costo total debe ser mayor o igual a 0.',
    ];
  }
}
