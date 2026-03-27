<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\taller\TypePlanningWorkOrder;
use Illuminate\Validation\Rule;

class UpdateTypePlanningWorkOrderRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'code' => [
        'sometimes',
        'required',
        'string',
        Rule::unique('type_planning_work_order', 'code')
          ->whereNull('deleted_at')
          ->ignore($this->route('typePlanningWorkOrder')),
      ],
      'description' => 'sometimes|string|max:255',
      'validate_receipt' => 'sometimes|boolean',
      'validate_labor' => 'sometimes|boolean',
      'type_document' => 'sometimes|string|in:' . TypePlanningWorkOrder::INTERNA . ',' . TypePlanningWorkOrder::PAYMENT_RECEIPTS,
      'status' => 'sometimes|boolean',
    ];
  }

  public function messages(): array
  {
    return [
      'code.required' => 'El campo código es obligatorio.',
      'code.string' => 'El código debe ser una cadena de texto.',
      'code.unique' => 'El código ingresado ya existe en los registros.',
      'description.required' => 'El campo descripción es obligatorio.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',
      'description.string' => 'La descripción debe ser una cadena de texto.',
      'validate_receipt.required' => 'El campo validar recepción es obligatorio.',
      'validate_receipt.boolean' => 'El campo validar recepción debe ser un valor booleano.',
      'validate_labor.required' => 'El campo validar mano de obra es obligatorio.',
      'validate_labor.boolean' => 'El campo validar mano de obra debe ser un valor booleano.',
      'type_document.required' => 'El campo tipo documento es obligatorio.',
      'type_document.string' => 'El tipo documento debe ser una cadena de texto.',
      'type_document.in' => 'El tipo documento seleccionado no es válido. Los valores permitidos son: INTERNA, PAYMENT_RECEIPTS.',
    ];
  }
}
