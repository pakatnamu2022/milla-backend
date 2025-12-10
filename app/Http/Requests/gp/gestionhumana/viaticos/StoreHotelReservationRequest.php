<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use Illuminate\Foundation\Http\FormRequest;

class StoreHotelReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
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
            'receipt_path' => ['nullable', 'string', 'max:500'],
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
}
