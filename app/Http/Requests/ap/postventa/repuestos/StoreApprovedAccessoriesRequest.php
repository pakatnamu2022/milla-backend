<?php

namespace App\Http\Requests\ap\postventa\repuestos;

use App\Http\Requests\StoreRequest;
use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\postventa\repuestos\ApprovedAccessoryPrice;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;

class StoreApprovedAccessoriesRequest extends StoreRequest
{
  public function prepareForValidation(): void
  {
    $typeOperationId = (int) $this->input('type_operation_id');
    $currencyId = match ($typeOperationId) {
      ApMasters::TIPO_OPERACION_COMERCIAL => TypeCurrency::USD_ID,
      ApMasters::TIPO_OPERACION_POSTVENTA => TypeCurrency::PEN_ID,
      default                             => TypeCurrency::PEN_ID,
    };
    $this->merge(['type_currency_id' => $currencyId]);
  }

  public function rules(): array
  {
    return [
      'type_operation_id' => [
        'required', 'integer',
        'in:' . ApMasters::TIPO_OPERACION_COMERCIAL . ',' . ApMasters::TIPO_OPERACION_POSTVENTA,
        'exists:ap_masters,id',
      ],
      'description' => ['required', 'string', 'max:255'],
      'type_currency_id' => ['required', 'exists:type_currency,id'],
      'status' => ['sometimes', 'boolean'],

      'prices' => ['required', 'array', 'min:1'],
      'prices.*.body_type_id' => ['required', 'distinct', 'integer', 'exists:ap_masters,id'],
      'prices.*.price' => ['required', 'numeric', 'min:0'],
    ];
  }

  public function withValidator(Validator $validator): void
  {
    $validator->after(function (Validator $validator) {
      if ($validator->errors()->isNotEmpty()) {
        return;
      }

      $conflicts = $this->conflictingBodyTypes();
      if ($conflicts->isNotEmpty()) {
        $description = Str::upper(trim((string) $this->input('description')));
        $names = $conflicts->implode(', ');
        $validator->errors()->add(
          'prices',
          "La descripción \"{$description}\" ya está registrada para: {$names}. " .
          "Quita esas carrocerías o cambia la descripción para poder guardar."
        );
      }
    });
  }

  /**
   * Carrocerías del payload que ya tienen un accesorio con la misma descripción.
   *
   * @return \Illuminate\Support\Collection<int, string>
   */
  protected function conflictingBodyTypes()
  {
    $description = Str::upper(trim((string) $this->input('description')));
    $bodyTypeIds = collect($this->input('prices', []))
      ->pluck('body_type_id')
      ->filter()
      ->map(fn ($id) => (int) $id)
      ->all();

    if (empty($bodyTypeIds) || $description === '') {
      return collect();
    }

    return ApprovedAccessoryPrice::query()
      ->with('bodyType')
      ->whereIn('body_type_id', $bodyTypeIds)
      ->whereHas('approvedAccessory', function ($q) use ($description) {
        $q->where('description', $description);
        if ($ignoreId = $this->ignoreAccessoryId()) {
          $q->where('id', '!=', $ignoreId);
        }
      })
      ->get()
      ->map(fn ($row) => $row->bodyType->description ?? ('#' . $row->body_type_id))
      ->unique()
      ->values();
  }

  protected function ignoreAccessoryId(): ?int
  {
    return null;
  }

  public function messages(): array
  {
    return [
      'type_operation_id.required' => 'El tipo de operación es obligatorio.',
      'type_operation_id.in' => 'El tipo de operación debe ser Comercial o Posventa.',
      'type_operation_id.exists' => 'El tipo de operación seleccionado no es válido.',

      'description.required' => 'La descripción es obligatoria.',
      'description.max' => 'La descripción no debe exceder los 255 caracteres.',

      'type_currency_id.required' => 'El tipo de moneda es obligatorio.',
      'type_currency_id.exists' => 'El tipo de moneda seleccionado no es válido.',

      'prices.required' => 'Debes registrar al menos un precio por carrocería.',
      'prices.min' => 'Debes registrar al menos un precio por carrocería.',
      'prices.*.body_type_id.required' => 'La carrocería es obligatoria.',
      'prices.*.body_type_id.distinct' => 'No repitas la misma carrocería.',
      'prices.*.body_type_id.exists' => 'La carrocería seleccionada no es válida.',
      'prices.*.price.required' => 'El precio es obligatorio.',
      'prices.*.price.numeric' => 'El precio debe ser un número.',
      'prices.*.price.min' => 'El precio debe ser mayor o igual a 0.',
    ];
  }
}
