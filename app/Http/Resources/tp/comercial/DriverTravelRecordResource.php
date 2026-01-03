<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverTravelRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = (array) $this->resource;
        
        return [
            'id' => $data['id'] ?? null,
            'dispatch_id' => $data['dispatch_id'] ?? null,
            'driver_id' => $data['driver_id'] ?? null,
            'record_type' => $data['record_type'] ?? null,
            'record_type_label' => $this->getRecordTypeLabel($data['record_type'] ?? null),
            'recorded_at' => isset($data['recorded_at']) ? $this->formatDate($data['recorded_at']) : null,
            'recorded_at_human' => isset($data['recorded_at']) ? $this->getHumanDate($data['recorded_at']) : null,
            'formatted_date' => isset($data['recorded_at']) ? $this->formatDate($data['recorded_at'], 'd/m/Y H:i') : null,
            'recorded_mileage' => $data['recorded_mileage'] ?? null,
            'recorded_mileage_formatted' => isset($data['recorded_mileage']) ? 
                number_format($data['recorded_mileage'], 0, '.', ',') . ' km' : null,
            'notes' => $data['notes'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'sync_status' => $data['sync_status'] ?? null,
            'travel' => $this->getTravelInfo($data),
            
            'driver_info' => $this->getDriverInfo($data),
            
            'location' => [
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'has_location' => !empty($data['latitude']) && !empty($data['longitude'])
            ],
            
            'device_metadata' => [
                'user_agent' => $data['user_agent'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'platform' => $data['platform'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'operating_system' => $data['operating_system'] ?? null,
                'browser' => $data['browser'] ?? null
            ],
            
            'validation' => [
                'is_valid' => $this->isValidRecord($data),
                'mileage_is_valid' => $this->isMileageValid($data),
                'has_required_data' => $this->hasRequiredData($data),
                'is_complete' => $this->isComplete($data)
            ],
            
            'context' => [
                'is_start_record' => ($data['record_type'] ?? null) === 'start',
                'is_end_record' => ($data['record_type'] ?? null) === 'end',
                'is_fuel_record' => ($data['record_type'] ?? null) === 'fuel',
                'is_incident_record' => ($data['record_type'] ?? null) === 'incident',
                'record_order' => $data['record_order'] ?? 0
            ],
            
            'calculated_fields' => [
                'previous_mileage' => $data['previous_mileage'] ?? null,
                'mileage_difference' => $data['mileage_difference'] ?? null,
                'hours_since_start' => $this->getHoursSinceStart($data),
                'days_since_creation' => isset($data['created_at']) ? $this->getDaysSinceCreation($data['created_at']) : null
            ],
            
            'created_at' => isset($data['created_at']) ? $this->formatDate($data['created_at']) : null,
            'updated_at' => isset($data['updated_at']) ? $this->formatDate($data['updated_at']) : null,
            'sync_at' => isset($data['sync_at']) ? $this->formatDate($data['sync_at']) : null,
            
            'audit_info' => [
                'created_by' => $data['created_by'] ?? null,
                'updated_by' => $data['updated_by'] ?? null,
                'sync_attempts' => $data['sync_attempts'] ?? 0,
                'last_sync_error' => $data['last_sync_error'] ?? null
            ],
            'related_stats' => [
                'total_records_in_trip' => isset($data['travel']) && isset($data['travel']['driver_records_count']) 
                    ? $data['travel']['driver_records_count'] 
                    : 0,
                'photo_count_for_record' => isset($data['photos']) && is_array($data['photos']) 
                    ? count($data['photos']) 
                    : 0
            ]
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
            'incident' => 'Incidente',
            'checkpoint' => 'Punto de Control',
            'break' => 'Descanso',
            'loading' => 'Carga',
            'unloading' => 'Descarga',
            'maintenance' => 'Mantenimiento',
            'inspection' => 'Inspección'
        ];
        
        return $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
    
    private function isValidRecord(array $data): bool
    {
        return !empty($data['record_type']) && 
               !empty($data['recorded_at']) && 
               !empty($data['dispatch_id']);
    }
    
    private function isMileageValid(array $data): bool
    {
        if (empty($data['recorded_mileage'])) {
            return true; 
        }
        
        return is_numeric($data['recorded_mileage']) && $data['recorded_mileage'] >= 0;
    }
    
    private function hasRequiredData(array $data): bool
    {
        $required = ['record_type', 'recorded_at', 'dispatch_id'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }

    private function isComplete(array $data): bool
    {
        $recordType = $data['record_type'] ?? null;
        
        if (in_array($recordType, ['start', 'end'])) {
            return $this->hasRequiredData($data) && 
                   $this->isMileageValid($data) && 
                   !empty($data['recorded_mileage']);
        }
        
        return $this->hasRequiredData($data);
    }
    
    private function formatDate($date, $format = 'Y-m-d H:i:s'): ?string
    {
        if (!$date) {
            return null;
        }
        
        try {
            if (is_string($date)) {
                return date($format, strtotime($date));
            } elseif ($date instanceof \DateTime) {
                return $date->format($format);
            }
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
    }
    
    private function getHumanDate($date): ?string
    {
        if (!$date) {
            return null;
        }
        
        try {
            $dateTime = is_string($date) ? new \DateTime($date) : $date;
            $now = new \DateTime();
            $diff = $now->diff($dateTime);
            
            if ($diff->days === 0) {
                return 'Hoy';
            } elseif ($diff->days === 1) {
                return 'Ayer';
            } elseif ($diff->days < 7) {
                return "Hace {$diff->days} días";
            } elseif ($diff->days < 30) {
                $weeks = floor($diff->days / 7);
                return "Hace {$weeks} semana" . ($weeks > 1 ? 's' : '');
            } else {
                $months = floor($diff->days / 30);
                return "Hace {$months} mes" . ($months > 1 ? 'es' : '');
            }
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function getDaysSinceCreation($createdAt): ?int
    {
        if (!$createdAt) {
            return null;
        }
        
        try {
            $createdDate = is_string($createdAt) ? new \DateTime($createdAt) : $createdAt;
            $now = new \DateTime();
            return $now->diff($createdDate)->days;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    private function getTravelInfo(array $data): array
    {
        if (!isset($data['travel']) || !is_array($data['travel'])) {
            return [];
        }
        
        return [
            'id' => $data['travel']['id'] ?? null,
            'codigo' => $data['travel']['codigo'] ?? null,
            'tripNumber' => $data['travel']['codigo'] ?? null,
            'ruta' => $data['travel']['ruta'] ?? null,
            'cliente_nombre' => $data['travel']['cliente_nombre'] ?? null
        ];
    }
    
    private function getDriverInfo(array $data): array
    {
        if (!isset($data['driver']) || !is_array($data['driver'])) {
            return [];
        }
        
        return [
            'id' => $data['driver']['id'] ?? null,
            'nombre_completo' => $data['driver']['nombre_completo'] ?? null,
            'documento' => $data['driver']['documento'] ?? null,
            'telefono' => $data['driver']['telefono'] ?? null
        ];
    }
    
    private function getHoursSinceStart(array $data): ?float
    {
        return null;
    }
    
    public function with($request)
    {
        return [
            'meta' => [
                'version' => '1.0',
                'resource_type' => 'driver_travel_record',
                'allowed_record_types' => [
                    'start', 'end', 'fuel', 'incident', 'checkpoint', 
                    'break', 'loading', 'unloading', 'maintenance', 'inspection'
                ],
                'timestamp' => now()->toISOString()
            ]
        ];
    }
}