<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class StorePerDiemPolicyRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'version' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'is_current' => 'required|boolean',
            'document' => 'required|file|mimes:pdf|max:10240', // Max 10MB
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'version.required' => 'La versión es requerida.',
            'version.max' => 'La versión no puede exceder 20 caracteres.',
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'effective_from.required' => 'La fecha de inicio de vigencia es requerida.',
            'effective_from.date' => 'La fecha de inicio debe ser una fecha válida.',
            'effective_to.date' => 'La fecha de fin debe ser una fecha válida.',
            'effective_to.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'is_current.required' => 'Debe indicar si es la política actual.',
            'is_current.boolean' => 'El campo política actual debe ser verdadero o falso.',
            'document.required' => 'El documento PDF es requerido.',
            'document.file' => 'Debe subir un archivo válido.',
            'document.mimes' => 'El documento debe ser un archivo PDF.',
            'document.max' => 'El documento no puede exceder 10MB.',
        ];
    }
}
