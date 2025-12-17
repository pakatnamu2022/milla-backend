<?php

namespace App\Http\Requests\gp\gestionhumana\viaticos;

use App\Http\Requests\StoreRequest;

class ValidatePerDiemExpenseRequest extends StoreRequest
{

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    return [
      // No additional fields required
    ];
  }

  /**
   * Get the validated data with additional computed fields
   */
  public function validated($key = null, $default = null)
  {
    $data = parent::validated($key, $default);

    // Set validation fields
    $data['validated'] = true;
    $data['validated_by'] = auth()->id();
    $data['validated_at'] = now();

    return $data;
  }
}
