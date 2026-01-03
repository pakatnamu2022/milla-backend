<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TpTravelPhotoResource extends JsonResource
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
            'photo_type' => $data['photo_type'] ?? null,
            'photo_type_label' => $this->getPhotoTypeLabel($data['photo_type'] ?? null),
            'file_name' => $data['file_name'] ?? null,
            'path' => $data['path'] ?? null,
            'public_url' => $data['public_url'] ?? null,
            'mime_type' => $data['mime_type'] ?? null,
            'file_size' => $data['file_size'] ?? null,
            'file_extension' => $this->getFileExtension($data['file_name'] ?? ''),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'has_geolocation' => !empty($data['latitude']) && !empty($data['longitude']),
            'user_agent' => $data['user_agent'] ?? null,
            'userAgent' => $data['user_agent'] ?? null,
            'operating_system' => $data['operating_system'] ?? null,
            'operatingSystem' => $data['operating_system'] ?? null,
            'browser' => $data['browser'] ?? null,
            'device_model' => $data['device_model'] ?? null,
            'deviceModel' => $data['device_model'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => isset($data['created_at']) ? $this->formatDate($data['created_at']) : null,
            'updated_at' => isset($data['updated_at']) ? $this->formatDate($data['updated_at']) : null,
            'formattedDate' => isset($data['created_at']) ? $this->formatDate($data['created_at'], 'd/m/Y H:i') : null,
            'created_at_human' => isset($data['created_at']) ? $this->getHumanDate($data['created_at']) : null,
            'travel' => $this->getTravelInfo($data),
        
            'driver_info' => $this->getDriverInfo($data),
            'thumbnail_url' => $this->getThumbnailUrl($data),
            'preview_url' => $this->getPreviewUrl($data),
            'metadata' => [
                'is_image' => $this->isImage($data),
                'is_valid_photo' => $this->isValidPhoto($data),
                'storage_disk' => config('filesystems.default', 's3'),
                'max_file_size_mb' => 5,
                'allowed_mime_types' => [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/gif',
                    'image/webp'
                ],
                'can_be_deleted' => $this->canBeDeleted($data),
                'can_be_downloaded' => true
            ],
            'validation' => [
                'has_geolocation' => !empty($data['latitude']) && !empty($data['longitude']),
                'has_metadata' => !empty($data['user_agent']) || !empty($data['notes']),
                'is_complete' => !empty($data['public_url']) && !empty($data['photo_type'])
            ]
        ];
    }
    
    private function getPhotoTypeLabel(?string $type): string
    {
        if (!$type) {
            return 'Desconocido';
        }
        
        $labels = [
            'start' => 'Inicio de Viaje',
            'end' => 'Fin de Viaje',
            'fuel' => 'Combustible',
            'incident' => 'Incidente',
            'invoice' => 'Comprobante'
        ];
        
        return $labels[$type] ?? ucfirst($type);
    }
    
    private function getFileExtension(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }
    
    private function isImage(array $data): bool
    {
        $mimeType = $data['mime_type'] ?? null;
        
        if (!$mimeType) {
            return false;
        }
        
        $allowedMimes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];
        
        return in_array($mimeType, $allowedMimes);
    }
    
    private function isValidPhoto(array $data): bool
    {
        return $this->isImage($data) && 
               !empty($data['public_url']) && 
               !empty($data['photo_type']) &&
               in_array($data['photo_type'], ['start', 'end', 'fuel', 'incident', 'invoice']);
    }
    
    private function canBeDeleted(array $data): bool
    {
        return auth()->check() && (
            auth()->id() == ($data['created_by'] ?? null) 
        );
    }
    
    private function getThumbnailUrl(array $data): ?string
    {
        $publicUrl = $data['public_url'] ?? null;
        
        if (!$publicUrl) {
            return null;
        }
        
        if (strpos($publicUrl, 'amazonaws.com') !== false) {
            return $publicUrl . '?w=150&h=150&fit=crop';
        }
        
        return $publicUrl;
    }
    
    private function getPreviewUrl(array $data): ?string
    {
        $publicUrl = $data['public_url'] ?? null;
        
        if (!$publicUrl) {
            return null;
        }
        
        if (strpos($publicUrl, 'amazonaws.com') !== false) {
            return $publicUrl . '?w=800&h=600&fit=contain';
        }
        
        return $publicUrl;
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
    
    private function getTravelInfo(array $data): array
    {
        if (!isset($data['travel']) || !is_array($data['travel'])) {
            return [];
        }
        
        return [
            'id' => $data['travel']['id'] ?? null,
            'tripNumber' => $data['travel']['codigo'] ?? null,
            'route' => $data['travel']['ruta'] ?? null,
            'driver_name' => $data['travel']['conductor_nombre'] ?? null
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
            'documento' => $data['driver']['documento'] ?? null
        ];
    }
    
    public function with($request)
    {
        return [
            'meta' => [
                'version' => '1.0',
                'resource_type' => 'travel_photo',
                'allowed_photo_types' => ['start', 'end', 'fuel', 'incident', 'invoice'],
                'max_file_size_mb' => 5,
                'compatible_with' => 'travelphoto.actions.ts',
                'timestamp' => now()->toISOString()
            ]
        ];
    }
}