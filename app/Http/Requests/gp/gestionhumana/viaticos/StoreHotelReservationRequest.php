<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class StoreHotelReservationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'hotel_agreement_id' => ['nullable', 'integer', 'exists:gh_hotel_agreement,id'],
      'hotel_name' => ['required', 'string', 'max:255'],
      'address' => ['required', 'string', 'max:500'],
      'phone' => ['nullable', 'string', 'max:50'],
      'checkin_date' => ['required', 'date'],
      'checkout_date' => ['required', 'date', 'after:checkin_date'],
      'total_cost' => ['required', 'numeric', 'min:0'],
      'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
      'notes' => ['nullable', 'string'],
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

  /**
   * Get the validated data with additional computed fields
   */
  public function validated($key = null, $default = null)
  {
    $data = parent::validated($key, $default);

    // Calculate nights count from checkin and checkout dates
    if (isset($data['checkin_date']) && isset($data['checkout_date'])) {
      $checkinDate = new \DateTime($data['checkin_date']);
      $checkoutDate = new \DateTime($data['checkout_date']);
      $interval = $checkinDate->diff($checkoutDate);
      $data['nights_count'] = $interval->days;
    }

    return $data;
  }
}
