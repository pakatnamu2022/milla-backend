<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class RejectPerDiemRequestRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comments.required' => 'Los comentarios son requeridos para rechazar la solicitud.',
            'comments.string' => 'Los comentarios deben ser texto.',
            'comments.max' => 'Los comentarios no deben exceder 1000 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'comments' => 'Comentarios',
        ];
    }
}
