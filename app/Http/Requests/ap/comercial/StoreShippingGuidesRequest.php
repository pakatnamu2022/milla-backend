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
      'document_series_id' => 'required|integer|exists:assign_sales_series,id',
      'issue_date' => 'required|date',
      'sede_transmitter_id' => 'required|integer|exists:config_sede,id',
      'sede_receiver_id' => 'required|integer|exists:config_sede,id',
      'transmitter_id' => 'required|integer|exists:business_partners_establishment,id',
      'receiver_id' => 'required|integer|exists:business_partners_establishment,id',
      'ap_vehicle_purchase_order_id' => 'required|integer|exists:ap_vehicle_purchase_order,id',
      'requires_sunat' => 'nullable|boolean',
      'total_packages' => 'nullable|numeric|min:0',
      'total_weight' => 'nullable|numeric|min:0',
      'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xml|max:10240',
      'transport_company_id' => 'nullable|integer|exists:business_partners,id',
      'driver_doc' => 'nullable|string|max:255',
      'license' => 'nullable|string|max:255',
      'plate' => 'nullable|string|max:255',
      'driver_name' => 'nullable|string|max:255',
      'notes' => 'nullable|string',
      'status' => 'nullable|boolean',
      'transfer_reason_id' => 'nullable|integer|exists:sunat_concepts,id',
      'transfer_modality_id' => 'nullable|integer|exists:sunat_concepts,id',
    ];
  }

  public function messages(): array
  {
    return [
      'document_type.required' => 'El tipo de documento es obligatorio.',
      'issuer_type.required' => 'El tipo de emisor es obligatorio.',
      'issue_date.required' => 'La fecha de emisión es obligatoria.',
      'sede_transmitter_id.required' => 'El sede origen es obligatorio.',
      'sede_transmitter_id.integer' => 'El sede origen es invalido.',
      'sede_transmitter_id.exists' => 'El sede origen no existe.',
      'sede_receiver_id.required' => 'El sede destino es obligatorio.',
      'sede_receiver_id.integer' => 'El sede destino es invalido.',
      'sede_receiver_id.exists' => 'El sede destino no existe.',
      'transmitter_id.required' => 'El remitente es obligatorio.',
      'receiver_id.required' => 'El destinatario es obligatorio.',
      'file.mimes' => 'El archivo debe ser un PDF, JPG, JPEG, PNG o XML.',
      'file.max' => 'El tamaño máximo del archivo es 10MB.',
      'requires_sunat' => 'El campo requiere sunat debe ser verdadero o falso.',
    ];
  }
}
