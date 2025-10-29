<?php

namespace App\Http\Requests\ap\compras;

use App\Models\ap\comercial\Vehicles;
use App\Models\ap\compras\PurchaseOrder;
use Illuminate\Validation\Rule;

class ResendPurchaseOrderRequest extends StorePurchaseOrderRequest
{
  public function rules(): array
  {
    // Obtener las reglas base del request padre
    $rules = parent::rules();

    // Obtener la OC original desde la ruta
    $originalPOId = $this->route('id');

    // Si hay una OC original, modificar las reglas de validación del VIN y motor
    if ($originalPOId) {
      $originalPO = PurchaseOrder::with('vehicleMovement.vehicle')->find($originalPOId);

      if ($originalPO && $originalPO->vehicleMovement && $originalPO->vehicleMovement->vehicle) {
        $existingVehicle = $originalPO->vehicleMovement->vehicle;

        // Verificar si el request tiene items con vehículo
        $hasVehicle = false;
        if ($this->has('items') && is_array($this->items)) {
          foreach ($this->items as $item) {
            if (isset($item['is_vehicle']) && $item['is_vehicle'] === true) {
              $hasVehicle = true;
              break;
            }
          }
        }

        // Si tiene vehículo, modificar las reglas para ignorar el vehículo existente
        if ($hasVehicle) {
          // Modificar regla del VIN para ignorar el vehículo de la OC original
          $rules['vin'] = [
            'required',
            'string',
            'max:17',
            Rule::unique('ap_vehicles', 'vin')
              ->whereNull('deleted_at')
              ->where('status', 1)
              ->ignore($existingVehicle->id)
          ];

          // Modificar regla del número de motor para ignorar el vehículo de la OC original
          $rules['engine_number'] = [
            'required',
            'string',
            'max:30',
            Rule::unique('ap_vehicles', 'engine_number')
              ->whereNull('deleted_at')
              ->where('status', 1)
              ->ignore($existingVehicle->id)
          ];
        }
      }
    }

    return $rules;
  }

  public function messages()
  {
    return array_merge(parent::messages(), [
      'vin.unique' => 'El VIN ya existe en otro vehículo del sistema (diferente al vehículo de esta orden)',
      'engine_number.unique' => 'El número de motor ya existe en otro vehículo del sistema (diferente al vehículo de esta orden)',
    ]);
  }
}
