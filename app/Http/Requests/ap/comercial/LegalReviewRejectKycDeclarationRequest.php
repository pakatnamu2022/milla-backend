<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class LegalReviewRejectKycDeclarationRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'comments' => 'required|string|min:10|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'comments.required' => 'El motivo del rechazo es obligatorio.',
            'comments.string'   => 'El motivo del rechazo debe ser un texto.',
            'comments.min'      => 'El motivo del rechazo debe tener al menos 10 caracteres.',
            'comments.max'      => 'El motivo del rechazo no puede superar los 1000 caracteres.',
        ];
    }
}

