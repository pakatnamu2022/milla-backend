<?php

namespace App\Http\Requests\gp\gestionhumana\evaluacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationParameterRequest extends FormRequest
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
                    ->where('type', $this->type)
                    ->ignore($this->route('parameter')),
            ],
            'type' => 'required|in:objectives,competences,final',
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
