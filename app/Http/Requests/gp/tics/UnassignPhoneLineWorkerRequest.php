<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class UnassignPhoneLineWorkerRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'unassigned_at' => 'nullable|date',
    ];
  }
}
