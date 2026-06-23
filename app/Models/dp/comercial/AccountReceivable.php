<?php

namespace App\Models\dp\comercial;

use App\Http\Traits\Reportable;
use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountReceivable extends BaseModel
{
  use Reportable;

  protected array $reportRelations = ['sede', 'comments'];

  protected array $reportColumns = [
    'sede'              => 'Sede',
    'seller'            => 'Vendedor',
    'cashier'           => 'Cajero',
    'document_number'   => 'N° Documento',
    'client_id'         => 'Cliente ID',
    'client_name'       => 'Cliente Nombre',
    'client_id_real'    => 'Cliente ID Real',
    'client_name_real'  => 'Cliente Nombre Real',
    'document_date'     => 'Fecha Documento',
    'document_due_date' => 'Fecha Vencimiento',
    'due_year'          => 'Año Venc.',
    'due_month'         => 'Mes Venc.',
    'overdue_days'      => 'Días Vencidos',
    'overdue_status'    => 'Estado',
    'currency'          => 'Moneda',
    'exchange_rate'     => 'T/C',
    'amount'            => 'Importe',
    'balance'           => 'Saldo',
    'amount_pen'        => 'Importe PEN (S/)',
    'balance_pen'       => 'Saldo PEN (S/)',
    'collection_date'   => 'Fecha Cobro',
    'last_comment'      => 'Último Comentario',
  ];

  protected array $reportColorRules = [
    'overdue_status' => [
      'VENCIDO'    => 'FFCCCC',
      'POR VENCER' => 'FFF3CD',
    ],
  ];

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
    'amount_pen',
    'balance_pen',
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
    'amount_pen'        => 'decimal:5',
    'balance_pen'       => 'decimal:5',
    'overdue_days'      => 'integer',
    'due_year'          => 'integer',
  ];

  const filters = [
    'search'            => ['document_number', 'client_name', 'client_id', 'seller', 'branch'],
    'sede_id'           => 'in_or_equal',
    'company'           => '=',
    'currency'          => '=',
    'overdue_status'    => 'in_or_equal',
    'seller'            => 'like',
    'document_date'     => 'date_between',
    'document_due_date' => 'date_between',
    'due_year'          => 'in_or_equal',
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
