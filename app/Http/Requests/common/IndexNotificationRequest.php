<?php

namespace App\Http\Requests\common;

use App\Http\Requests\IndexRequest;

class IndexNotificationRequest extends IndexRequest
{
  public function rules(): array
  {
    return [
      'is_read'  => 'nullable|boolean',
      'type'     => 'nullable|string|max:100',
      'per_page' => 'nullable|integer|min:1|max:100',
    ];
  }
}
