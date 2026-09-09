<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|integer|exists:op_vehiculo,id',
            'driver_id' => 'required|integer|exists:rrhh_persona,id',
            'supplier_id' => 'required|integer|exists:op_supplier,id',
            'mileage' => 'required|numeric|min:0',
            'gallons' => 'required|numeric|min:0|max:999999.999',
            'is_base' => 'nullable|boolean',
            'recorded_at' => 'nullable|date',
            'tank_left_photo' => 'nullable|string',
            'tank_right_photo' => 'nullable|string',
            'ticket_photo' => 'nullable|string',
            'photo' => 'nullable|string',
            'photo_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_id.required' => 'Debe seleccionar un vehículo',
            'vehicle_id.exists' => 'El vehículo seleccionado no es válido',
            'driver_id.required' => 'Debe seleccionar un conductor',
            'driver_id.exists' => 'El conductor seleccionado no es válido',
            'supplier_id.required' => 'Debe seleccionar un grifo',
            'supplier_id.exists' => 'El grifo seleccionado no es válido',
            'mileage.required' => 'El kilometraje es obligatorio',
            'mileage.min' => 'El kilometraje no puede ser negativo',
            'gallons.required' => 'Los galones son obligatorios',
            'gallons.min' => 'Los galones no pueden ser negativos',
        ];
    }
}
