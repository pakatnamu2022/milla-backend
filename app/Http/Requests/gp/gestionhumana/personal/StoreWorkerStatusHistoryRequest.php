<?php

namespace App\Http\Requests\gp\gestionhumana\personal;

use App\Models\gp\gestionhumana\personal\WorkerStatusHistory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkerStatusHistoryRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'empleado_id' => 'required|integer|exists:rrhh_persona,id',
      'fecha'       => 'required|date_format:Y-m-d',
      'estado'      => ['required', 'integer', Rule::in([WorkerStatusHistory::STATUS_ACTIVE, WorkerStatusHistory::STATUS_TERMINATED])],
      'motivo'      => 'nullable|string',
      'sucursal_id' => 'nullable|integer|exists:config_sede,id',
    ];
  }
}
