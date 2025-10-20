<?php

namespace App\Http\Requests\gp\gestionsistema;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
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
        $permissionId = $this->route('id');

        return [
            'code' => 'sometimes|required|string|max:255|unique:permission,code,' . $permissionId,
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'sometimes|required|string|max:255',
            'policy_method' => 'nullable|string|max:255',
            'type' => 'sometimes|required|in:basic,special,custom',
            'is_active' => 'nullable|boolean',
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El código del permiso es obligatorio',
            'code.unique' => 'Ya existe un permiso con este código',
            'name.required' => 'El nombre del permiso es obligatorio',
            'module.required' => 'El módulo es obligatorio',
            'type.required' => 'El tipo de permiso es obligatorio',
            'type.in' => 'El tipo debe ser: basic, special o custom',
        ];
    }
}
