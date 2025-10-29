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
    // Acceder al modelo a través de: PurchaseOrder -> vehicleMovement -> vehicle -> model
    $modelId = $this->vehicleMovement?->vehicle?->ap_models_vn_id;
    $modelCode = $modelId ? ApModelsVn::find($modelId)?->code : null;
    $warehouseCode = Warehouse::find($this->warehouse_id)?->dyn_code;

    if (!$modelCode) throw new Exception("Model code not found for vehicle purchase order {$this->id}");
    if (!$warehouseCode) throw new Exception("Warehouse code not found for vehicle purchase order {$this->id}");

    return [
      'EmpresaId' => Company::AP_DYNAMICS,
      'OrdenCompraId' => $this->number,
      'Linea' => 1,
      'ArticuloId' => $modelCode,
      'SitioId' => $warehouseCode,
      'UnidadMedidaId' => 'UND',
      'Cantidad' => 1,
      'CostoUnitario' => $this->subtotal,
      'CuentaNumeroInventario' => '',
      'CodigoDimension1' => '',
      'CodigoDimension2' => '',
      'CodigoDimension3' => '',
      'CodigoDimension4' => '',
      'CodigoDimension5' => '',
      'CodigoDimension6' => '',
      'CodigoDimension7' => '',
      'CodigoDimension8' => '',
      'CodigoDimension9' => '',
      'CodigoDimension10' => ''
    ];
  }
}
