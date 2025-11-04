<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingGuidesResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'ap_vehicle_id' => $this->vehicleMovement->ap_vehicle_id,
      'document_series_id' => $this->document_series_id ?? "",
      'series' => $this->series ?? "",
      'correlative' => $this->correlative ?? "",
      'document_type' => $this->document_type,
      'issuer_type' => $this->issuer_type,
      'document_series' => $this->documentSeries->series ?? $this->series ?? "-",
      'document_number' => $this->document_number,
      'issue_date' => $this->issue_date,
      'requires_sunat' => $this->requires_sunat,
      'is_sunat_registered' => $this->is_sunat_registered,
      'transmitter_id' => $this->transmitter_id,
      'transmitter_establishment' => $this->transmitter ? [
        'id' => $this->transmitter->id,
        'code' => $this->transmitter->code,
        'description' => $this->transmitter->description,
        'full_address' => $this->transmitter->full_address,
      ] : null,
      'receiver_id' => $this->receiver_id,
      'receiver_establishment' => $this->receiver ? [
        'id' => $this->receiver->id,
        'code' => $this->receiver->code,
        'description' => $this->receiver->description,
        'full_address' => $this->receiver->full_address,
      ] : null,
      'transmitter_origin_id' => $this->transmitter->business_partner_id,
      'receiver_destination_id' => $this->receiver->business_partner_id,
      'file_url' => $this->file_url,
      'driver_doc' => $this->driver_doc,
      'company_name' => $this->company_name,
      'license' => $this->license,
      'plate' => $this->plate,
      'driver_name' => $this->driver_name,
      'cancellation_reason' => $this->cancellation_reason,
      'cancelled_by' => $this->cancelled_by,
      'cancelled_at' => $this->cancelled_at,
      'total_packages' => $this->total_packages,
      'total_weight' => $this->total_weight,
      'notes' => $this->notes,
      'status' => $this->status,
      'transfer_reason_id' => $this->transfer_reason_id,
      'transfer_modality_id' => $this->transfer_modality_id,
      'transport_company_id' => $this->transport_company_id,
      'sede_transmitter_id' => $this->sede_transmitter_id,
      'sede_receiver_id' => $this->sede_receiver_id,
      'is_received' => $this->is_received,
      // Relaciones
      'sede_transmitter' => $this->sedeTransmitter->abreviatura ?? "-",
      'sede_receiver' => $this->sedeReceiver->abreviatura ?? "-",
      'transmitter_description' => $this->transmitter->description . ' - ' . $this->transmitter->address,
      'receiver_description' => $this->receiver->description . ' - ' . $this->receiver->address,
      'transfer_modality_description' => $this->transferModality->description,
      'transfer_reason_description' => $this->transferReason->description,
      'sent_at' => $this->sent_at,
    ];
  }
}
