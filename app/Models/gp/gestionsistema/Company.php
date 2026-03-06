<?php

namespace App\Models\gp\gestionsistema;

use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\gp\maestroGeneral\SunatConcepts;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends BaseModel
{
  use HasFactory;

  protected $table = "companies";

  protected $fillable = [
    'name',
    'abbreviation',
    'description',
    'businessName',
    'num_doc',
    'email',
    'logo',
    'website',
    'phone',
    'address',
    'city',
    'detraction_amount'
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

  // IDs DE EMPRESAS
  const COMPANY_TP_ID = 1;
  const COMPANY_DP_ID = 2;
  const COMPANY_AP_ID = 3;
  const COMPANY_GP_ID = 4;

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
