<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;
use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Exception;
use Illuminate\Validation\Rule;

class UpdateCreditNoteRequest extends StoreRequest
{
  /**
   * Prepare the data for validation.
   * @throws Exception
   */
  protected function prepareForValidation()
  {
    // original_document_id comes from the route {id} parameter
    $routeId = $this->route('id');
    if ($routeId !== null) {
      $this->merge(['original_document_id' => (int) $routeId]);
    }

    $numericFields = [
      'series',
      'sunat_concept_credit_note_type_id',
    ];

    $dataToMerge = [];

    foreach ($numericFields as $field) {
      if ($this->has($field) && $this->input($field) !== null && $this->input($field) !== '') {
        $dataToMerge[$field] = (int)$this->input($field);
      }
    }

    if ($this->has('discount_amount') && $this->input('discount_amount') !== null) {
      $dataToMerge['discount_amount'] = (float)$this->input('discount_amount');
    }

    if ($this->has('account_plan_id') && $this->input('account_plan_id') !== null) {
      $dataToMerge['account_plan_id'] = (int)$this->input('account_plan_id');
    }

    if ($this->has('detail_ids') && is_array($this->input('detail_ids'))) {
      $dataToMerge['detail_ids'] = array_map('intval', $this->input('detail_ids'));
    }

    $booleanFields = [
      'enviar_automaticamente_a_la_sunat',
      'enviar_automaticamente_al_cliente',
    ];

    foreach ($booleanFields as $field) {
      if ($this->has($field) && $this->input($field) !== null) {
        $value = $this->input($field);
        $dataToMerge[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $value;
      }
    }

    if (!empty($dataToMerge)) {
      $this->merge($dataToMerge);
    }

    if ($this->has('series')) {
      $seriesId = (int)$this->input('series');
      $assignSeries = AssignSalesSeries::find($seriesId);

      if ($assignSeries) {
        $this->merge([
          'series_id' => $seriesId,
          'serie' => $assignSeries->series,
        ]);
      } else {
        throw new Exception('La serie seleccionada no es válida');
      }
    }
  }

  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    $userId = $this->user()->id;
    $typeCode = $this->getCreditNoteTypeCode();

    $rules = [
      'original_document_id' => [
        'required',
        'integer',
        Rule::exists('ap_billing_electronic_documents', 'id')
          ->whereNull('deleted_at')
          ->where('aceptada_por_sunat', true)
          ->where('anulado', false)
      ],

      'sunat_concept_credit_note_type_id' => [
        'required',
        'integer',
        Rule::exists('sunat_concepts', 'id')
          ->where('type', SunatConcepts::BILLING_CREDIT_NOTE_TYPE)
          ->whereNull('deleted_at')
          ->where('status', 1)
      ],

      'series_id' => [
        'required',
        'integer',
        Rule::exists('assign_sales_series', 'id')
          ->where('status', 1)
          ->whereNull('deleted_at'),
        Rule::exists('user_series_assignment', 'voucher_id')
          ->where('worker_id', $userId)
      ],
      'serie' => 'required|string|max:4',

      'fecha_de_emision' => 'required|date',

      'observaciones' => 'nullable|string|max:1000',
      'enviar_automaticamente_a_la_sunat' => 'nullable|boolean',
      'enviar_automaticamente_al_cliente' => 'nullable|boolean',
    ];

    if ($typeCode === SunatConcepts::CODE_CREDIT_NOTE_DESCUENTO_GLOBAL) {
      $rules['discount_amount'] = 'required|numeric|min:0.01';
      $rules['account_plan_id'] = [
        'required',
        'integer',
        Rule::exists('ap_accounting_account_plan', 'id')
          ->whereNull('deleted_at')
          ->where('status', 1)
      ];
    } elseif ($typeCode === SunatConcepts::CODE_CREDIT_NOTE_DEVOLUCION_ITEM) {
      $rules['detail_ids'] = 'required|array|min:1';
      $rules['detail_ids.*'] = 'required|integer';
    }
    // For '01' (Anulación) and '06' (Devolución total): no extra fields needed

    return $rules;
  }

  /**
   * Get custom messages for validator errors.
   */
  public function messages(): array
  {
    return [
      'original_document_id.required' => 'El documento original es obligatorio',
      'original_document_id.exists' => 'El documento original no existe, no está aceptado por SUNAT o está anulado',
      'sunat_concept_credit_note_type_id.required' => 'El tipo de nota de crédito es obligatorio',
      'sunat_concept_credit_note_type_id.exists' => 'El tipo de nota de crédito seleccionado no es válido',
      'series.required' => 'La serie es obligatoria',
      'fecha_de_emision.required' => 'La fecha de emisión es obligatoria',
      'discount_amount.required' => 'El monto del descuento es obligatorio para descuento global',
      'discount_amount.min' => 'El monto del descuento debe ser mayor a 0',
      'account_plan_id.required' => 'La cuenta contable es obligatoria para descuento global',
      'detail_ids.required' => 'Debe especificar al menos un ítem a devolver',
      'detail_ids.min' => 'Debe especificar al menos un ítem a devolver',
    ];
  }

  /**
   * Configure the validator instance.
   */
  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $typeCode = $this->getCreditNoteTypeCode();

      if ($typeCode !== SunatConcepts::CODE_CREDIT_NOTE_DEVOLUCION_ITEM) {
        return;
      }

      $originalDocumentId = $this->input('original_document_id');
      $detailIds = $this->input('detail_ids');

      if (!$originalDocumentId || !is_array($detailIds) || empty($detailIds)) {
        return;
      }

      $originalDocument = ElectronicDocument::with('items')->find($originalDocumentId);
      if (!$originalDocument) {
        return;
      }

      $validIds = $originalDocument->items->pluck('id')->toArray();
      $invalidIds = array_diff($detailIds, $validIds);

      if (!empty($invalidIds)) {
        $validator->errors()->add(
          'detail_ids',
          'Los siguientes IDs de ítems no pertenecen al documento original: ' . implode(', ', $invalidIds)
        );
      }
    });
  }

  /**
   * Resolve the code_nubefact of the selected credit note type.
   */
  private function getCreditNoteTypeCode(): ?string
  {
    $typeId = $this->input('sunat_concept_credit_note_type_id');
    if (!$typeId) {
      return null;
    }
    $concept = SunatConcepts::find((int)$typeId);
    return $concept?->code_nubefact;
  }
}
