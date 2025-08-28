<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApModelsVn extends Model
{
  use SoftDeletes;

  protected $table = 'ap_models_vn';

  protected $fillable = [
    'codigo',
    'version',
    'potencia',
    'anio_modelo',
    'distancias_ejes',
    'num_ejes',
    'ancho',
    'largo',
    'altura',
    'num_asientos',
    'num_puertas',
    'peso_neto',
    'peso_bruto',
    'carga_util',
    'cilindrada',
    'num_cilindros',
    'num_pasajeros',
    'num_ruedas',
    'precio_distribuidor',
    'costo_transporte',
    'otros_importes',
    'descuento_compra',
    'importe_igv',
    'total_compra_cigv',
    'total_compra_sigv',
    'precio_venta',
    'margen',
    'familia_id',
    'clase_id',
    'combustible_id',
    'tipo_vehiculo_id',
    'tipo_carroceria_id',
    'tipo_traccion_id',
    'transmision_id',
    'tipo_moneda_id',
  ];

  const filters = [
    'search' => ['codigo', 'version'],
    'status' => '=',
  ];

  const sorts = [
    'codigo',
    'version',
  ];

  public function setCodigoAttribute($value)
  {
    $this->attributes['codigo'] = Str::upper(Str::ascii($value));
  }

  public function setVersionAttribute($value)
  {
    $this->attributes['version'] = Str::upper(Str::ascii($value));
  }

  public function setPotenciaAttribute($value)
  {
    $this->attributes['potencia'] = Str::upper(Str::ascii($value));
  }

  public function setDistanciaEjesAttribute($value)
  {
    $this->attributes['distancias_ejes'] = Str::upper(Str::ascii($value));
  }

  public function setNumEjesAttribute($value)
  {
    $this->attributes['num_ejes'] = Str::upper(Str::ascii($value));
  }

  public function setAnchoAttribute($value)
  {
    $this->attributes['ancho'] = Str::upper(Str::ascii($value));
  }

  public function setLargoAttribute($value)
  {
    $this->attributes['largo'] = Str::upper(Str::ascii($value));
  }

  public function setAlturaAttribute($value)
  {
    $this->attributes['altura'] = Str::upper(Str::ascii($value));
  }

  public function setNumAsientosAttribute($value)
  {
    $this->attributes['num_asientos'] = Str::upper(Str::ascii($value));
  }

  public function setNumPuertasAttribute($value)
  {
    $this->attributes['num_puertas'] = Str::upper(Str::ascii($value));
  }

  public function setPesoNetoAttribute($value)
  {
    $this->attributes['peso_neto'] = Str::upper(Str::ascii($value));
  }

  public function setPesoBrutoAttribute($value)
  {
    $this->attributes['peso_bruto'] = Str::upper(Str::ascii($value));
  }

  public function setCargaUtilAttribute($value)
  {
    $this->attributes['carga_util'] = Str::upper(Str::ascii($value));
  }

  public function setCilindradaAttribute($value)
  {
    $this->attributes['cilindrada'] = Str::upper(Str::ascii($value));
  }

  public function setNumCilindrosAttribute($value)
  {
    $this->attributes['num_cilindros'] = Str::upper(Str::ascii($value));
  }

  public function setNumPasajerosAttribute($value)
  {
    $this->attributes['num_pasajeros'] = Str::upper(Str::ascii($value));
  }

  public function setNumRuedasAttribute($value)
  {
    $this->attributes['num_ruedas'] = Str::upper(Str::ascii($value));
  }
}
