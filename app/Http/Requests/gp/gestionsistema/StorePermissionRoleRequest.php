<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRoleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'role_id' => 'required|exists:config_roles,id',
      'permission_id' => 'required|exists:permission,id',
      'granted' => 'boolean',
    ];
  }

  /**
   * Atributos personalizados para los mensajes de error de validación
   */
  public function attributes(): array
  {
    return [
      'role_id' => 'rol',
      'permission_id' => 'permiso',
      'granted' => 'concedido',
    ];
  }
}
