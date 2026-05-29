<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollLoanExtraDiscount extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_payroll_loan_extra_discounts';

    protected $fillable = [
        'loan_id',
        'concept_type',
        'amount',
        'month_number',
        'applied',
        'status',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'month_number' => 'integer',
        'applied'      => 'boolean',
        'status'       => 'integer',
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
        'month_number',
        'amount',
        'applied',
        'created_at',
    ];

    // Concept type constants (opcionales, para referencia)
    const CONCEPT_TYPE_GRATIFICACION_JULIO = 'GRATIFICACION_JULIO';
    const CONCEPT_TYPE_GRATIFICACION_DICIEMBRE = 'GRATIFICACION_DICIEMBRE';
    const CONCEPT_TYPE_UTILIDADES = 'UTILIDADES';

    public function loan(): BelongsTo
    {
        return $this->belongsTo(PayrollLoan::class, 'loan_id');
    }
}