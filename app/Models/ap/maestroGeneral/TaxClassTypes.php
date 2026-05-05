<?php

namespace App\Models\ap\maestroGeneral;

use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaxClassTypes extends Model
{
  protected $table = 'tax_class_types';

  protected $fillable = [
    'dyn_code',
    'description',
    'tax_class',
    'igv',
    'type_detraction_id',
    'type',
    'status',
  ];

  const filters = [
    'search' => ['dyn_code', 'description', 'type', 'tax_class'],
    'type' => '=',
    'status' => '='
  ];

  const sorts = [
    'dyn_code',
    'description',
    'type',
  ];

  public function setDynCodeAttribute($value)
  {
    $this->attributes['dyn_code'] = Str::upper(Str::ascii($value));
  }

  public function setDescriptionAttribute($value)
  {
    $this->attributes['description'] = Str::upper(Str::ascii($value));
  }

  public function setTaxClassAttribute($value)
  {
    $this->attributes['tax_class'] = Str::upper(Str::ascii($value));
  }

  public function setTypeAttribute($value)
  {
    $this->attributes['type'] = Str::upper(Str::ascii($value));
  }

  public function typeDetraction()
  {
    return $this->belongsTo(SunatConcepts::class, 'type_detraction_id');
  }
}
