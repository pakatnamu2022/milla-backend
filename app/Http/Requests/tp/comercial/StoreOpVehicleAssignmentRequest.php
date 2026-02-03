<?php

namespace App\Http\Requests\tp\comercial;

use App\Http\Requests\StoreRequest;

class StoreOpVehicleAssignmentRequest extends StoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle' => 'required|exists:op_vehiculo,id',
            'driver' => 'required|exists:rrhh_persona,id'
        ];
    }
    
    public function messages()
    {
        return[
            'vehicle.required' => 'El Vehiculo es requerido',
            'vehicle.exists' => 'El vehiculo seleccionado no existe',
            'driver.required' => 'El conductor es requerido',
            'driver.exists' => 'El conductor seleccionado no existe',
        ];
    }
}
