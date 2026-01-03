<?php

namespace App\Http\Resources\tp\comercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelControlResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = (array) $this->resource;
        $mappedStatus = $this->getMappedStatus($data['idestado'] ?? $data['estado'] ?? 1);
        $initialKm = $this->convertToNumber($data['km_inicio'] ?? null);
        $finalKm = $this->convertToNumber($data['km_fin'] ?? null);
        $totalKmBackend = $this->convertToNumber($data['totalKm'] ?? null);
        $fuelAmount = $this->convertToNumber($data['fuelAmount'] ?? null);
        $totalKmCalculated = $totalKmBackend;
        if (!$totalKmCalculated && $initialKm !== null && $finalKm !== null) {
            $totalKmCalculated = $finalKm - $initialKm;
        }
        $kmFactor = null;
        if ($fuelAmount !== null && $totalKmCalculated !== null && $totalKmCalculated > 0) {
            $kmFactor = $fuelAmount / $totalKmCalculated;
        }
        $producto = $this->getProductFromItems($data['items'] ?? []);

        $photos = isset($data['photos']) && is_array($data['photos'])
                        ? TpTravelPhotoResource::collection(collect($data['photos']))
                        : collect([]);
        $driverRecords = isset($data['driver_records']) && is_array($data['driver_records'])
                        ? DriverTravelRecordResource::collection(collect($data['driver_records']))
                        : collect([]);
        
        return [
            'id' => $data['id'] ?? null,
            'codigo' => $data['codigo'] ?? 'TPV' . str_pad($data['id'] ?? 0, 8, '0', STR_PAD_LEFT),
            'tripNumber' => $data['codigo'] ?? 'TPV' . str_pad($data['id'] ?? 0, 8, '0', STR_PAD_LEFT),
            'estado' => $data['idestado'] ?? $data['estado'] ?? null,
            'idestado' => $data['idestado'] ?? $data['estado'] ?? null,
            'estado_descripcion' => $data['estado_descripcion'] ?? null,
            'estado_color' => $data['estado_color'] ?? null,
            'estado_porcentaje' => $data['estado_porcentaje'] ?? null,
            'estado_norden' => $data['estado_norden'] ?? null,
            'status' => $mappedStatus,
            'conductor_id' => $data['conductor_id'] ?? null,
            'conductor_nombre' => $data['conductor_nombre'] ?? null,
            'conductor_documento' => $data['conductor_documento'] ?? null,
            'conductor_telefono' => $data['conductor_telefono'] ?? null,
            'conductor_email' => $data['conductor_email'] ?? null,
            'tracto_id' => $data['tracto_id'] ?? null,
            'tracto_placa' => $data['tracto_placa'] ?? null,
            'tracto_marca' => $data['tracto_marca'] ?? null,
            'tracto_modelo' => $data['tracto_modelo'] ?? null,
            'carreta_id' => $data['carreta_id'] ?? null,
            'carreta_placa' => $data['carreta_placa'] ?? null,
            'plate' => $data['tracto_placa'] ?? null,
            'cliente_id' => $data['idcliente'] ?? null,
            'cliente_nombre' => $data['cliente_nombre'] ?? null,
            'cliente_ruc' => $data['cliente_ruc'] ?? null,
            'client' => $data['cliente_nombre'] ?? null,
            'ruta' => $data['ruta'] ?? 'Sin ruta',
            'route' => $data['ruta'] ?? 'Sin ruta',
            'producto' => $producto,
            'ubic_cabecera' => $data['ubic_cabecera'] ?? $data['ubicacion'] ?? null,
            'ubicacion' => $data['ubic_cabecera'] ?? $data['ubicacion'] ?? null,
            'km_inicio' => $initialKm,
            'km_fin' => $finalKm,
            'initialKm' => $initialKm,
            'finalKm' => $finalKm,
            'totalKm' => $totalKmCalculated,
            'totalHours' => $this->convertToNumber($data['totalHours'] ?? null),
            'tonnage' => $this->convertToNumber($data['tonnage'] ?? null),
            'fuelAmount' => $fuelAmount,
            'fuelGallons' => $this->convertToNumber($data['fuelGallons'] ?? null),
            'fuelAmount' => $fuelAmount,
            'factorKm' => $kmFactor,
            'fecha_viaje' => $data['fecha_viaje'] ?? null,
            'fecha_estado' => $data['fecha_estado'] ?? $data['fecha_viaje'] ?? null,
            'startTime' => $data['fecha_viaje'] ?? null,
            'endTime' => $data['fecha_estado'] ?? $data['fecha_viaje'] ?? null,
            'observacion_comercial' => $data['observacion_comercial'] ?? null,
            'proxima_prog' => $data['proxima_prog'] ?? null,
            'produccion' => $data['produccion'] ?? null,
            'condiciones' => $data['condiciones'] ?? null,
            'nliquidacion' => $data['nliquidacion'] ?? null,
            'cantidad' => $data['cantidad'] ?? null,
            'items' => $this->formatItems($data['items'] ?? []),
            'driver_records' => $driverRecords,
            'gastos' => $data['gastos'] ?? [],
            'photos' => $photos,
            'proximocod' => $data['proximocod'] ?? '-',
            'proximoruta' => $data['proximoruta'] ?? '-',
            'pendientecond' => $data['pendientecond'] ?? 0,
            'pendientetracto' => $data['pendientetracto'] ?? 0,
            'pendientecarreta' => $data['pendientecarreta'] ?? 0,
            'driver' => [
                'id' => $data['conductor_id'] ?? null,
                'name' => $data['conductor_nombre'] ?? null,
                'license_number' => null, 
                'phone' => $data['conductor_telefono'] ?? null,
                'email' => $data['conductor_email'] ?? null
            ],
            'vehicle' => [
                'id' => $data['tracto_id'] ?? null,
                'placa' => $data['tracto_placa'] ?? null,
                'marca' => $data['tracto_marca'] ?? null,
                'modelo' => $data['tracto_modelo'] ?? null
            ],
            'estado_info' => [
                'descripcion' => $data['estado_descripcion'] ?? null,
                'color' => $data['estado_color'] ?? null,
                'porcentaje' => $data['estado_porcentaje'] ?? null,
                'norden' => $data['estado_norden'] ?? null
            ],
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'user_id' => $data['user_id'] ?? $data['conductor_id'] ?? null,
            'previousFinalKm' => $data['previousFinalKm'] ?? 0,
            'hasEmptySegment' => $data['hasEmptySegment'] ?? false,
            'obs_cistas' => $data['obs_cistas'] ?? null,
            'obs_combustible' => $data['obs_combustible'] ?? null,
            'obs_adic_1' => $data['obs_adic_1'] ?? null,
            'obs_adic_2' => $data['obs_adic_2'] ?? null,
            'obs_adic_3' => $data['obs_adic_3'] ?? null,
            'obs_adic_4' => $data['obs_adic_4'] ?? null,
            'obs_adic_5' => $data['obs_adic_5'] ?? null,
            'obs_adic_6' => $data['obs_adic_6'] ?? null,
            'obs_adic_7' => $data['obs_adic_7'] ?? null,
            'obs_adic_8' => $data['obs_adic_8'] ?? null,
            
           
            'statistics' => [
                'total_items' => count($data['items'] ?? []),
                'total_driver_records' => count($data['driver_records'] ?? []),
                'total_expenses' => count($data['gastos'] ?? []),
                'total_photos' => isset($data['photos']) ? count($data['photos']) : 0,
                'has_fuel_expense' => ($fuelAmount ?? 0) > 0
            ],
            
          
            'metadata' => [
                'is_active' => !in_array($data['idestado'] ?? $data['estado'] ?? null, [9, 10]),
                'can_start' => ($data['idestado'] ?? $data['estado'] ?? null) == 1,
                'can_end' => in_array($data['idestado'] ?? $data['estado'] ?? null, [3, 4, 5, 6, 7]),
                'can_add_fuel' => in_array($data['idestado'] ?? $data['estado'] ?? null, [8, 3, 4, 5, 6, 7]),
                'can_upload_photos' => !in_array($data['idestado'] ?? $data['estado'] ?? null, [9, 10])
            ]
        ];
    }

    private function formatItems(array $items): array
    {
        return array_map(function ($item) {
            $itemArray = (array) $item;
            return [
                'id' => $itemArray['id'] ?? null,
                'despacho_id' => $itemArray['despacho_id'] ?? null,
                'cantidad' => $itemArray['cantidad'] ?? 0,
                'idproducto' => $itemArray['idproducto'] ?? null,
                'idorigen' => $itemArray['idorigen'] ?? null,
                'iddestino' => $itemArray['iddestino'] ?? null,
                'producto_descripcion' => $itemArray['producto_descripcion'] ?? null,
                'origen_descripcion' => $itemArray['origen_descripcion'] ?? null,
                'destino_descripcion' => $itemArray['destino_descripcion'] ?? null
            ];
        }, $items);
    }
    
    private function getMappedStatus($estado): string
    {
        $map = [
            1 => 'pending',
            2 => 'pending',
            3 => 'in_progress',
            4 => 'in_progress',
            5 => 'in_progress',
            6 => 'in_progress',
            7 => 'in_progress',
            8 => 'fuel_pending',
            9 => 'completed',
            10 => 'cancelled',
            11 => 'completed'
        ];
        
        return $map[$estado] ?? 'pending';
    }
    
    private function convertToNumber($value): ?float
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        
        $num = floatval($value);
        return is_nan($num) ? null : $num;
    }
    
    private function getProductFromItems(array $items): string
    {
        if (empty($items)) {
            return 'Sin producto';
        }
        
        $firstItem = (array) ($items[0] ?? []);
        return $firstItem['producto_descripcion'] ?? 'Sin producto';
    }
    
    public function with($request)
    {
        return [
            'meta' => [
                'version' => '1.0',
                'resource_type' => 'travel_control',
                'compatible_with' => 'travelControl.interface',
                'timestamp' => now()->toISOString()
            ]
        ];
    }
}