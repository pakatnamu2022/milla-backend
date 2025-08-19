<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateViewRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('config_vista', 'descripcion')
                    ->where(fn($query) => $query
                        ->where('status_deleted', 1)
                        ->where('parent_id', $this->parent_id)
                        ->where('company_id', $this->company_id))
                    ->ignore($this->route('view')),
            ],
            'submodule' => 'nullable|boolean',
            'route' => 'nullable|string|max:255',
            'ruta' => 'nullable|string|max:255',
            'icono' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|integer|exists:config_vista,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'idPadre' => 'nullable|integer|exists:config_vista,id',
            'idSubPadre' => 'nullable|integer|exists:config_vista,id',
            'idHijo' => 'nullable|integer|exists:config_vista,id',
        ];
    }
}
