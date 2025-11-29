<?php

namespace App\Http\Resources\ap\comercial;

use App\Http\Resources\ap\configuracionComercial\vehiculo\ApFamiliesResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApModelsVnResource;
use App\Http\Resources\ap\configuracionComercial\vehiculo\ApVehicleBrandResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiclesResource extends JsonResource
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
      'vin' => $this->vin,
      'year' => $this->year,
      'engine_number' => $this->engine_number,
      'ap_models_vn_id' => $this->ap_models_vn_id,
      'vehicle_color_id' => $this->vehicle_color_id,
      'engine_type_id' => $this->engine_type_id,
      'ap_vehicle_status_id' => $this->ap_vehicle_status_id,
      'vehicle_color' => $this->color->description,
      'engine_type' => $this->engineType->description,
      'status' => $this->status,
      'vehicle_status' => $this->vehicleStatus->description,
      'status_color' => $this->vehicleStatus->color,
      'warehouse_id' => $this->warehouse_id ?? null,
      'warehouse_name' => $this->warehouse?->description ?? null,
      'warehouse_physical_id' => $this->warehouse_physical_id ?? null,
      'warehouse_physical_name' => $this->warehousePhysical?->description ?? null,
      'sede_name_warehouse_physical' => $this->warehousePhysical?->sede?->abreviatura,
      'sede_name_warehouse' => $this->warehouse?->sede?->abreviatura ?? null,
      'model' => ApModelsVnResource::make($this->model),
      'movements' => VehicleMovementResource::collection($this->vehicleMovements),
      'owner' => $this->getOwnerData(),
    ];
  }

  /**
   * Obtiene la información del propietario del vehículo
   * Incluye datos de orden de compra, estado de pago y cliente
   */
  protected function getOwnerData(): ?array
  {
    $purchaseRequestQuote = $this->purchaseRequestQuote;

    // Si no tiene orden de compra, retornar estructura básica
    if (!$purchaseRequestQuote) {
      return [
        'has_purchase_order' => false,
        'is_cancelled' => false,
        'is_paid' => false,
        'client' => null,
      ];
    }

    // Verificar si está cancelado (status = 0)
    $isCancelled = $purchaseRequestQuote->status == 0;

    // Verificar si está pagado usando el método del modelo
    $isPaid = $this->is_paid;

    // Obtener datos del cliente si está pagado
    $clientData = null;
    if ($isPaid) {
      try {
        $data = \App\Models\ap\comercial\Vehicles::getElectronicDocumentWithClient($this->id);
        $client = $data->client;

        if ($client) {
          $clientData = [
            'id' => $client->id,
            'num_doc' => $client->num_doc,
            'full_name' => $client->full_name,
            'direction' => $client->direction,
            'email' => $client->email,
            'phone' => $client->phone ?? null,
          ];
        }
      } catch (\Exception $e) {
        // Si hay error al obtener el cliente, dejar como null
        $clientData = null;
      }
    }

    return [
      'has_purchase_order' => true,
      'is_cancelled' => $isCancelled,
      'is_paid' => $isPaid,
      'client' => $clientData,
    ];
  }
}
