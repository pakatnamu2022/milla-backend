<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollLoanExtraDiscount extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_payroll_loan_extra_discounts';

    // Usado solo internamente por el sistema al generar cuotas automáticas
    const CONCEPT_TYPE_REGULAR = 'REGULAR';

    protected $fillable = [
        'loan_id',
        'scheduled_date',
        'concept_type',
        'amount',
        'month_number',
        'applied',
        'confirmed_by',
        'confirmed_at',
        'status',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'month_number'   => 'integer',
        'applied'        => 'boolean',
        'status'         => 'integer',
        'scheduled_date' => 'date',
        'confirmed_at'   => 'datetime',
    ];

    const filters = [
        'search'       => ['concept_type'],
        'loan_id'      => '=',
        'concept_type' => '=',
        'applied'      => '=',
        'status'       => '=',
    ];

    const sorts = [
        'loan_id',
        'concept_type',
        'scheduled_date',
        'month_number',
        'amount',
        'applied',
        'confirmed_at',
        'created_at',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(PayrollLoan::class, 'loan_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}