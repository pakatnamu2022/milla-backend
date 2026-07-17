<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;

class UploadWorkOrderDocumentsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'files' => 'required|array|min:1|max:3',
      'files.*' => 'required|file|mimes:pdf|max:10240', // Max 10MB c/u
    ];
  }
}
