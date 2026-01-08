<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class StoreApExhibitionVehiclesRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            // Header fields
            'supplier_id' => 'sometimes|nullable|integer|exists:business_partners,id',
            'guia_number' => 'sometimes|nullable|string|max:255',
            'guia_date' => 'sometimes|nullable|date',
            'llegada' => 'sometimes|nullable|date',
            'ubicacion_id' => 'sometimes|nullable|integer|exists:warehouse,id',
            'advisor_id' => 'sometimes|nullable|integer|exists:rrhh_persona,id',
            'propietario_id' => 'sometimes|nullable|integer|exists:business_partners,id',
            'ap_vehicle_status_id' => 'sometimes|nullable|integer|exists:ap_vehicle_status,id',
            'pedido_sucursal' => 'sometimes|nullable|string|max:255',
            'dua_number' => 'sometimes|nullable|string|max:255',
            'observaciones' => 'sometimes|nullable|string',
            'status' => 'sometimes|boolean',

            // Items array
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:vehicle,equipment',
            'items.*.description' => 'sometimes|nullable|string',
            'items.*.quantity' => 'sometimes|integer|min:1',
            'items.*.observaciones' => 'sometimes|nullable|string',
            'items.*.status' => 'sometimes|boolean',

            // Vehicle data (for items with item_type=vehicle)
            'items.*.vehicle_data' => 'required_if:items.*.item_type,vehicle|array',
            'items.*.vehicle_data.vin' => 'required_with:items.*.vehicle_data|string|max:17|min:17|unique:ap_vehicles,vin',
            'items.*.vehicle_data.year' => 'required_with:items.*.vehicle_data|integer|min:1900|max:' . ((int)date('Y') + 2),
            'items.*.vehicle_data.engine_number' => 'required_with:items.*.vehicle_data|string|max:50|unique:ap_vehicles,engine_number',
            'items.*.vehicle_data.ap_models_vn_id' => 'required_with:items.*.vehicle_data|integer|exists:ap_models_vn,id',
            'items.*.vehicle_data.vehicle_color_id' => 'required_with:items.*.vehicle_data|integer|exists:ap_masters,id',
            'items.*.vehicle_data.engine_type_id' => 'required_with:items.*.vehicle_data|integer|exists:ap_masters,id',
            'items.*.vehicle_data.plate' => 'sometimes|nullable|string|max:10',
            'items.*.vehicle_data.ap_vehicle_status_id' => 'sometimes|integer|exists:ap_vehicle_status,id',
            'items.*.vehicle_data.warehouse_id' => 'sometimes|nullable|integer|exists:warehouse,id',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe proporcionar al menos un item (vehículo o equipo)',
            'items.*.item_type.required' => 'El tipo de item es requerido',
            'items.*.item_type.in' => 'El tipo de item debe ser "vehicle" o "equipment"',
            'items.*.vehicle_data.required_if' => 'Los datos del vehículo son requeridos para items de tipo "vehicle"',
            'items.*.vehicle_data.vin.required_with' => 'El VIN es requerido',
            'items.*.vehicle_data.vin.unique' => 'El VIN ya existe en el sistema',
            'items.*.vehicle_data.vin.min' => 'El VIN debe tener exactamente 17 caracteres',
            'items.*.vehicle_data.vin.max' => 'El VIN debe tener exactamente 17 caracteres',
            'items.*.vehicle_data.engine_number.unique' => 'El número de motor ya existe en el sistema',
            'supplier_id.exists' => 'El proveedor seleccionado no existe',
            'ubicacion_id.exists' => 'La ubicación seleccionada no existe',
            'advisor_id.exists' => 'El asesor seleccionado no existe',
            'propietario_id.exists' => 'El propietario seleccionado no existe',
        ];
    }
}
