<?php

namespace App\Http\Requests\gp\gestionhumana\reclutamiento;

use Illuminate\Foundation\Http\FormRequest;

class RehireSelectedWorkerRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'proceso_postulacion_id' => 'required|integer|exists:rrhh_proceso_postulacion,id',
    ];
  }
}
