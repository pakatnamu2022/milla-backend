<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApMasters;
use Illuminate\Validation\Rule;

class StoreApCampaignRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'area_id'        => [
        'required',
        'integer',
        Rule::exists('ap_masters', 'id')->where(function ($query) {
          $query->where('type', ApMasters::TYPE_AREA);
        }),
        Rule::in(ApMasters::where('type', ApMasters::TYPE_AREA)->pluck('id')->toArray()),
      ],
      'code'           => 'required|string|max:50|unique:ap_campaigns,code',
      'name'           => 'required|string|max:150',
      'description'    => 'nullable|string',
      'start_date'     => 'required|date',
      'end_date'       => 'required|date|after_or_equal:start_date',
      'discount_type'  => 'required|string|in:fixed,percentage',
      'discount_value' => 'required|numeric|min:0',
      'status'         => 'nullable|boolean',
    ];
  }

  public function attributes(): array
  {
    return [
      'area_id'        => 'área',
      'code'           => 'código',
      'name'           => 'nombre',
      'description'    => 'descripción',
      'start_date'     => 'fecha de inicio',
      'end_date'       => 'fecha de fin',
      'discount_type'  => 'tipo de descuento',
      'discount_value' => 'valor de descuento',
      'status'         => 'estado',
    ];
  }
}
