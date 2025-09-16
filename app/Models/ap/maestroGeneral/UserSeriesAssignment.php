<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\gp\gestionhumana\personal\Worker;
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
    return $this->belongsTo(Worker::class, 'worker_id');
  }

  public function workers()
  {
    return $this->belongsToMany(
      Worker::class,
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
