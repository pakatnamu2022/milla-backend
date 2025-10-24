<?php

namespace App\Http\Resources\ap\comercial;

use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiclePurchaseOrderDetailDynamicsResource extends JsonResource
{
  /**
   * @throws Exception
   */
  public function toArray(Request $request): array
  {

    return [
      'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
      'OrdenCompraId' => fn($data) => $data['number'],
      'Linea' => 1, // TODO: Aquí deberías implementar la lógica para obtener la línea correcta
      'ArticuloId' => fn($data) => ApModelsVn::find($data['ap_models_vn_id'])->code,
      'SitioId' => fn($data) => Warehouse::find($data['warehouse_id'])->dyn_code,
      'UnidadMedidaId' => fn($data) => 'UND', // TODO: Asumiendo que siempre es 'UND', ajusta según sea necesario
      'Cantidad' => 1, // TODO: Aquí deberías implementar la lógica para obtener la cantidad correcta
      'CostoUnitario' => fn($data) => $data['subtotal'],
      'CuentaNumeroInventario' => fn($data) => '',
      'CodigoDimension1' => fn($data) => '',
      'CodigoDimension2' => fn($data) => '',
      'CodigoDimension3' => fn($data) => '',
      'CodigoDimension4' => fn($data) => '',
      'CodigoDimension5' => fn($data) => '',
      'CodigoDimension6' => fn($data) => '',
      'CodigoDimension7' => fn($data) => '',
      'CodigoDimension8' => fn($data) => '',
      'CodigoDimension9' => fn($data) => '',
      'CodigoDimension10' => fn($data) => ''
    ];
  }
}
