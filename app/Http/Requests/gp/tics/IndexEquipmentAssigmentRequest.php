<?php

namespace App\Http\Requests\gp\tics;

use Illuminate\Foundation\Http\FormRequest;

class IndexEquipmentAssigmentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [];
  }
}
