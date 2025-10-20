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
      'permissions' => 'required|array',
      'permissions.*' => 'exists:permission,id',
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
    ];
  }
}
