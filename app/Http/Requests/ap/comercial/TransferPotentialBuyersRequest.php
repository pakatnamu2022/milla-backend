<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class TransferPotentialBuyersRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'from_worker_id'        => 'required|integer|exists:rrhh_persona,id',
      'to_worker_id'          => 'required|integer|exists:rrhh_persona,id|different:from_worker_id',
      'potential_buyer_ids'   => 'sometimes|array',
      'potential_buyer_ids.*' => 'integer|exists:potential_buyers,id',
    ];
  }

  public function messages(): array
  {
    return [
      'from_worker_id.required'    => 'El asesor origen es obligatorio.',
      'from_worker_id.exists'      => 'El asesor origen no es válido.',
      'to_worker_id.required'      => 'El asesor destino es obligatorio.',
      'to_worker_id.exists'        => 'El asesor destino no es válido.',
      'to_worker_id.different'     => 'El asesor destino debe ser diferente al asesor origen.',
      'potential_buyer_ids.array'  => 'Los IDs de potential buyers deben ser un arreglo.',
      'potential_buyer_ids.*.exists' => 'Uno o más potential buyers seleccionados no existen.',
    ];
  }
}
