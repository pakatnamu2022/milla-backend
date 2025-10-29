<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiclePurchaseOrderMigrationLogResource extends JsonResource
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
      'step' => $this->step,
      'step_name' => $this->getStepName(),
      'status' => $this->status,
      'status_name' => $this->getStatusName(),
      'table_name' => $this->table_name,
      'external_id' => $this->external_id,
      'proceso_estado' => $this->proceso_estado,
      'proceso_estado_name' => $this->getProcesoEstadoName(),
      'error_message' => $this->error_message,
      'attempts' => $this->attempts,
      'last_attempt_at' => $this->last_attempt_at?->format('Y-m-d H:i:s'),
      'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
      'created_at' => $this->created_at->format('Y-m-d H:i:s'),
      'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
    ];
  }

  /**
   * Get human-readable step name
   */
  protected function getStepName(): string
  {
    return match ($this->step) {
      'supplier' => 'Proveedor',
      'supplier_address' => 'Dirección del Proveedor',
      'article' => 'Artículo',
      'purchase_order' => 'Orden de Compra',
      'purchase_order_detail' => 'Detalle de Orden de Compra',
      'reception' => 'Recepción',
      'reception_detail' => 'Detalle de Recepción',
      'reception_detail_serial' => 'Serial de Recepción',
      default => $this->step,
    };
  }

  /**
   * Get human-readable status name
   */
  protected function getStatusName(): string
  {
    return match ($this->status) {
      'pending' => 'Pendiente',
      'in_progress' => 'En Progreso',
      'completed' => 'Completado',
      'failed' => 'Fallido',
      default => $this->status,
    };
  }

  /**
   * Get human-readable proceso estado name
   */
  protected function getProcesoEstadoName(): ?string
  {
    if ($this->proceso_estado === null) {
      return null;
    }

    return match ($this->proceso_estado) {
      0 => 'Pendiente de Procesar',
      1 => 'Procesado Exitosamente',
      default => "Estado {$this->proceso_estado}",
    };
  }
}
