<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Traits\HandlesMissingValue;

class TravelExpenseResource extends JsonResource
{
    use HandlesMissingValue;
    
    public function toArray(Request $request): array
    {
        
        if ($this->isMissingValue()) {
            return [];
        }

        $isFuelExpense = $this->safeGet('concepto_id') == 25;
        
        return [
            'id' => $this->safeGet('id'),
            'viaje_id' => $this->safeGet('viaje_id'),
            'concepto_id' => $this->safeGet('concepto_id'),
            'monto' => $this->safeGet('monto'),
            'numero_doc' => $this->safeGet('numero_doc'),
            'fecha_emision' => $this->safeFormatDate($this->safeGet('fecha_emision')),
            'ruc' => $this->safeGet('ruc'),
            'status_aprobacion' => $this->safeGet('status_aprobacion'),
            'status_observacion' => $this->safeGet('status_observacion'),
            'aprobado' => (bool) $this->safeGet('aprobado', false),
            'status_deleted' => (bool) $this->safeGet('status_deleted', false),
            'file' => $this->safeGet('file'),
            'extencion' => $this->safeGet('extencion'),
            'fileDoc' => $this->safeGet('fileDoc'),
            'liquidacion_id' => $this->safeGet('liquidacion_id'),
            'is_fuel_expense' => $isFuelExpense,
            'km_tanqueo' => $this->safeGet('km_tanqueo'),
            'punto_tanqueo_id' => $this->safeGet('punto_tanqueo_id'),
            'created_at' => $this->safeFormatDate($this->safeGet('created_at')),
            'updated_at' => $this->safeFormatDate($this->safeGet('updated_at')),
        ];
    }
}