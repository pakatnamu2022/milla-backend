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
    // Extraer la acción del código (ej: "users.view" -> "view")
    $action = $this->action ?? $this->getActionFromCode();

    // Obtener el label desde el config
    $actionConfig = config("permissions.actions.{$action}", []);
    $actionLabel = $actionConfig['label'] ?? null;

    return [
      'id' => $this->id,
      'code' => $this->code,
      'name' => $this->name,
      'description' => $this->description,
      'module' => $this->module,
      'policy_method' => $this->policy_method,
      'is_active' => $this->is_active,
      'action' => $action,
      'action_label' => $actionLabel,
    ];
  }

  /**
   * Extraer la acción del código del permiso
   */
  private function getActionFromCode(): ?string
  {
    if (!$this->code) {
      return null;
    }

    $parts = explode('.', $this->code);
    return end($parts);
  }
}
