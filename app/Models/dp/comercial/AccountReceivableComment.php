<?php

namespace App\Models\dp\comercial;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountReceivableComment extends BaseModel
{
  protected $table = 'accounts_receivable_comments';

  protected $fillable = [
    'accounts_receivable_id',
    'sede_id',
    'user_id',
    'comment',
  ];

  public function accountsReceivable(): BelongsTo
  {
    return $this->belongsTo(AccountReceivable::class, 'accounts_receivable_id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
