<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApVehicleDocumentsResource extends JsonResource
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
            'document_type' => $this->document_type,
            'issuer_type' => $this->issuer_type,
            'document_series' => $this->document_series,
            'document_number' => $this->document_number,
            'issue_date' => $this->issue_date,
            'requires_sunat' => $this->requires_sunat,
            'is_sunat_registered' => $this->is_sunat_registered,
            'vehicle_movement_id' => $this->vehicle_movement_id,
            'transmitter_id' => $this->transmitter_id,
            'receiver_id' => $this->receiver_id,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_url' => $this->file_url,
            'driver_doc' => $this->driver_doc,
            'company_name' => $this->company_name,
            'license' => $this->license,
            'plate' => $this->plate,
            'driver_name' => $this->driver_name,
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_by' => $this->cancelled_by,
            'cancelled_at' => $this->cancelled_at,
            'notes' => $this->notes,
            'status' => $this->status,
            'transfer_reason_id' => $this->transfer_reason_id,
            'transfer_modality_id' => $this->transfer_modality_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relaciones
            'vehicle_movement' => $this->whenLoaded('vehicleMovement', function () {
                return [
                    'id' => $this->vehicleMovement->id,
                    'movement_type' => $this->vehicleMovement->movement_type,
                    'movement_date' => $this->vehicleMovement->movement_date,
                    'origin_address' => $this->vehicleMovement->origin_address,
                    'destination_address' => $this->vehicleMovement->destination_address,
                    'observation' => $this->vehicleMovement->observation,
                    'previous_status_id' => $this->vehicleMovement->previous_status_id,
                    'new_status_id' => $this->vehicleMovement->new_status_id,
                    'ap_vehicle_purchase_order_id' => $this->vehicleMovement->ap_vehicle_purchase_order_id,
                ];
            }),

            'transmitter' => $this->whenLoaded('transmitter', function () {
                return [
                    'id' => $this->transmitter->id,
                    'full_name' => $this->transmitter->full_name,
                    'num_doc' => $this->transmitter->num_doc,
                    'email' => $this->transmitter->email,
                    'phone' => $this->transmitter->phone,
                    'direction' => $this->transmitter->direction,
                ];
            }),

            'receiver' => $this->whenLoaded('receiver', function () {
                return [
                    'id' => $this->receiver->id,
                    'full_name' => $this->receiver->full_name,
                    'num_doc' => $this->receiver->num_doc,
                    'email' => $this->receiver->email,
                    'phone' => $this->receiver->phone,
                    'direction' => $this->receiver->direction,
                ];
            }),

            'transfer_modality' => $this->whenLoaded('transferModality', function () {
                return [
                    'id' => $this->transferModality->id,
                    'code' => $this->transferModality->code,
                    'description' => $this->transferModality->description,
                    'type' => $this->transferModality->type,
                ];
            }),

            'transfer_reason' => $this->whenLoaded('transferReason', function () {
                return [
                    'id' => $this->transferReason->id,
                    'code' => $this->transferReason->code,
                    'description' => $this->transferReason->description,
                    'type' => $this->transferReason->type,
                ];
            }),
        ];
    }
}
