<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSede extends BaseModel
{
  use SoftDeletes;

  protected $table = 'config_asig_user_sede';

  protected $fillable = [
    'user_id',
    'sede_id',
    'status'
  ];

  protected $casts = [
    'status' => 'boolean',
  ];

  const filters = [
    'id' => '=',
    'user_id' => '=',
    'sede_id' => '=',
    'status' => '=',
    'search' => ['user.name', 'user.username', 'sede.suc_abrev', 'sede.razon_social'],
  ];

  const sorts = [
    'id',
    'user_id',
    'sede_id',
    'status',
    'created_at',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id', 'id');
  }

  public function sede(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_id', 'id');
  }
}
