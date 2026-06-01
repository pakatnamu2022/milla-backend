<?php

namespace App\Http\Services\tp\comercial;

use App\Http\Services\BaseService;
use App\Models\tp\comercial\DriverLocationConfiguration;

class DriverLocationConfigurationService extends BaseService
{
    public function list()
    {
        $configs = DriverLocationConfiguration::orderBy('key')->get();

        return response()->json([
            'success' => true,
            'data' => $configs
        ]);
    }

    public function store($data)
    {
        $config = DriverLocationConfiguration::updateOrCreate(
            ['key' => $data['key']],
            [
                'value' => $data['value'],
                'description' => $data['description'] ?? null
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada exitosamente',
            'data' => $config
        ]);
    }

    public function show($id)
    {
        $config = DriverLocationConfiguration::where('key', $id)->first();

        if(!$config){
            return response()->json([
                'success' => false,
                'message' => 'Configuración no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    public function update($data)
    {
        $config = DriverLocationConfiguration::where('key', $data['key'])->firstOrFail();

        $config->update([
            'value' => $data['value'],
            'description' => $data['description'] ?? null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada exitosamente',
            'data' => $config
        ]);
    }

    public function destroy($key)
    {
        $config = DriverLocationConfiguration::where('key', $key)->firstOrFail();
        $config->delete();

        return response()->json([
            'success' => true,
            'message' => 'Configuración eliminada exitosamente'
        ]);
    }

    public function getPublic($key)
    {
        $value = DriverLocationConfiguration::get($key);

        return [
            'success' => true,
            'key' => $key,
            'value' => $value
        ];
    }
}