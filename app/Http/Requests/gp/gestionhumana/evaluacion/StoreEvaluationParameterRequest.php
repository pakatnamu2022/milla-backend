<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use App\Http\Requests\StoreRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationParameterRequest extends StoreRequest
{
  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
        'max:255',
        Rule::unique('gh_evaluation_parameter')
          ->whereNull('deleted_at')
          ->where('type', $this->type),
      ],
      'type' => [
        'required',
        'integer',
        Rule::in(array_keys(config('evaluation.typesParameter')))
      ],
      'details' => 'required|array',
      'details.*.label' => 'required|string|max:255',
      'details.*.from' => 'required|numeric|min:0',
      'details.*.to' => 'required|numeric|min:0',
    ];
  }

  public function withValidator($validator)
  {
    $validator->after(function ($validator) {
      $details = $this->input('details', []);
      foreach ($details as $index => $detail) {
        $from = isset($detail['from']) ? (float)$detail['from'] : null;
        $to = isset($detail['to']) ? (float)$detail['to'] : null;

        if (is_numeric($from) && is_numeric($to) && $to <= $from) {
          $validator->errors()->add("details.$index.to", 'El valor ' . $to . ' debe ser mayor que el valor ' . $from . ', del detalle ' . $detail['label'] . '.');
        }
      }
    });
  }


}
