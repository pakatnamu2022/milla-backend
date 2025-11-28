<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\ShippingGuides;
use App\Models\ap\postventa\gestionProductos\InventoryMovement;
use App\Models\ap\postventa\gestionProductos\TransferReception;

class StoreTransferReceptionRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Reception data
      'transfer_movement_id' => 'required|integer|exists:inventory_movements,id',
      'warehouse_id' => 'required|integer|exists:warehouse,id',
      'reception_date' => 'required|date',
      'notes' => 'nullable|string|max:1000',

      // Reception details
      'details' => 'required|array|min:1',
      'details.*.product_id' => 'required|integer|exists:products,id',
      'details.*.quantity_sent' => 'required|numeric|min:0',
      'details.*.quantity_received' => 'required|numeric|min:0',
      'details.*.observed_quantity' => 'nullable|numeric|min:0',
      'details.*.reason_observation' => 'nullable|string|in:DAMAGED,MISSING,EXPIRED,DEFECTIVE,OTHER',
      'details.*.observation_notes' => 'nullable|string|max:500',
    ];
  }

  public function messages(): array
  {
    return [
      'transfer_movement_id.required' => 'El movimiento de transferencia es requerido',
      'transfer_movement_id.exists' => 'El movimiento de transferencia no existe',
      'warehouse_id.required' => 'El almacén es requerido',
      'warehouse_id.exists' => 'El almacén no existe',
      'reception_date.required' => 'La fecha de recepción es requerida',
      'reception_date.date' => 'La fecha de recepción debe ser una fecha válida',
      'notes.string' => 'Las notas deben ser texto',
      'notes.max' => 'Las notas no pueden exceder 1000 caracteres',
      'details.required' => 'Debe proporcionar al menos un producto',
      'details.array' => 'Los detalles deben ser un array',
      'details.min' => 'Debe proporcionar al menos un producto',
      'details.*.product_id.required' => 'El producto es requerido',
      'details.*.product_id.exists' => 'El producto no existe',
      'details.*.quantity_sent.required' => 'La cantidad enviada es requerida',
      'details.*.quantity_sent.numeric' => 'La cantidad enviada debe ser numérica',
      'details.*.quantity_sent.min' => 'La cantidad enviada debe ser mayor o igual a 0',
      'details.*.quantity_received.required' => 'La cantidad recibida es requerida',
      'details.*.quantity_received.numeric' => 'La cantidad recibida debe ser numérica',
      'details.*.quantity_received.min' => 'La cantidad recibida debe ser mayor o igual a 0',
      'details.*.observed_quantity.numeric' => 'La cantidad observada debe ser numérica',
      'details.*.observed_quantity.min' => 'La cantidad observada debe ser mayor o igual a 0',
      'details.*.reason_observation.in' => 'El motivo de observación no es válido',
      'details.*.observation_notes.string' => 'Las notas de observación deben ser texto',
      'details.*.observation_notes.max' => 'Las notas de observación no pueden exceder 500 caracteres',
    ];
  }

  /**
   * Configure the validator instance
   */
  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      // Validate that the transfer movement is a TRANSFER_OUT
      $transferMovementId = $this->input('transfer_movement_id');
      $movement = InventoryMovement::find($transferMovementId);

      if ($movement) {
        // Check if it's a TRANSFER_OUT movement
        if ($movement->movement_type !== InventoryMovement::TYPE_TRANSFER_OUT) {
          $validator->errors()->add('transfer_movement_id', 'El movimiento debe ser de tipo TRANSFERENCIA SALIDA');
        }

        // Check if the shipping guide exists
        if ($movement->reference_type !== ShippingGuides::class) {
          $validator->errors()->add('transfer_movement_id', 'El movimiento no tiene una guía de remisión asociada');
        } else {
          // Check if the shipping guide has been sent to SUNAT
          $shippingGuide = $movement->reference;
          if (!$shippingGuide || !$shippingGuide->is_sunat_registered) {
            $validator->errors()->add('transfer_movement_id', 'No se puede recepcionar: la guía de remisión aún no ha sido enviada a SUNAT');
          }
        }

        // Validate that warehouse_id matches destination warehouse
        $warehouseId = (int)$this->input('warehouse_id');
        if ($warehouseId && $movement->warehouse_destination_id !== $warehouseId) {
          $validator->errors()->add('warehouse_id', 'El almacén debe ser el almacén de destino de la transferencia');
        }

        // Check if already has an approved reception
        $existingApprovedReception = TransferReception::where('transfer_movement_id', $movement->id)
          ->where('status', TransferReception::STATUS_APPROVED)
          ->first();
        if ($existingApprovedReception) {
          $validator->errors()->add('transfer_movement_id', 'Esta transferencia ya tiene una recepción aprobada');
        }
      }

      // Validate that quantity_received + observed_quantity = quantity_sent for each detail
      $details = $this->input('details', []);
      foreach ($details as $index => $detail) {
        $quantitySent = $detail['quantity_sent'] ?? 0;
        $quantityReceived = $detail['quantity_received'] ?? 0;
        $observedQuantity = $detail['observed_quantity'] ?? 0;

        if (($quantityReceived + $observedQuantity) != $quantitySent) {
          $validator->errors()->add(
            "details.{$index}.quantity_received",
            'La cantidad recibida + cantidad observada debe ser igual a la cantidad enviada'
          );
        }

        // If there's observed quantity, reason is required
        if ($observedQuantity > 0 && empty($detail['reason_observation'])) {
          $validator->errors()->add(
            "details.{$index}.reason_observation",
            'Debe especificar un motivo de observación cuando hay cantidad observada'
          );
        }
      }
    });
  }
}
