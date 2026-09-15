<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use App\Models\gp\gestionhumana\reclutamiento\SelectedWorker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeSelectedWorkerLifeStatusRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'estado' => ['required', 'integer', Rule::in([SelectedWorker::STATUS_ALTA, SelectedWorker::STATUS_BAJA])],
      'fecha'  => 'required|date',
      'motivo' => 'nullable|string',
    ];
  }
}
