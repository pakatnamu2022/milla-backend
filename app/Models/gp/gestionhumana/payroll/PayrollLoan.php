<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\GeneralMaster;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollLoan extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_payroll_loans';

    protected $fillable = [
        'concept_id',
        'worker_id',
        'delivery_date',
        'reason',
        'payment_start',
        'loan_amount',
        'installments_count',
        'installment_amount',
        'status',
    ];

    protected $casts = [
        'loan_amount'        => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'delivery_date'      => 'date',
        'payment_start'      => 'date',
        'installments_count' => 'integer',
        'status'             => 'integer',
    ];

    const filters = [
        'search'       => ['reason'],
        'worker_id'    => '=',
        'concept_id'   => '=',
        'status'       => '=',
    ];

    const sorts = [
        'worker_id',
        'concept_id',
        'delivery_date',
        'loan_amount',
        'created_at',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function concept(): BelongsTo
    {
        return $this->belongsTo(GeneralMaster::class, 'concept_id');
    }

    public function extraDiscounts(): HasMany
    {
        return $this->hasMany(PayrollLoanExtraDiscount::class, 'loan_id');
    }
}