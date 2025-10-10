<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends BaseModel
{
  use HasFactory;

  protected $fillable = [
    'name',
    'abbreviation',
    'description',
    'businessName',
    'email',
    'logo',
    'website',
    'phone',
    'address',
    'city',
  ];

  const filters = [
    'serach' => [
      'name',
      'businessName',
      'email',
      'website',
      'phone',
      'address',
      'city'
    ],
    'name' => 'like',
    'businessName' => 'like',
    'email' => 'like',
    'website' => 'like',
    'phone' => 'like',
    'address' => 'like',
    'city' => 'like',
  ];

  const sorts = [
    'name' => 'asc',
    'businessName' => 'asc',
    'email' => 'asc',
    'website' => 'asc',
    'phone' => 'asc',
    'address' => 'asc',
    'city' => 'asc',
  ];

  const TEST_DYNAMICS = 'CTEST';
  const AP_DYNAMICS = 'CTEST';
  const TP_DYNAMICS = 'GPTRP';

  const DP_DYNAMICS = 'GPDPT';
  const GP_DYNAMICS = 'GPGP';

  public function sedes()
  {
    return $this->hasMany(Sede::class, 'empresa_id', 'id');
  }
}
