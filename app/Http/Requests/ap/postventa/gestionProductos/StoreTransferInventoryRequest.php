<?php

namespace App\Http\Requests\ap\postventa\gestionProductos;

use App\Http\Requests\StoreRequest;

class StoreTransferInventoryRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      // Transferencia
      'transmitter_id' => 'required|integer|exists:business_partners_establishment,id',
      'receiver_id' => 'required|integer|exists:business_partners_establishment,id|different:transmitter_id',
      'movement_date' => 'required|date',
      'notes' => 'nullable|string|max:1000',
      'reason_in_out_id' => 'nullable|integer|exists:ap_masters,id',
      'item_type' => 'required|in:PRODUCTO,SERVICIO',
      'document_series_id' => 'required|integer|exists:assign_sales_series,id',
      'document_type' => 'required|in:GUIA_REMISION',

      // Detalles de productos o servicios
      'details' => 'required|array|min:1',
      'details.*.product_id' => 'nullable|integer|exists:products,id',
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
      'transmitter_id.required' => 'El remitente es requerido',
      'transmitter_id.integer' => 'El remitente debe ser un número entero',
      'transmitter_id.exists' => 'El remitente no existe',
      'receiver_id.required' => 'El destinatario es requerido',
      'receiver_id.integer' => 'El destinatario debe ser un número entero',
      'receiver_id.exists' => 'El destinatario no existe',
      'receiver_id.different' => 'El destinatario debe ser diferente al remitente',
      'movement_date.required' => 'La fecha de movimiento es requerida',
      'movement_date.date' => 'La fecha de movimiento debe ser una fecha válida',
      'notes.string' => 'Las notas deben ser texto',
      'notes.max' => 'Las notas no pueden exceder 1000 caracteres',
      'item_type.required' => 'El tipo de item es requerido',
      'item_type.in' => 'El tipo de item debe ser PRODUCTO o SERVICIO',
      'document_series_id.required' => 'La serie de documento es requerida',
      'document_series_id.integer' => 'La serie de documento debe ser un número entero',
      'document_series_id.exists' => 'La serie de documento no existe',
      'document_type.required' => 'El tipo de documento es requerido',
      'document_type.in' => 'El tipo de documento debe ser GUIA_REMISION',
      'details.required' => 'Debe proporcionar al menos un producto o servicio',
      'details.array' => 'Los detalles deben ser un array',
      'details.min' => 'Debe proporcionar al menos un producto o servicio',
      'details.*.product_id.exists' => 'El producto no existe',
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
