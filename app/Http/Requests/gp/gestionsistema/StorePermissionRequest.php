<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class StorePermissionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => 'required|string|max:150|unique:permission,code',
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:500',
      'module' => 'required|string|max:100',
      'vista_id' => 'nullable|exists:config_vista,id',
      'policy_method' => 'required|string|max:100',
      'is_active' => 'nullable|boolean',
    ];
  }

  /**
   * Mensajes personalizados de validación
   */
  public function messages(): array
  {
    return [
      'code.required' => 'El código del permiso es requerido',
      'code.unique' => 'Ya existe un permiso con este código',
      'code.max' => 'El código no puede exceder 150 caracteres',
      'name.required' => 'El nombre del permiso es requerido',
      'name.max' => 'El nombre no puede exceder 255 caracteres',
      'description.max' => 'La descripción no puede exceder 500 caracteres',
      'module.required' => 'El módulo es requerido',
      'module.max' => 'El módulo no puede exceder 100 caracteres',
      'vista_id.exists' => 'La vista seleccionada no existe',
      'policy_method.required' => 'El método de policy es requerido',
      'policy_method.max' => 'El método de policy no puede exceder 100 caracteres',
    ];
  }

  /**
   * Atributos personalizados para los mensajes de error
   */
  public function attributes(): array
  {
    return [
      'code' => 'código',
      'name' => 'nombre',
      'description' => 'descripción',
      'module' => 'módulo',
      'vista_id' => 'vista',
      'policy_method' => 'método de policy',
      'is_active' => 'activo',
    ];
  }

  /**
   * Preparar datos para validación
   */
  protected function prepareForValidation(): void
  {
    // Establecer valores por defecto
    if (!$this->has('is_active')) {
      $this->merge(['is_active' => true]);
    }

    // Generar policy_method a partir del code si no viene
    if (!$this->has('policy_method') && $this->has('code')) {
      $parts = explode('.', $this->code);
      $action = end($parts);
      $this->merge(['policy_method' => $action]);
    }
  }
}
