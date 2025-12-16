<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseTypeRequest extends StoreRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('expense_type');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:gh_expense_type,id'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('gh_expense_type', 'code')->ignore($id)
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'requires_receipt' => ['sometimes', 'required', 'boolean'],
            'active' => ['sometimes', 'required', 'boolean'],
            'order' => ['sometimes', 'required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'El tipo de gasto padre seleccionado no existe.',
            'code.required' => 'El código es requerido.',
            'code.unique' => 'El código ya está en uso.',
            'code.max' => 'El código no debe exceder 50 caracteres.',
            'name.required' => 'El nombre es requerido.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'requires_receipt.required' => 'Debe indicar si requiere comprobante.',
            'requires_receipt.boolean' => 'El campo requiere comprobante debe ser verdadero o falso.',
            'active.required' => 'El estado es requerido.',
            'active.boolean' => 'El estado debe ser verdadero o falso.',
            'order.required' => 'El orden es requerido.',
            'order.integer' => 'El orden debe ser un número entero.',
            'order.min' => 'El orden debe ser al menos 1.',
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id' => 'Tipo de gasto padre',
            'code' => 'Código',
            'name' => 'Nombre',
            'description' => 'Descripción',
            'requires_receipt' => 'Requiere comprobante',
            'active' => 'Estado',
            'order' => 'Orden',
        ];
    }
}
