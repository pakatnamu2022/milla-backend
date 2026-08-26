<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class StoreApCampaignScheduleRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'sede_id' => 'required|integer|exists:config_sede,id',
      'worker_id' => 'required|integer|exists:rrhh_persona,id',
      'dates' => 'required|array|min:1',
      'dates.*' => 'required|date|after_or_equal:' . now()->startOfMonth()->toDateString(),
    ];
  }

  public function messages(): array
  {
    return [
      'sede_id.required' => 'La sede es requerida.',
      'sede_id.exists' => 'La sede seleccionada no existe.',
      'worker_id.required' => 'El técnico es requerido.',
      'worker_id.exists' => 'El técnico seleccionado no existe.',
      'dates.required' => 'Debe seleccionar al menos una fecha.',
      'dates.array' => 'Las fechas deben ser un arreglo.',
      'dates.min' => 'Debe seleccionar al menos una fecha.',
      'dates.*.required' => 'Cada fecha es requerida.',
      'dates.*.date' => 'Cada fecha debe ser una fecha válida.',
      'dates.*.after_or_equal' => 'No se pueden registrar fechas de meses pasados. Solo puede modificar el mes actual o futuros.',
    ];
  }
}
