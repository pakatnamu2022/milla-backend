<?php

namespace App\Http\Requests\ap\comercial;

use App\Http\Requests\StoreRequest;
use App\Models\ap\comercial\PurchaseRequestQuoteAdjustmentItem;
use Illuminate\Contracts\Validation\Validator;

class StorePurchaseRequestQuoteAdjustmentRequestRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'purchase_request_quote_id' => ['required', 'integer', 'exists:purchase_request_quote,id'],
      'reason' => ['nullable', 'string', 'max:1000'],
      'items' => ['required', 'array', 'min:1'],
      'items.*.action' => ['required', 'string', 'in:' . implode(',', PurchaseRequestQuoteAdjustmentItem::getActions())],
      'items.*.item_type' => ['nullable', 'string', 'in:' . implode(',', PurchaseRequestQuoteAdjustmentItem::getItemTypes())],

      // --- Bono / descuento --- (obligatoriedad afinada en withValidator)
      'items.*.discount_coupon_id' => ['nullable', 'integer', 'exists:discount_coupons,id'],
      'items.*.concept_code_id' => ['nullable', 'integer', 'exists:ap_masters,id'],
      'items.*.type' => ['nullable', 'string', 'in:FIJO,PORCENTAJE'],
      'items.*.value' => ['nullable', 'numeric', 'min:0'],
      'items.*.has_retention' => ['nullable', 'boolean'],

      // --- Obsequio (accesorio type = OBSEQUIO) ---
      'items.*.accessory_detail_id' => ['nullable', 'integer', 'exists:details_approved_accessories_quote,id'],
      'items.*.approved_accessory_id' => ['nullable', 'integer', 'exists:approved_accessories,id'],
      'items.*.quantity' => ['nullable', 'integer', 'min:1'],
      'items.*.additional_price' => ['nullable', 'numeric', 'min:0'],
    ];
  }

  /**
   * Afina la obligatoriedad de cada línea según su item_type real, porque las
   * reglas anidadas required_if/required_unless no se combinan bien cuando el
   * request mezcla líneas de bono y de obsequio.
   */
  public function withValidator(Validator $validator)
  {
    $validator->after(function ($v) {
      $gift = PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_GIFT;
      $delete = PurchaseRequestQuoteAdjustmentItem::ACTION_DELETE;

      foreach ((array)$this->input('items', []) as $i => $item) {
        $itemType = $item['item_type'] ?? PurchaseRequestQuoteAdjustmentItem::ITEM_TYPE_BONUS_DISCOUNT;
        $action = $item['action'] ?? null;

        if ($itemType === $gift) {
          if ($action === 'create' && empty($item['approved_accessory_id'])) {
            $v->errors()->add("items.$i.approved_accessory_id", 'Debe seleccionar el accesorio para el obsequio.');
          }
          if (in_array($action, ['update', $delete], true) && empty($item['accessory_detail_id'])) {
            $v->errors()->add("items.$i.accessory_detail_id", 'Debe indicar el obsequio existente a modificar o eliminar.');
          }
          if ($action !== $delete && (int)($item['quantity'] ?? 0) < 1) {
            $v->errors()->add("items.$i.quantity", 'La cantidad del obsequio debe ser al menos 1.');
          }
        } else {
          if ($action !== $delete) {
            if (empty($item['concept_code_id'])) {
              $v->errors()->add("items.$i.concept_code_id", 'El concepto es obligatorio para agregar o editar un bono/descuento.');
            }
            if (empty($item['type'])) {
              $v->errors()->add("items.$i.type", 'El tipo (fijo/porcentaje) es obligatorio para agregar o editar un bono/descuento.');
            }
            if (!isset($item['value']) || $item['value'] === '' || $item['value'] === null) {
              $v->errors()->add("items.$i.value", 'El valor es obligatorio para agregar o editar un bono/descuento.');
            }
          }
        }
      }
    });
  }

  public function messages(): array
  {
    return [
      'purchase_request_quote_id.required' => 'La solicitud/cotización es obligatoria.',
      'purchase_request_quote_id.exists' => 'La solicitud/cotización especificada no existe.',
      'items.required' => 'Debe agregar al menos una línea de cambio.',
      'items.min' => 'Debe agregar al menos una línea de cambio.',
      'items.*.action.required' => 'El tipo de cambio de cada línea es obligatorio.',
      'items.*.action.in' => 'El tipo de cambio de cada línea no es válido.',
      'items.*.item_type.in' => 'El tipo de línea (bono/descuento u obsequio) no es válido.',
    ];
  }
}
