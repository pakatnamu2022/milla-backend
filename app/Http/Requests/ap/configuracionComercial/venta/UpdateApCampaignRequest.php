<?php

namespace App\Http\Requests\ap\configuracionComercial\venta;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class UpdateApCampaignRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'area_id'        => 'nullable|integer|exists:ap_masters,id',
      'code'           => [
        'nullable',
        'string',
        'max:50',
        Rule::unique('ap_campaigns', 'code')
          ->whereNull('deleted_at')
          ->ignore($this->route('campaign')),
      ],
      'name'           => 'nullable|string|max:150',
      'description'    => 'nullable|string',
      'start_date'     => 'nullable|date',
      'end_date'       => 'nullable|date|after_or_equal:start_date',
      'discount_type'  => 'nullable|string|in:fixed,percentage',
      'discount_value' => 'nullable|numeric|min:0',
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
