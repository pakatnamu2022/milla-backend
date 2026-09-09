<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:op_supplier,name',
            'ruc' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del grifo es obligatorio',
            'name.unique' => 'Ya existe un grifo con este nombre',
        ];
    }
}
