<?php

namespace App\Http\Resources\gp\gestionsistema;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'description' => $this->description,
      'module' => $this->module,
      'policy_method' => $this->policy_method,
      'type' => $this->type,
      'type_label' => $this->getTypeLabel(),
      'is_active' => $this->is_active,

      // Relaciones opcionales
//      'roles' => $this->whenLoaded('roles', function () {
//        return $this->roles->map(function ($role) {
//          return [
//            'id' => $role->id,
//            'nombre' => $role->nombre,
//            'granted' => $role->pivot->granted,
//          ];
//        });
//      }),
    ];
  }

  /**
   * Obtener etiqueta legible del tipo
   */
  private function getTypeLabel(): string
  {
    return match ($this->type) {
      'basic' => 'Básico (CRUD)',
      'special' => 'Especial',
      'custom' => 'Personalizado',
      default => $this->type,
    };
  }
}
