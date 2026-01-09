<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverTravelRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dispatch_id' => $this->dispatch_id,
            'driver_id' => $this->driver_id,
            'record_type' => $this->record_type,
            'record_type_label' => $this->getRecordTypeLabel($this->record_type),
            'recorded_at' => $this->recorded_at,
            'recorded_at_human' => $this->recorded_at ? $this->recorded_at->diffForHumans() : null,
            'formatted_date' => $this->recorded_at ? $this->recorded_at->format('d/m/Y H:i') : null,
            'recorded_mileage' => $this->recorded_mileage,
            'recorded_mileage_formatted' => $this->recorded_mileage ? 
                number_format($this->recorded_mileage, 0, '.', ',') . ' km' : null,
            'notes' => $this->notes,
            'device_id' => $this->device_id,
            'sync_status' => $this->sync_status,
            'travel' => $this->relationLoaded('dispatch') && $this->dispatch ? [
                'id' => $this->dispatch->id,
                'codigo' => $this->dispatch->trip_number ?? 'TPV' . str_pad($this->dispatch->id, 8, '0', STR_PAD_LEFT),
                'tripNumber' => $this->dispatch->trip_number ?? 'TPV' . str_pad($this->dispatch->id, 8, '0', STR_PAD_LEFT),
                'ruta' => $this->dispatch->ruta ?? 'Sin ruta',
                'cliente_nombre' => $this->dispatch->relationLoaded('customer') && $this->dispatch->customer ? 
                    $this->dispatch->customer->nombre_completo : null
            ] : null,
            'driver_info' => $this->relationLoaded('driver') && $this->driver ? [
                'id' => $this->driver->id,
                'nombre_completo' => $this->driver->nombre_completo,
                'vat' => $this->driver->vat
            ] : null,
            'validation' => [
                'is_valid' => $this->isValid(),
                'mileage_is_valid' => $this->isMileageValid(),
                'has_required_data' => $this->hasRequiredData(),
                'is_complete' => $this->isComplete()
            ],
            'context' => [
                'is_start_record' => $this->record_type === 'start',
                'is_end_record' => $this->record_type === 'end',
                'is_fuel_record' => $this->record_type === 'fuel',
                'is_incident_record' => $this->record_type === 'incident'
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'sync_at' => $this->sync_at
        ];
    }


    private function getRecordTypeLabel(?string $type): string
    {
        if (!$type) {
            return 'Desconocido';
        }
        
        $labels = [
            'start' => 'Inicio de Ruta',
            'end' => 'Fin de Ruta',
            'fuel' => 'Registro de Combustible',
            'incident' => 'Incidente'
        ];
        
        return $labels[$type] ?? ucfirst($type);
    }
    
    private function isValid(): bool
    {
        return !empty($this->record_type) && 
               !empty($this->recorded_at) && 
               !empty($this->dispatch_id);
    }
    
    private function isMileageValid(): bool
    {
        if (!$this->recorded_mileage) {
            return true;
        }
        
        return is_numeric($this->recorded_mileage) && $this->recorded_mileage >= 0;
    }
    
    private function hasRequiredData(): bool
    {
        return !empty($this->record_type) && 
               !empty($this->recorded_at) && 
               !empty($this->dispatch_id);
    }

    private function isComplete(): bool
    {
        if (in_array($this->record_type, ['start', 'end'])) {
            return $this->hasRequiredData() && 
                   $this->isMileageValid() && 
                   !empty($this->recorded_mileage);
        }
        
        return $this->hasRequiredData();
    }
}