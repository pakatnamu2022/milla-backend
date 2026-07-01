<?php

namespace App\Models\gp\gestionhumana\asistencias;

use Illuminate\Database\Eloquent\Model;

class AttendanceCodeMapping extends Model
{
  protected $table = 'attendance_code_mappings';

  protected $fillable = [
    'emp_code',
    'vat',
    'note',
    'created_by',
  ];

  const filters = [
    'search'   => ['emp_code', 'vat'],
    'emp_code' => '=',
    'vat'      => '=',
  ];

  const sorts = ['id', 'emp_code', 'vat', 'created_at'];
}
