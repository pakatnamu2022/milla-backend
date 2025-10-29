<?php

namespace App\Http\Resources\ap\compras;

use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderVehicleReceptionDetailResource extends JsonResource
{
  /**
   * Transform the resource into an array for neInTbRecepcionDt (Reception Detail/Items)
   * Mapea datos de PurchaseOrder + Vehicle para el detalle de items de la recepción
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    // Obtener el vehículo a través de la relación vehicle_movement_id
    $vehicle = $this->vehicle;
    $warehouse = Warehouse::find($this->warehouse_id);

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'RecepcionId' => $this->number_guide,
      'Linea' => 1,
      'OrdenCompraId' => $this->number,
      'LineaOC' => 1,
      'ArticuloId' => $vehicle?->model?->code ?? '',
      'SitioId' => $warehouse?->dyn_code ?? '',
      'UnidadMedidaId' => 'UND',
      'Cantidad' => 1,
    ];
  }
}
