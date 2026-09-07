<?php

namespace App\Http\Requests\ap\postventa\taller;

use App\Http\Requests\StoreRequest;
use App\Models\ap\postventa\taller\ApOrderQuotationDetails;
use App\Models\ap\postventa\taller\ApOrderQuotations;

class ReorderApOrderQuotationDetailsRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'items' => [
        'required',
        'array',
        'min:1',
      ],
      'items.*.id' => [
        'required',
        'integer',
        'exists:ap_order_quotation_details,id,deleted_at,NULL',
      ],
      'items.*.order' => [
        'required',
        'integer',
        'min:0',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      'items.required' => 'El listado de items es obligatorio.',
      'items.array' => 'El listado de items debe ser un arreglo.',
      'items.min' => 'Debe proporcionar al menos un item para reordenar.',

      'items.*.id.required' => 'El ID del item es obligatorio.',
      'items.*.id.integer' => 'El ID del item debe ser un número entero.',
      'items.*.id.exists' => 'Uno o más items no existen o fueron eliminados.',

      'items.*.order.required' => 'El orden del item es obligatorio.',
      'items.*.order.integer' => 'El orden del item debe ser un número entero.',
      'items.*.order.min' => 'El orden del item no puede ser negativo.',
    ];
  }

  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      $quotationId = $this->route('id');
      $items = $this->input('items', []);

      if (empty($items)) {
        return;
      }

      // Verificar que la cotización existe
      $quotation = ApOrderQuotations::find($quotationId);
      if (!$quotation) {
        $validator->errors()->add('items', 'La cotización no existe.');
        return;
      }

      // Obtener IDs de los items del request
      $itemIds = collect($items)->pluck('id')->all();

      // Verificar que no haya duplicados
      if (count($itemIds) !== count(array_unique($itemIds))) {
        $validator->errors()->add('items', 'No se permiten items duplicados en el listado.');
        return;
      }

      // Verificar que todos los items pertenezcan a esta cotización
      $detailsCount = ApOrderQuotationDetails::whereIn('id', $itemIds)
        ->where('order_quotation_id', $quotationId)
        ->count();

      if ($detailsCount !== count($itemIds)) {
        $validator->errors()->add(
          'items',
          'Todos los items deben pertenecer a la cotización especificada.'
        );
      }
    });
  }
}