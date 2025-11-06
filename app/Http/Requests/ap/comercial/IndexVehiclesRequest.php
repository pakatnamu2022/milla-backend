<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\IndexRequest;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Illuminate\Validation\Rule;

class IndexVehiclesRequest extends IndexRequest
{

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'ap_vehicle_status_id' => 'array',
      'ap_vehicle_status_id.*' => [
        'integer',
        Rule::exists('ap_vehicle_status', 'id'),
        Rule::in([ApVehicleStatus::VEHICULO_EN_TRAVESIA, ApVehicleStatus::INVENTARIO_VN])
      ],
//      'search' => [
//        'integer',
//        Rule::exists('ap_vehicle_status', 'id'),
//        Rule::in([ApVehicleStatus::VEHICULO_EN_TRAVESIA, ApVehicleStatus::PEDIDO_VN])
//      ],
      'has_purchase_request_quote' => 'nullable|boolean|in:0,1',
      'ap_models_vn_id' => [
        'integer',
        Rule::in(ApModelsVn::all()->pluck('id')->toArray()),
        Rule::exists('ap_models_vn', 'id')->whereNull('deleted_at')->where('status', 1)
      ]
    ];
  }
}
