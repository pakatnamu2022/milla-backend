<?php

namespace App\Http\Requests\gp\gestionhumana;

use App\Http\Requests\StoreRequest;
use App\Models\gp\gestionhumana\AccountantDistrictAssignment;

class StoreAccountantDistrictAssignmentRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'worker_id' => ['required', 'integer', 'exists:rrhh_persona,id'],
      'district_id' => ['required', 'integer', 'exists:district,id'],
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $workerId = $this->input('worker_id');
      $districtId = $this->input('district_id');

      $exists = AccountantDistrictAssignment::where('worker_id', $workerId)->where('district_id', $districtId)->exists();

      if ($exists) {
        $validator->errors()->add('worker_id', 'El trabajador ya está asignado a este distrito.');
      }
    });
  }

  public function attributes(): array
  {
    return [
      'worker_id' => 'Trabajador',
      'district_id' => 'Distrito',
    ];
  }
}
