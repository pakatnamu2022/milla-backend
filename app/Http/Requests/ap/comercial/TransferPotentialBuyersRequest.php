<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;

class TransferPotentialBuyersRequest extends StoreRequest
{
  public function rules(): array
  {
    $transferingAll = empty($this->input('potential_buyer_ids'));

    return [
      'from_worker_id'        => 'required|integer|exists:rrhh_persona,id',
      'to_worker_id'          => 'required|integer|exists:rrhh_persona,id|different:from_worker_id',
      'potential_buyer_ids'   => 'sometimes|array',
      'potential_buyer_ids.*' => 'integer|exists:potential_buyers,id',
      'date_from'             => ($transferingAll ? 'required' : 'nullable') . '|date',
      'date_to'               => ($transferingAll ? 'required' : 'nullable') . '|date|after_or_equal:date_from',
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
      'date_from.required'         => 'La fecha de inicio es obligatoria cuando se transfieren todos los registros.',
      'date_from.date'             => 'La fecha de inicio no es válida.',
      'date_to.required'           => 'La fecha de fin es obligatoria cuando se transfieren todos los registros.',
      'date_to.date'               => 'La fecha de fin no es válida.',
      'date_to.after_or_equal'     => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
    ];
  }
}
