<?php

namespace App\Models\gp\gestionhumana\personal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
  protected $table = 'work_schedules';

  protected $fillable = [
    'name',
    'checkin',
    'lunch_out',
    'lunch_in',
    'checkout',
  ];

  public function workers(): HasMany
  {
    return $this->hasMany(Worker::class, 'work_schedule_id');
  }
}
