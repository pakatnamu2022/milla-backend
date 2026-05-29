<?php

namespace App\Models\gp\gestionhumana\asistencias;

use App\Models\gp\gestionhumana\personal\Worker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSync extends Model
{
  protected $table = 'attendance_sync';

  protected $fillable = [
    'zkbio_transaction_id',
    'person_id',
    'emp_code',
    'full_name',
    'date',
    'mark_type',
    'time',
    'area',
    'punch_state_original',
    'synced_at',
  ];

  protected $casts = [
    'date'     => 'date',
    'synced_at' => 'datetime',
  ];

  public function person(): BelongsTo
  {
    return $this->belongsTo(Worker::class, 'person_id');
  }
}
