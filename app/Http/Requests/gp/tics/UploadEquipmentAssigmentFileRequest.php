<?php

namespace App\Http\Requests\gp\tics;

use App\Http\Requests\StoreRequest;

class UploadEquipmentAssigmentFileRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
      'type' => 'required|in:assignment,unassignment',
    ];
  }
}
