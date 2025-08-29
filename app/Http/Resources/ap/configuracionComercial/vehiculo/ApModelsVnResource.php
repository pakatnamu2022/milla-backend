<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApModelsVnResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'codigo' => $this->codigo,
      'version' => $this->version,
      'potencia' => $this->potencia,
      'anio_modelo' => $this->anio_modelo,
      'distancias_ejes' => $this->distancias_ejes,
      'num_ejes' => $this->num_ejes,
      'ancho' => $this->ancho,
      'largo' => $this->largo,
      'altura' => $this->altura,
      'num_asientos' => $this->num_asientos,
      'num_puertas' => $this->num_puertas,
      'peso_neto' => $this->peso_neto,
      'peso_bruto' => $this->peso_bruto,
      'carga_util' => $this->carga_util,
      'cilindrada' => $this->cilindrada,
      'num_cilindros' => $this->num_cilindros,
      'num_pasajeros' => $this->num_pasajeros,
      'num_ruedas' => $this->num_ruedas,
      'precio_distribuidor' => $this->precio_distribuidor,
      'costo_transporte' => $this->cilindrada,
      'otros_importes' => $this->num_cilindros,
      'descuento_compra' => $this->num_pasajeros,
      'importe_igv' => $this->num_ruedas,
      'total_compra_cigv' => $this->precio_distribuidor,
      'total_compra_sigv' => $this->total_compra_sigv,
      'precio_venta' => $this->precio_venta,
      'margen' => $this->margen,
      'marca_id' => $this->family->marca_id,
      'marca' => $this->family->marca->nombre,
      'familia_id' => $this->familia_id,
      'familia' => $this->family->descripcion,
      'clase_id' => $this->clase_id,
      'clase' => $this->classArticle->descripcion,
      'combustible_id' => $this->combustible_id,
      'combustible' => $this->fuelType->descripcion,
      'tipo_vehiculo_id' => $this->tipo_vehiculo_id,
      'tipo_vehiculo' => $this->vehicleType->descripcion,
      'tipo_carroceria_id' => $this->tipo_carroceria_id,
      'tipo_carroceria' => $this->bodyType->descripcion,
      'tipo_traccion_id' => $this->tipo_traccion_id,
      'tipo_traccion' => $this->tractionType->descripcion,
      'transmision_id' => $this->transmision_id,
      'transmision' => $this->vehicleTransmission->descripcion,
      'tipo_moneda_id' => $this->tipo_moneda_id,
      'tipo_moneda' => $this->typeCurrency->nombre,
      'status' => $this->status,
    ];
  }
}
