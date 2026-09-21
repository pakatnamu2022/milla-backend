<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollSubsidy extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_payroll_subsidies';

    const string TYPE_TEMPORARY_DISABILITY = 'INCAPACIDAD_TEMPORAL';
    const string TYPE_MATERNITY = 'MATERNIDAD';

    const array TYPES = [
        self::TYPE_TEMPORARY_DISABILITY,
        self::TYPE_MATERNITY,
    ];

    // Convención del módulo de planillas: mes comercial de 30 días.
    const int MONTH_DAYS = 30;

    protected $fillable = [
        'worker_id',
        'type',
        'start_date',
        'end_date',
        'days',
        'amount',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'integer',
        'amount' => 'decimal:2',
    ];

    const filters = [
        'search' => ['worker.nombre_completo', 'worker.vat', 'reference'],
        'worker_id' => '=',
        'type' => '=',
        'start_date' => 'date_between',
    ];

    const sorts = [
        'worker_id',
        'start_date',
        'end_date',
        'amount',
        'created_at',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function scopeOverlapping($query, $from, $to)
    {
        return $query->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from);
    }

    /**
     * Días y monto de subsidio que le corresponden al trabajador dentro de un periodo:
     * por cada certificado que se cruza con [periodStart, periodEnd], los días del cruce y el
     * monto prorrateado (amount x días del cruce / días totales del certificado). Los días se
     * topan a 30 (mes comercial) para que un mes de 31 días no reste más de un mes completo.
     *
     * @return array{days: int, amount: float}
     */
    public static function allocationForPeriod(int $workerId, $periodStart, $periodEnd): array
    {
        $periodStart = Carbon::parse($periodStart)->startOfDay();
        $periodEnd = Carbon::parse($periodEnd)->startOfDay();

        $days = 0;
        $amount = 0.0;

        foreach (self::where('worker_id', $workerId)->overlapping($periodStart, $periodEnd)->get() as $subsidy) {
            $from = $subsidy->start_date->copy()->max($periodStart);
            $to = $subsidy->end_date->copy()->min($periodEnd);
            $overlap = $from->diffInDays($to) + 1;

            $days += $overlap;
            $amount += (float)$subsidy->amount * $overlap / max(1, $subsidy->days);
        }

        return [
            'days' => min($days, self::MONTH_DAYS),
            'amount' => round($amount, 2),
        ];
    }
}
