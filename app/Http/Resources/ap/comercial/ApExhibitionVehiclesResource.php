<?php

namespace App\Http\Resources\ap\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApExhibitionVehiclesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Header fields
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->supplier ? [
                'id' => $this->supplier->id,
                'full_name' => $this->supplier->full_name,
                'num_doc' => $this->supplier->num_doc,
            ] : null,

            'guia_number' => $this->guia_number,
            'guia_date' => $this->guia_date?->format('Y-m-d'),
            'llegada' => $this->llegada?->format('Y-m-d'),

            'ubicacion_id' => $this->ubicacion_id,
            'ubicacion' => $this->ubicacion ? [
                'id' => $this->ubicacion->id,
                'description' => $this->ubicacion->description,
                'sede_id' => $this->ubicacion->sede_id,
                'sede_name' => $this->ubicacion->sede?->abreviatura ?? null,
            ] : null,

            'advisor_id' => $this->advisor_id,
            'advisor' => $this->advisor ? [
                'id' => $this->advisor->id,
                'nombre_completo' => $this->advisor->nombre_completo,
            ] : null,

            'propietario_id' => $this->propietario_id,
            'propietario' => $this->propietario ? [
                'id' => $this->propietario->id,
                'full_name' => $this->propietario->full_name,
                'num_doc' => $this->propietario->num_doc,
            ] : null,

            'ap_vehicle_status_id' => $this->ap_vehicle_status_id,
            'vehicle_status' => $this->vehicleStatus?->description,

            'pedido_sucursal' => $this->pedido_sucursal,
            'dua_number' => $this->dua_number,
            'observaciones' => $this->observaciones,
            'status' => $this->status,

            // Items
            'items' => $this->items->map(function ($item) {
                $itemData = [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'observaciones' => $item->observaciones,
                    'status' => $item->status,
                ];

                // Add vehicle data if item is a vehicle
                if ($item->item_type === 'vehicle' && $item->vehicle) {
                    $itemData['vehicle'] = [
                        'id' => $item->vehicle->id,
                        'vin' => $item->vehicle->vin,
                        'plate' => $item->vehicle->plate,
                        'year' => $item->vehicle->year,
                        'engine_number' => $item->vehicle->engine_number,
                        'ap_vehicle_status_id' => $item->vehicle->ap_vehicle_status_id,
                        'vehicle_status' => $item->vehicle->vehicleStatus?->description,
                        'vehicle_color_id' => $item->vehicle->vehicle_color_id,
                        'vehicle_color' => $item->vehicle->color?->description,
                        'model_id' => $item->vehicle->ap_models_vn_id,
                        'model_version' => $item->vehicle->model?->version,
                        'family' => $item->vehicle->model?->family?->description,
                        'brand' => $item->vehicle->model?->family?->brand?->name,
                    ];
                }

                return $itemData;
            }),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
