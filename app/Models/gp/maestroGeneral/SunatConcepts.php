<?php

namespace App\Models\gp\maestroGeneral;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SunatConcepts extends Model
{
  use softDeletes;

  protected $table = 'sunat_concepts';

  protected $fillable = [
    'code_nubefact',
    'description',
    'type',
    'status',
  ];

  const filters = [
    'id' => '=',
    'search' => ['code_nubefact', 'description', 'type'],
    'type' => 'in',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'code_nubefact',
    'description',
    'type',
    'status',
  ];

  const GUIA_REMISION_REMITENTE = 68;

  const RUC_CODE_NUBEFACT = 6;
}
