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

  // CONNECTIONS DYNAMICS
  const string CONNECTION_DYNAMICS_1 = 'dbtp';
  const string CONNECTION_DYNAMICS_2 = 'dbtp2';
  const string CONNECTION_DYNAMICS_3 = 'dbtp3';

  // COMPANY ID
  const string COMPANY_GPAUP_ID = 'GPAUP';
  const string AP_TEST_DYNAMICS = 'CTEST';

  const string AP_DYNAMICS = self::COMPANY_GPAUP_ID;

  // OTROS
  const string TEST_DYNAMICS = 'CTEST';
  const string TP_DYNAMICS = 'GPTRP';
  const string DP_DYNAMICS = 'GPDPT';
  const string GP_DYNAMICS = 'GPGP';

  public function sedes()
  {
    return $this->hasMany(Sede::class, 'empresa_id', 'id');
  }
}
