<?php

namespace App\Http\Requests\ap\comercial;

use Illuminate\Foundation\Http\FormRequest;

class IndexVehiclesRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'page' => 'sometimes|integer|min:1',
      'per_page' => 'sometimes|integer|min:1|max:100',
      'search' => 'sometimes|string|max:255',
      'sort' => 'sometimes|string|in:vin,year,engine_number,created_at',
      'direction' => 'sometimes|string|in:asc,desc',

      // Filtros
      'ap_models_vn_id' => 'sometimes|integer|exists:ap_models_vn,id',
      'ap_vehicle_status_id' => 'sometimes|integer|exists:ap_vehicle_status,id',
      'vehicle_color_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
      'engine_type_id' => 'sometimes|integer|exists:ap_commercial_masters,id',
      'sede_id' => 'sometimes|integer|exists:sede,id',
      'warehouse_physical_id' => 'sometimes|integer|exists:warehouse,id',
      'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 2),
    ];
  }
}
