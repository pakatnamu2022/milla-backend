<?php

namespace App\Models\gp\maestroGeneral;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SunatConcepts extends Model
{
  use softDeletes;

  protected $table = 'sunat_concepts';

  protected $fillable = [
    'code',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'id' => '=',
    'search' => ['code', 'description', 'type'],
    'type' => '=',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'code',
    'description',
    'type',
    'status',
  ];
}
