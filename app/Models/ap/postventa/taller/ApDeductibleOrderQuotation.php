<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApDeductibleOrderQuotation extends Model
{
  use SoftDeletes;

  protected $table = 'ap_deductible_order_quotation';

  protected $fillable = [
    'order_quotation_id',
    'electronic_document_id',
    'created_by',
  ];

  const filters = [
    'order_quotation_id' => '=',
    'electronic_document_id' => '=',
  ];

  const sorts = [
    'id',
    'created_at',
  ];

  // Relations
  public function orderQuotation(): BelongsTo
  {
    return $this->belongsTo(ApOrderQuotations::class, 'order_quotation_id');
  }

  public function electronicDocument(): BelongsTo
  {
    return $this->belongsTo(ElectronicDocument::class, 'electronic_document_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}