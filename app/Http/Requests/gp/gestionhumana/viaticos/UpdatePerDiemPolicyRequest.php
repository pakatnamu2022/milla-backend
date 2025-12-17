<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class UpdatePerDiemPolicyRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'version' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_current' => 'required|boolean',
            'document_path' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'version.required' => 'La versión es obligatoria.',
            'version.string' => 'La versión debe ser una cadena de texto.',
            'version.max' => 'La versión no debe exceder los 50 caracteres.',
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'effective_from.required' => 'La fecha de vigencia desde es obligatoria.',
            'effective_from.date' => 'La fecha de vigencia desde debe ser una fecha válida.',
            'effective_to.date' => 'La fecha de vigencia hasta debe ser una fecha válida.',
            'effective_to.after_or_equal' => 'La fecha de vigencia hasta debe ser igual o posterior a la fecha de vigencia desde.',
            'is_current.required' => 'El campo de vigente es obligatorio.',
            'is_current.boolean' => 'El campo de vigente debe ser verdadero o falso.',
            'document_path.string' => 'La ruta del documento debe ser una cadena de texto.',
            'document_path.max' => 'La ruta del documento no debe exceder los 255 caracteres.',
            'notes.string' => 'Las notas deben ser una cadena de texto.',
        ];
    }
}
