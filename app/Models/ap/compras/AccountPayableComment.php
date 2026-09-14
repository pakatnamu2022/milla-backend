<?php

namespace App\Models\ap\compras;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountPayableComment extends BaseModel
{
  use SoftDeletes;

  protected $table = 'accounts_payable_comments';

  protected $fillable = [
    'accounts_payable_id',
    'user_id',
    'comment',
  ];

  public function accountsPayable(): BelongsTo
  {
    return $this->belongsTo(AccountPayable::class, 'accounts_payable_id');
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
