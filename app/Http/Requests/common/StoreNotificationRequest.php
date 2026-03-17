<?php

namespace App\Http\Requests\common;

use App\Http\Requests\StoreRequest;

class StoreNotificationRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'title'    => 'required|string|max:255',
      'body'     => 'required|string',
      'type'     => 'required|string|max:100',
      'data'     => 'nullable|array',
      'user_ids' => 'required|array|min:1',
      'user_ids.*' => 'integer|exists:usr_users,id',
    ];
  }
}
