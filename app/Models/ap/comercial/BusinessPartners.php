<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TaxClassTypes;
use App\Models\gp\gestionsistema\Company;
use App\Models\gp\gestionsistema\District;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BusinessPartners extends Model
{
  use SoftDeletes;

  protected $table = 'business_partners';

  protected $fillable = [
    'first_name',
    'middle_name',
    'paternal_surname',
    'maternal_surname',
    'full_name',
    'birth_date',
    'nationality',
    'num_doc',
    'spouse_num_doc',
    'spouse_full_name',
    'direction',
    'legal_representative_num_doc',
    'legal_representative_name',
    'legal_representative_paternal_surname',
    'legal_representative_maternal_surname',
    'legal_representative_full_name',
    'email',
    'secondary_email',
    'phone',
    'secondary_phone',
    'secondary_phone_contact_name',
    'driver_num_doc',
    'driver_full_name',
    'driving_license',
    'driving_license_issue_date',
    'driving_license_expiration_date',
    'status_license',
    'restriction',
    'status_gp',
    'status_ap',
    'status_tp',
    'status_dp',
    'company_status',
    'company_condition',
    'origin_id',
    'driving_license_category',
    'tax_class_type_id',
    'type_person_id',
    'district_id',
    'document_type_id',
    'person_segment_id',
    'marital_status_id',
    'gender_id',
    'activity_economic_id',
    'company_id',
    'type',
    'establishments_status',
    'supplier_tax_class_id'
  ];

  protected $casts = [
    'birth_date' => 'date',
    'driving_license_issue_date' => 'date',
    'driving_license_expiration_date' => 'date',
  ];

  const filters = [
    'search' => ['full_name', 'paternal_surname', 'maternal_surname', 'num_doc', 'email', 'phone'],
    'company_id' => '=',
    'type_person_id' => '=',
    'document_type_id' => '=',
    'district_iddistrict_id' => '=',
    'nationality' => '=',
    'marital_status_id' => '=',
    'gender_id' => '=',
    'type' => 'in',
    'status_gp' => '=',
    'status_ap' => '=',
    'status_tp' => '=',
    'status_dp' => '=',
  ];

  const sorts = [
    'full_name',
    'paternal_surname',
    'maternal_surname',
    'created_at',
    'updated_at',
  ];

  const CLIENT = 'CLIENTE';
  const SUPPLIER = 'PROVEEDOR';
  const BOTH = 'AMBOS';

  const DYNAMICS_CLIENT = 'CLIENTES';
  const DYNAMICS_SUPPLIER = 'PROVEEDORES';

  public function setFirstNameAttribute($value)
  {
    $this->attributes['first_name'] = Str::upper($value);
  }

  public function setMiddleNameAttribute($value)
  {
    if ($value) {
      $this->attributes['middle_name'] = Str::upper($value);
    }
  }

  public function setPaternalSurnameAttribute($value)
  {
    $this->attributes['paternal_surname'] = Str::upper($value);
  }

  public function setMaternalSurnameAttribute($value)
  {
    $this->attributes['maternal_surname'] = Str::upper($value);
  }

  public function setFullNameAttribute($value)
  {
    $this->attributes['full_name'] = Str::upper($value);
  }

  public function setSpouseFullNameAttribute($value)
  {
    if ($value) {
      $this->attributes['spouse_full_name'] = Str::upper($value);
    }
  }

  public function setDirectionAttribute($value)
  {
    $this->attributes['direction'] = Str::upper($value);
  }

  public function setCompanyStatusAttribute($value)
  {
    if ($value) {
      $this->attributes['company_status'] = Str::upper($value);
    }
  }

  public function setCompanyConditionAttribute($value)
  {
    if ($value) {
      $this->attributes['company_condition'] = Str::upper($value);
    }
  }

  public function setLegalRepresentativeNameAttribute($value)
  {
    if ($value) {
      $this->attributes['legal_representative_name'] = Str::upper($value);
    }
  }

  public function setLegalRepresentativePaternalSurnameAttribute($value)
  {
    if ($value) {
      $this->attributes['legal_representative_paternal_surname'] = Str::upper($value);
    }
  }

  public function setLegalRepresentativeMaternalSurnameAttribute($value)
  {
    if ($value) {
      $this->attributes['legal_representative_maternal_surname'] = Str::upper($value);
    }
  }

  public function setLegalRepresentativeFullNameAttribute($value)
  {
    if ($value) {
      $this->attributes['legal_representative_full_name'] = Str::upper($value);
    }
  }

  public function setDriverFullNameAttribute($value)
  {
    if ($value) {
      $this->attributes['driver_full_name'] = Str::upper($value);
    }
  }

  public function setDrivingLicenseCategoryAttribute($value)
  {
    if ($value) {
      $this->attributes['driving_license_category'] = Str::upper($value);
    }
  }

  public function origin()
  {
    return $this->belongsTo(ApMasters::class, 'origin_id');
  }

  public function taxClassType()
  {
    return $this->belongsTo(TaxClassTypes::class, 'tax_class_type_id');
  }

  public function supplierTaxClassType()
  {
    return $this->belongsTo(TaxClassTypes::class, 'supplier_tax_class_id');
  }

  public function typePerson()
  {
    return $this->belongsTo(ApMasters::class, 'type_person_id');
  }

  public function district()
  {
    return $this->belongsTo(District::class, 'district_id');
  }

  public function documentType()
  {
    return $this->belongsTo(ApMasters::class, 'document_type_id');
  }

  public function personSegment()
  {
    return $this->belongsTo(ApMasters::class, 'person_segment_id');
  }

  public function maritalStatus()
  {
    return $this->belongsTo(ApMasters::class, 'marital_status_id');
  }

  public function gender()
  {
    return $this->belongsTo(ApMasters::class, 'gender_id');
  }

  public function activityEconomic()
  {
    return $this->belongsTo(ApMasters::class, 'activity_economic_id');
  }

  public function company()
  {
    return $this->belongsTo(Company::class, 'company_id');
  }

  public function establishments()
  {
    return $this->hasMany(BusinessPartnersEstablishment::class, 'business_partner_id');
  }

  public function opportunities()
  {
    return $this->hasMany(Opportunity::class, 'client_id');
  }
}
