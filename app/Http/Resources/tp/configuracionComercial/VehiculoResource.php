<?php

namespace App\Http\Resources\tp\configuracionComercial;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_vehiculo_id' => $this->tipo_vehiculo_id,
            'tipo_vehiculo_descripcion' => $this->tipo_vehiculo ? $this->tipo_vehiculo->descripcion : null,
            'placa' => $this->placa,
            'modelo' => $this->modelo,
            'marca' => $this->marca,
            'serie_chasis' => $this->serie_chasis,
            'motor' => $this->motor,
            'num_mtc' => $this->num_mtc,
            'tarjeta_circulacion' => $this->tarjeta_circulacion,
            'kilometraje' => $this->kilometraje ? floatval($this->kilometraje) : null,
            'capacidad_bruta' => $this->capacidad_bruta ? floatval($this->capacidad_bruta) : null,
            'reserva' => $this->reserva ? floatval($this->reserva) : null,
            'capacidad_util' => $this->capacidad_util ? floatval($this->capacidad_util) : null,
            'km_mantenimiento' => $this->km_mantenimiento ? floatval($this->km_mantenimiento) : null,
            'capacidad' => $this->capacidad,
            'longitud' => $this->longitud,
            'latitud' => $this->latitud,
            'isDriving' => $this->isDriving,
            'coordenadas' => $this->coordenadas,
            'ubicacion' => $this->ubicacion,
            'region' => $this->region,
            'city' => $this->city,
            'country' => $this->country,
            
            'tercero' => $this->tercero,
            'vehiculo_status' => $this->vehiculo_status,
            'status_geotab_km' => $this->status_geotab_km,
            'status_matpel' => $this->status_matpel,
            'status_ubicacion' => $this->status_ubicacion,
            'sede_id' => $this->sede_id,
            'sede_abreviatura' => $this->sede ? $this->sede->abreviatura : null,
            'write_id' => $this->write_id,
            'status_deleted' => $this->status_deleted,
            'ult_manteniento_realizado' => $this->ult_manteniento_realizado,
            'geotab_serial' => $this->geotab_serial,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}