<?php

namespace App\Http\Requests;

use App\Rules\NoDateOverlap;
use Illuminate\Validation\Rule;

class StoreEvaluationPeriodRequest extends StoreRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('gh_evaluation_periods', 'name')->whereNull('deleted_at'),
            ],
            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                new NoDateOverlap()
            ],
        ];

    }
}
