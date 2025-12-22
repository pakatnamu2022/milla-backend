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
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'penalty' => ['nullable', 'numeric', 'min:0'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'attended.required' => 'Debe indicar si asistió o no.',
            'attended.boolean' => 'El campo asistió debe ser verdadero o falso.',
            'total_cost.numeric' => 'El costo total debe ser un número.',
            'total_cost.min' => 'El costo total debe ser mayor o igual a 0.',
            'penalty.numeric' => 'La penalidad debe ser un número.',
            'penalty.min' => 'La penalidad debe ser mayor o igual a 0.',
            'receipt_file.file' => 'El comprobante debe ser un archivo.',
            'receipt_file.mimes' => 'El comprobante debe ser un archivo PDF, JPG, JPEG o PNG.',
            'receipt_file.max' => 'El comprobante no debe superar los 10MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'attended' => 'Asistió',
            'total_cost' => 'Costo total',
            'penalty' => 'Penalidad',
            'receipt_file' => 'Comprobante',
            'notes' => 'Notas',
        ];
    }
}
