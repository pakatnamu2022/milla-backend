<?php

namespace App\Models\ap\postventa\taller;

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApDeductibleWorkOrder extends Model
{
  use SoftDeletes;

  protected $table = 'ap_deductible_work_order';

  protected $fillable = [
    'work_order_id',
    'electronic_document_id',
    'created_by',
  ];

  const filters = [
    'work_order_id' => '=',
    'electronic_document_id' => '=',
  ];

  const sorts = [
    'id',
    'created_at',
  ];

  // Relations
  public function workOrder(): BelongsTo
  {
    return $this->belongsTo(ApWorkOrder::class, 'work_order_id');
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