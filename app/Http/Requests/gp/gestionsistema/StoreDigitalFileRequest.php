<?php

namespace App\Http\Requests\gp\gestionsistema;

use App\Http\Requests\StoreRequest;

class StoreDigitalFileRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'file' => 'required|file|max:10240', // Max 10MB
    ];
  }
}
