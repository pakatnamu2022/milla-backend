<?php

namespace App\Http\Resources\gp\tics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
//            RESOURCE
            'id' => $this->id,
            'equipo' => $this->equipo,
            'tipo_equipo' => $this->equipmentType ? $this->equipmentType->name : null,
            'marca_modelo' => $this->marca_modelo,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'serie' => $this->serie,
            'status' => $this->status ? $this->status->estado : null,
            'estado_uso' => $this->estado_uso,
            'detalle' => $this->detalle,
            'sede' => $this->sede ? $this->sede->suc_abrev : null,
            'empresa' => $this->sede?->company ? $this->sede->company->abbreviation . ' - ' . $this->sede->suc_abrev : null,

//            DETALLES
            'ram' => $this->ram,
            'almacenamiento' => $this->almacenamiento,
            'procesador' => $this->procesador,
            'stock_actual' => $this->stock_actual,
            'pertenece_sede' => $this->pertenece_sede,

//            ADQUISICION
            'tipo_adquisicion' => $this->tipo_adquisicion,
            'factura' => $this->factura,
            'contrato' => $this->contrato,
            'proveedor' => $this->proveedor,
            'fecha_adquisicion' => $this->fecha_adquisicion,
            'fecha_garantia' => $this->fecha_garantia,

//            FOREIGN KEYS
            'tipo_equipo_id' => $this->tipo_equipo_id,
            'sede_id' => $this->sede_id,
            'status_id' => $this->status_id,
            'status_deleted' => $this->status_deleted,

//            ESTADO DE ASIGNACIÓN (calculado)
            'assignment_status' => $this->computeAssignmentStatus(),
            'assigned_to' => $this->activeAssignment
              ? [
                'assignment_id' => $this->activeAssignment->id,
                'persona_id'    => $this->activeAssignment->persona_id,
                'worker_name'   => $this->activeAssignment->worker?->nombre_completo,
                'fecha'         => $this->activeAssignment->fecha,
              ]
              : null,
        ];
    }

    /**
     * Calcula el estado de asignación del equipo:
     * - disponible: sin asignación activa
     * - asignado: asignado a un trabajador activo
     * - pendiente_liberacion: asignado a un trabajador que está de baja
     */
    private function computeAssignmentStatus(): string
    {
        $assignment = $this->activeAssignment;
        if (!$assignment) return 'disponible';

        $worker = $assignment->worker;
        if (!$worker || !$worker->b_empleado) return 'pendiente_liberacion';

        return 'asignado';
    }
}
