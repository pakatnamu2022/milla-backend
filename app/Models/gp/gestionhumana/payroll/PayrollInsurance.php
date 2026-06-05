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
        'doc_number_affiliate',
        'rate_with_tax',
        'contracting_name',
        'num_doc_contracting',
    ];

    protected $casts = [
        'rate_with_tax' => 'decimal:2',
    ];

    const filters = [
        'search'               => ['doc_number_affiliate', 'contracting_name', 'num_doc_contracting'],
        'worker_id'            => '=',
        'period_id'            => '=',
        'business_partner_id'  => '=',
    ];

    const sorts = [
        'worker_id',
        'period_id',
        'rate_with_tax',
        'contracting_name',
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