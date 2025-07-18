<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NoDateOverlap implements ValidationRule
{
    protected ?int $excludeId;

    public function __construct(?int $excludeId = null)
    {
        $this->excludeId = $excludeId;
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $startRaw = request()->input('start_date');
        $endRaw = request()->input('end_date');

        if (!$startRaw || !$endRaw) {
            return; // no validar si aún no hay ambas fechas
        }

        $start = Carbon::parse($startRaw)->startOfDay();
        $end = Carbon::parse($endRaw)->endOfDay();

        $query = DB::table('gh_evaluation_periods')
            ->whereNull('deleted_at');

        if ($this->excludeId) {
            $query->where('id', '<>', $this->excludeId);
        }

        $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(function ($sub) use ($start, $end) {
                    $sub->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
        });

        if ($query->exists()) {
            $fail('El rango de fechas se cruza con otro periodo existente.');
        }
    }
}
