<?php

namespace App\Http\Requests\ap\facturacion;

use App\Http\Requests\StoreRequest;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Validation\Rule;

class NextCorrelativeElectronicDocumentRequest extends StoreRequest
{
  /**
   * Get the validation rules that apply to the request.
   */
  public function rules(): array
  {
    $userId = $this->user()->id;
    return [
      /**
       * TODO: Change name to 'document_type_id' to avoid confusion with document type string
       */
      'document_type' => [
        'required',
        'integer',
        Rule::exists('sunat_concepts', 'id')
          ->where('type', SunatConcepts::BILLING_DOCUMENT_TYPE)
          ->whereNull('deleted_at')->where('status', 1)
      ],
      /**
       * TODO: Change name to 'series_id' to avoid confusion with document series string
       */
      'series' => [
        'required',
        'integer',
        Rule::exists('assign_sales_series', 'id')
          ->where('status', 1)->whereNull('deleted_at'),
        Rule::exists('user_series_assignment', 'voucher_id')
          ->where('worker_id', $userId)
      ]
    ];
  }

}
