<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispatchItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        
        $product = $this->whenLoaded('product');
        $origin = $this->whenLoaded('origin');
        $destination = $this->whenLoaded('destination');
        
        return [
            
            'id' => $this->id,
            'despacho_id' => $this->despacho_id,
            'cantidad' => $this->cantidad,
            'idproducto' => $this->idproducto,
            'idorigen' => $this->idorigen,
            'iddestino' => $this->iddestino,
            'observacion' => $this->observacion,
            'tiempo_estimado' => $this->tiempo_estimado,
            'tipo_flete' => $this->tipo_flete,
            'unidad_medida_id' => $this->unidad_medida_id,
            'km_viaje' => $this->km_viaje,
            'precio_unit' => $this->precio_unit,
            'total' => $this->total,
            'producto_descripcion' => $product->descripcion ?? null,
            'producto' => $product ? [
                'id' => $product->id,
                'descripcion' => $product->descripcion,
                'codigo' => $product->codigo ?? null
            ] : null,
            
            'origen_descripcion' => $origin->descripcion ?? null,
            'origen' => $origin ? [
                'id' => $origin->id,
                'descripcion' => $origin->descripcion,
                'codigo' => $origin->codigo ?? null
            ] : null,
            
            'destino_descripcion' => $destination->descripcion ?? null,
            'destino' => $destination ? [
                'id' => $destination->id,
                'descripcion' => $destination->descripcion,
                'codigo' => $destination->codigo ?? null
            ] : null,
            'valor_total' => $this->total,
            'valor_formateado' => $this->total ? 'S/ ' . number_format($this->total, 2) : 'S/ 0.00',
            'cantidad_formateada' => $this->cantidad ? number_format($this->cantidad, 2) : '0.00',
            'unidad_info' => $this->getUnidadInfo(),
            'is_valid' => $this->isValid(),
            'has_required_data' => $this->hasRequiredData(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'metadata' => [
                'has_product' => !empty($this->idproducto),
                'has_origin' => !empty($this->idorigen),
                'has_destination' => !empty($this->iddestino),
                'has_price' => !empty($this->precio_unit) || !empty($this->total),
                'is_complete' => $this->isComplete()
            ]
        ];
    }
    
    private function getUnidadInfo(): array
    {
        $unidades = [
            1 => ['id' => 1, 'nombre' => 'TONELADA', 'abreviatura' => 'TN'],
            2 => ['id' => 2, 'nombre' => 'CAJA', 'abreviatura' => 'CJA'],
            3 => ['id' => 3, 'nombre' => 'VIAJE', 'abreviatura' => 'VIAJE'],
            4 => ['id' => 4, 'nombre' => 'UNIDAD', 'abreviatura' => 'UND'],
            5 => ['id' => 5, 'nombre' => 'KILOGRAMO', 'abreviatura' => 'KG'],
            6 => ['id' => 6, 'nombre' => 'LITRO', 'abreviatura' => 'LT'],
            7 => ['id' => 7, 'nombre' => 'METRO', 'abreviatura' => 'M'],
            8 => ['id' => 8, 'nombre' => 'METRO CUADRADO', 'abreviatura' => 'M²'],
            9 => ['id' => 9, 'nombre' => 'METRO CÚBICO', 'abreviatura' => 'M³'],
            10 => ['id' => 10, 'nombre' => 'GALÓN', 'abreviatura' => 'GL'],
            11 => ['id' => 11, 'nombre' => 'BOLSA', 'abreviatura' => 'BOLSA'],
            12 => ['id' => 12, 'nombre' => 'PALET', 'abreviatura' => 'PALET']
        ];
        
        return $unidades[$this->unidad_medida_id] ?? ['id' => $this->unidad_medida_id, 'nombre' => 'Desconocido', 'abreviatura' => 'N/A'];
    }
    
    private function isValid(): bool
    {
        return !empty($this->idproducto) && 
               !empty($this->cantidad) && 
               !empty($this->idorigen) && 
               !empty($this->iddestino);
    }
    
    private function hasRequiredData(): bool
    {
        return !empty($this->idproducto) && 
               !empty($this->cantidad) && 
               !empty($this->idorigen) && 
               !empty($this->iddestino);
    }
    
    private function isComplete(): bool
    {
        return $this->hasRequiredData() && 
               !empty($this->tipo_flete) && 
               !empty($this->unidad_medida_id);
    }
    
    public function with($request)
    {
        return [
            'meta' => [
                'version' => '1.0',
                'resource_type' => 'dispatch_item',
                'allowed_tipo_flete' => ['TONELADAS', 'VIAJE', 'CAJA', 'PALET', 'BOLSA'],
                'timestamp' => now()->toISOString()
            ]
        ];
    }
}