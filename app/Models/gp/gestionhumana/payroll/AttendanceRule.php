<?php

namespace App\Models\gp\gestionhumana\payroll;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRule extends BaseModel
{
  use SoftDeletes;

  protected $table = 'attendance_rules';

  protected $fillable = [
    'code',
    'hour_type',
    'hours',
    'multiplier',
    'pay',
    'use_shift',
  ];

  protected $casts = [
    'hours' => 'decimal:2',
    'multiplier' => 'decimal:4',
    'pay' => 'boolean',
    'use_shift' => 'boolean',
  ];

  const filters = [
    'search' => ['code', 'hour_type'],
    'code' => '=',
    'hour_type' => '=',
    'use_shift' => '=',
    'pay' => '=',
  ];

  const sorts = [
    'code',
    'hour_type',
    'created_at',
  ];
}