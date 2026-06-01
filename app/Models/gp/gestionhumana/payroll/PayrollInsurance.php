<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\ap\comercial\BusinessPartners;
use App\Models\BaseModel;
use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollInsurance extends BaseModel
{
    use SoftDeletes;

    protected $table = 'gh_payroll_insurances';

    protected $fillable = [
        'worker_id',
        'period_id',
        'business_partner_id',
        'family_group_number',
        'relationship',
        'doc_type_affiliate',
        'doc_number_affiliate',
        'gender',
        'paternal_surname',
        'maternal_surname',
        'first_name',
        'second_name',
        'entry_date',
        'birth_date',
        'condition',
        'program',
        'plan',
        'payment_frequency',
        'type',
        'rate_without_tax',
        'tax',
        'rate_with_tax',
        'period_from',
        'period_until',
        'affiliation_continuity_date',
        'affiliation_from',
        'affiliation_until',
        'status',
    ];

    protected $casts = [
        'rate_without_tax' => 'decimal:2',
        'tax'              => 'decimal:2',
        'rate_with_tax'    => 'decimal:2',
        'entry_date'       => 'date',
        'birth_date'       => 'date',
        'period_from'      => 'date',
        'period_until'     => 'date',
        'affiliation_continuity_date' => 'date',
        'affiliation_from' => 'date',
        'affiliation_until' => 'date',
        'status'           => 'integer',
    ];

    const filters = [
        'search'               => ['paternal_surname', 'maternal_surname', 'first_name', 'doc_number_affiliate'],
        'worker_id'            => '=',
        'period_id'            => '=',
        'business_partner_id'  => '=',
        'relationship'         => '=',
        'program'              => '=',
        'plan'                 => '=',
        'status'               => '=',
    ];

    const sorts = [
        'worker_id',
        'period_id',
        'relationship',
        'paternal_surname',
        'program',
        'rate_with_tax',
        'created_at',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'period_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartners::class, 'business_partner_id');
    }
}