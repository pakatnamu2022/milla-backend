<?php

namespace App\Http\Resources\ap\configuracionComercial\vehiculo;

use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApModelsVnDynamicsResource extends JsonResource
{
  /**
   * @throws Exception
   */
  public function toArray(Request $request): array
  {
    $classDyn  = $this->classArticle?->dyn_code;
    $marcaDyn  = $this->family?->brand?->dyn_code;
    $familyDyn = $this->family?->code;

    if (!$this->code)     throw new Exception("El modelo ID {$this->id} no tiene código asignado.");
    if (!$classDyn)       throw new Exception("La clase del modelo '{$this->code}' no tiene código Dynamics (dyn_code).");
    if (!$marcaDyn)       throw new Exception("La marca de la familia '{$this->family?->description}' no tiene código Dynamics (dyn_code).");
    if (!$familyDyn)      throw new Exception("La familia del modelo '{$this->code}' no tiene código.");

    return [
      'EmpresaId'           => Company::AP_DYNAMICS,
      'Articulo'            => $this->code,
      'Nombre'              => $this->version,
      'DescripcionBreve'    => $this->version,
      'DescripcionGenerica' => $this->version,
      'Nota'                => '',
      'ClaseArticulo'       => $classDyn,
      'PlanUnidadMedida'    => 'UNIDAD',
      'CostoEstandar'       => 0,
      'CostoActual'         => 0,
      'PesoEnvio'           => 0,
      'Seguimiento'         => 2,
      'Sitio'               => '',
      'UnidadMedidaCompra'  => 'UND',
      'MetodoPrecio'        => 1,
      'UnidadMedidaVenta'   => '',
      'NivelPrecio'         => 'LISTA',
      'GrupoPrecio'         => 'GRUPO',
      'CategoriaArticulo1'  => $marcaDyn,
      'CategoriaArticulo2'  => $familyDyn,
      'CategoriaArticulo3'  => '',
      'CategoriaArticulo4'  => '',
      'CategoriaArticulo5'  => '',
      'CategoriaArticulo6'  => '',
      'CodigoABC'           => 1,
      'TipoArticulo'        => 1,
      'DetraccionId'        => '',
      'CuentaInventario'    => '',
      'CuentaContrapartida' => '',
      'ProcesoEstado'       => 0,
      'ProcesoError'        => '',
    ];
  }
}
