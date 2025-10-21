<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSeriesAssignment extends Model
{
  use SoftDeletes;

  protected $table = 'user_series_assignment';

  protected $fillable = [
    'worker_id',
    'voucher_id',
  ];

  public function worker()
  {
    return $this->belongsTo(User::class, 'worker_id');
  }

  public function workers()
  {
    return $this->belongsToMany(
      User::class,
      'user_series_assignment',
      'voucher_id',
      'user_id'
    )->withTimestamps()->withTrashed();
  }

  public function voucher()
  {
    return $this->belongsTo(AssignSalesSeries::class, 'voucher_id');
  }
}
