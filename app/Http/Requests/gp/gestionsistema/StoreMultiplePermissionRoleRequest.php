<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMultiplePermissionRoleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'role_id' => 'required|exists:config_roles,id',
      'permissions' => 'nullable|array',
      'permissions.*' => 'exists:permission,id',
      'permissions_to_remove' => 'nullable|array',
      'permissions_to_remove.*' => 'exists:permission,id',
    ];
  }

  /**
   * Atributos personalizados para los mensajes de error de validación
   */
  public function attributes(): array
  {
    return [
      'role_id' => 'rol',
      'permissions' => 'permisos',
      'permissions_to_remove' => 'permisos a eliminar',
    ];
  }
}
