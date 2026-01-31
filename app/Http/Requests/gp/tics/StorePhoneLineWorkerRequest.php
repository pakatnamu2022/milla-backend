<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class StorePhoneLineWorkerRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'phone_line_id' => 'required|exists:phone_line,id',
      'worker_id' => 'required|exists:rrhh_persona,id',
    ];
  }
}
