<?php

namespace App\Http\Requests\tp\comercial;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplyControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => 'sometimes|integer|exists:op_vehiculo,id',
            'driver_id' => 'sometimes|integer|exists:rrhh_persona,id',
            'supplier_id' => 'sometimes|integer|exists:op_supplier,id',
            'mileage' => 'sometimes|numeric|min:0',
            'gallons' => 'sometimes|numeric|min:0|max:999999.999',
            'is_base' => 'nullable|boolean',
            'recorded_at' => 'nullable|date',
        ];
    }
}
