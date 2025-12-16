<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class MarkAttendedHotelReservationRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attended' => ['required', 'boolean'],
            'penalty' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'attended.required' => 'Debe indicar si asistió o no.',
            'attended.boolean' => 'El campo asistió debe ser verdadero o falso.',
            'penalty.numeric' => 'La penalidad debe ser un número.',
            'penalty.min' => 'La penalidad debe ser mayor o igual a 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'attended' => 'Asistió',
            'penalty' => 'Penalidad',
        ];
    }
}
