<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreShippingGuidesRequest extends StoreRequest
{

  public function prepareForValidation()
  {
    if (!isset($this->send_dynamics)) {
      $this->merge([
        'send_dynamics' => 1,
      ]);
    }
    if (!isset($this->is_consignment)) {
      $this->merge([
        'is_consignment' => 0
      ]);
    }
  }

  public function rules(): array
  {
    return [
      'document_type' => 'required|string|max:100',
      'issuer_type' => 'required|string|max:100',
      'document_series_id' => 'nullable|integer|exists:assign_sales_series,id',
      'series' => 'nullable|string|max:20',
      'correlative' => 'nullable|string',
      'issue_date' => 'required|date',
      'sede_transmitter_id' => 'required|integer|exists:config_sede,id',
      'sede_receiver_id' => 'required|integer|exists:config_sede,id',
      'transmitter_id' => 'required|integer|exists:business_partners_establishment,id',
      'receiver_id' => 'required|integer|exists:business_partners_establishment,id',
      'ap_vehicle_id' => 'required|integer|exists:ap_vehicles,id',
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
      'ap_class_article_id' => 'required|integer|exists:ap_class_article,id',
      'send_dynamics' => 'required|boolean',
      'is_consignment' => 'required|boolean',
      'accessories' => 'nullable|array',
      'accessories.*.description' => 'required_with:accessories|string|max:255',
      'accessories.*.quantity' => 'required_with:accessories|numeric|min:0.01',
      'accessories.*.unit_measurement_id' => 'nullable|integer|exists:unit_measurement,id',
    ];
  }

  public function attributes(): array
  {
    return [
      'document_type' => 'tipo de documento',
      'issuer_type' => 'tipo de emisor',
      'document_series_id' => 'serie del documento',
      'series' => 'serie',
      'correlative' => 'correlativo',
      'issue_date' => 'fecha de emisión',
      'sede_transmitter_id' => 'sede remitente',
      'sede_receiver_id' => 'sede destinataria',
      'transmitter_id' => 'remitente',
      'receiver_id' => 'destinatario',
      'ap_vehicle_id' => 'vehículo',
      'requires_sunat' => 'requiere sunat',
      'total_packages' => 'total de bultos',
      'total_weight' => 'peso total',
      'file' => 'archivo',
      'transport_company_id' => 'empresa de transporte',
      'driver_doc' => 'documento del conductor',
      'license' => 'licencia',
      'plate' => 'placa',
      'driver_name' => 'nombre del conductor',
      'notes' => 'notas',
      'status' => 'estado',
      'transfer_reason_id' => 'motivo de traslado',
      'transfer_modality_id' => 'modalidad de traslado',
      'ap_class_article_id' => 'clase de artículo',
      'send_dynamics' => 'enviar a dynamics',
      'is_consignment' => 'es consignación',
      'accessories' => 'accesorios',
      'accessories.*.description' => 'descripción del accesorio',
      'accessories.*.quantity' => 'cantidad del accesorio',
      'accessories.*.unit_measurement_id' => 'unidad de medida del accesorio',
    ];
  }
}
