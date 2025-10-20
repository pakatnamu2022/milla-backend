<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreShippingGuidesRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'document_type' => 'required|string|max:100',
      'issuer_type' => 'required|string|max:100',
      'document_series' => 'nullable|string|max:20',
      'document_number' => 'nullable|string|max:50',
      'issue_date' => 'nullable|date',
      'requires_sunat' => 'nullable|boolean',
      'is_sunat_registered' => 'nullable|boolean',
      'vehicle_movement_id' => 'required|integer|exists:ap_vehicle_movement,id',
      'transmitter_id' => 'required|integer|exists:business_partners,id',
      'receiver_id' => 'required|integer|exists:business_partners,id',
      'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
      'driver_doc' => 'nullable|string|max:255',
      'company_name' => 'nullable|string|max:255',
      'license' => 'nullable|string|max:255',
      'plate' => 'nullable|string|max:255',
      'driver_name' => 'nullable|string|max:255',
      'notes' => 'nullable|string',
      'status' => 'nullable|boolean',
      'transfer_reason_id' => 'nullable|integer|exists:sunat_concepts,id',
      'transfer_modality_id' => 'nullable|integer|exists:sunat_concepts,id',

      // Datos del movimiento del vehículo
      'movement_type' => 'required|string|max:50',
      'origin_address' => 'nullable|string|max:255',
      'destination_address' => 'nullable|string|max:255',
      'ap_vehicle_purchase_order_id' => 'nullable|integer',
      'observation' => 'nullable|string',
      'movement_date' => 'required|date',
      'previous_status_id' => 'nullable|integer|exists:ap_vehicle_status,id',
      'new_status_id' => 'nullable|integer|exists:ap_vehicle_status,id',
    ];
  }
}
