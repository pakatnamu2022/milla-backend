<?php

namespace App\Models\dp\comercial;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountReceivable extends BaseModel
{
  protected $table = 'accounts_receivable';

  protected $fillable = [
    'company',
    'sede_id',
    'seller',
    'cashier',
    'document_number',
    'client_id',
    'client_name',
    'client_id_real',
    'client_name_real',
    'document_date',
    'document_due_date',
    'due_year',
    'due_month',
    'overdue_days',
    'overdue_status',
    'currency',
    'exchange_rate',
    'amount',
    'balance',
    'branch',
    'observations',
    'collection_date',
    'synced_at',
  ];

  protected $casts = [
    'document_date'     => 'date',
    'document_due_date' => 'date',
    'collection_date'   => 'date',
    'synced_at'         => 'datetime',
    'exchange_rate'     => 'decimal:5',
    'amount'            => 'decimal:5',
    'balance'           => 'decimal:5',
    'overdue_days'      => 'integer',
    'due_year'          => 'integer',
  ];

  const filters = [
    'search'            => ['document_number', 'client_name', 'client_id', 'seller', 'branch'],
    'sede_id'           => '=',
    'company'           => '=',
    'currency'          => '=',
    'overdue_status'    => '=',
    'seller'            => 'like',
    'document_date'     => 'date_between',
    'document_due_date' => 'date_between',
  ];

  const sorts = [
    'document_number',
    'client_name',
    'document_date',
    'document_due_date',
    'overdue_days',
    'amount',
    'balance',
    'seller',
    'synced_at',
    'created_at',
  ];

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function comments(): HasMany
  {
    return $this->hasMany(AccountReceivableComment::class, 'accounts_receivable_id')->latest();
  }
}
