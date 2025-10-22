<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends StoreRequest
{
  public function rules(): array
  {
    $permissionId = $this->route('permission');

    return [
      'code' => [
        'sometimes',
        'string',
        'max:255',
        Rule::unique('permission', 'code')->whereNull('deleted_at')->ignore($permissionId),
      ],
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
