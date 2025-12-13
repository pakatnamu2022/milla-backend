<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class StoreTransferInventoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Transferencia
      'warehouse_origin_id' => 'required|integer|exists:warehouse,id',
      'warehouse_destination_id' => 'required|integer|exists:warehouse,id|different:warehouse_origin_id',
      'movement_date' => 'required|date',
      'notes' => 'nullable|string|max:1000',
      'reason_in_out_id' => 'nullable|integer|exists:ap_post_venta_masters,id',
      'item_type' => 'required|in:PRODUCTO,SERVICIO',

      // Detalles de productos o servicios
      'details' => 'required|array|min:1',
      'details.*.product_id' => 'nullable|integer|exists:products,id|required_without:details.*.description',
      'details.*.description' => 'nullable|string|max:500|required_without:details.*.product_id',
      'details.*.quantity' => 'required|numeric|min:0.01',
      'details.*.unit_cost' => 'nullable|numeric|min:0',
      'details.*.batch_number' => 'nullable|string|max:255',
      'details.*.expiration_date' => 'nullable|date',
      'details.*.notes' => 'nullable|string|max:500',

      // Datos de la Guía de Remisión (requeridos para crear la guía)
      'driver_name' => 'nullable|string|max:255',
      'driver_doc' => 'nullable|string|max:20',
      'license' => 'nullable|string|max:20',
      'plate' => 'nullable|string|max:20',
      'transfer_reason_id' => 'required|integer|exists:sunat_concepts,id',
      'transfer_modality_id' => 'required|integer|exists:sunat_concepts,id',
      'transport_company_id' => 'nullable|integer|exists:business_partners,id',
      'total_packages' => 'nullable|integer|min:1',
      'total_weight' => 'nullable|numeric|min:0',

      // Transmitter and Receiver IDs (will be used to get address and ubigeo data)
      'transmitter_origin_id' => 'required|integer|exists:business_partners,id',
      'receiver_destination_id' => 'required|integer|exists:business_partners,id',
    ];
  }

  public function messages(): array
  {
    return [
      'warehouse_origin_id.required' => 'El almacén de origen es requerido',
      'warehouse_origin_id.exists' => 'El almacén de origen no existe',
      'warehouse_destination_id.required' => 'El almacén de destino es requerido',
      'warehouse_destination_id.exists' => 'El almacén de destino no existe',
      'warehouse_destination_id.different' => 'El almacén de destino debe ser diferente al almacén de origen',
      'movement_date.required' => 'La fecha de movimiento es requerida',
      'movement_date.date' => 'La fecha de movimiento debe ser una fecha válida',
      'notes.string' => 'Las notas deben ser texto',
      'notes.max' => 'Las notas no pueden exceder 1000 caracteres',
      'item_type.required' => 'El tipo de item es requerido',
      'item_type.in' => 'El tipo de item debe ser PRODUCTO o SERVICIO',
      'details.required' => 'Debe proporcionar al menos un producto o servicio',
      'details.array' => 'Los detalles deben ser un array',
      'details.min' => 'Debe proporcionar al menos un producto o servicio',
      'details.*.product_id.required_without' => 'Debe proporcionar un producto o una descripción',
      'details.*.product_id.exists' => 'El producto no existe',
      'details.*.description.required_without' => 'Debe proporcionar una descripción o un producto',
      'details.*.description.max' => 'La descripción no puede exceder 500 caracteres',
      'details.*.quantity.required' => 'La cantidad es requerida',
      'details.*.quantity.numeric' => 'La cantidad debe ser numérica',
      'details.*.quantity.min' => 'La cantidad debe ser mayor a 0',
      'details.*.unit_cost.numeric' => 'El costo unitario debe ser numérico',
      'details.*.unit_cost.min' => 'El costo unitario debe ser mayor o igual a 0',
      'details.*.batch_number.string' => 'El número de lote debe ser texto',
      'details.*.batch_number.max' => 'El número de lote no puede exceder 255 caracteres',
      'details.*.expiration_date.date' => 'La fecha de vencimiento debe ser una fecha válida',
      'details.*.notes.string' => 'Las notas del detalle deben ser texto',
      'details.*.notes.max' => 'Las notas del detalle no pueden exceder 500 caracteres',

      // Guía de remisión
      'transfer_reason_id.required' => 'El motivo de traslado es requerido',
      'transfer_reason_id.exists' => 'El motivo de traslado no es válido',
      'transfer_modality_id.required' => 'La modalidad de traslado es requerida',
      'transfer_modality_id.exists' => 'La modalidad de traslado no es válida',
      'transport_company_id.exists' => 'La empresa de transporte no existe',
      'total_packages.integer' => 'El total de bultos debe ser un número entero',
      'total_packages.min' => 'El total de bultos debe ser mayor a 0',
      'total_weight.numeric' => 'El peso total debe ser numérico',
      'total_weight.min' => 'El peso total debe ser mayor o igual a 0',

      // Transmitter and Receiver
      'transmitter_origin_id.required' => 'El remitente de origen es requerido',
      'transmitter_origin_id.exists' => 'El remitente de origen no existe',
      'receiver_destination_id.required' => 'El destinatario es requerido',
      'receiver_destination_id.exists' => 'El destinatario no existe',
    ];
  }
}
