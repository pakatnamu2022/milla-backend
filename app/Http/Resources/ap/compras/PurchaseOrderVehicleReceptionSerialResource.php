<?php

namespace App\Http\Resources\ap\compras;

use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderVehicleReceptionSerialResource extends JsonResource
{
  /**
   * Transform the resource into an array for neInTbRecepcionDtS (Reception Detail Serial/VIN)
   * Mapea datos de PurchaseOrder + Vehicle para el número de serie (VIN) de la recepción
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Obtener el vehículo a través de la relación vehicle_movement_id
    $vehicle = $this->vehicle;

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'RecepcionId' => $this->number_guide,
      'Linea' => 1,
      'Serie' => $vehicle?->vin ?? '',
      'ArticuloId' => $vehicle?->model?->code ?? '',
      'DatoUsuario1' => $vehicle?->vin ?? '',
      'DatoUsuario2' => $vehicle?->vin ?? '',
    ];
  }
}
