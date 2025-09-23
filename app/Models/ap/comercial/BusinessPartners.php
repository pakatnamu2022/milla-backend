<?php

namespace App\Models\ap\comercial;

use App\Models\ap\ApCommercialMasters;
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
    'name',
    'paternal_surname',
    'maternal_surname',
    'birth_date',
    'nationality',
    'num_doc',
    'spouse_num_doc',
    'spouse_name',
    'spouse_paternal_surname',
    'spouse_maternal_surname',
    'direction',
    'legal_representative_num_doc',
    'legal_representative_name',
    'legal_representative_paternal_surname',
    'legal_representative_maternal_surname',
    'email',
    'secondary_email',
    'tertiary_email',
    'phone',
    'secondary_phone',
    'tertiary_phone',
    'secondary_phone_contact_name',
    'tertiary_phone_contact_name',
    'driving_license',
    'driving_license_place',
    'driving_license_issue_date',
    'driving_license_expiration_date',
    'origin_id',
    'driving_license_type_id',
    'tax_class_type_id',
    'type_road_id',
    'type_person_id',
    'district_id',
    'document_type_id',
    'person_segment_id',
    'marital_status_id',
    'gender_id',
    'activity_economic_id',
    'company_id',
  ];

  protected $casts = [
    'birth_date' => 'date',
    'driving_license_issue_date' => 'date',
    'driving_license_expiration_date' => 'date',
  ];

  const filters = [
    'search' => ['name', 'paternal_surname', 'maternal_surname', 'num_doc', 'email', 'phone'],
    'company_id' => '=',
    'type_person_id' => '=',
    'document_type_id' => '=',
    'district_id' => '=',
    'nationality' => '=',
    'marital_status_id' => '=',
    'gender_id' => '=',
  ];

  const sorts = [
    'name',
    'paternal_surname',
    'maternal_surname',
    'created_at',
    'updated_at',
  ];

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = Str::upper($value);
  }

  public function setPaternalSurnameAttribute($value)
  {
    $this->attributes['paternal_surname'] = Str::upper($value);
  }

  public function setMaternalSurnameAttribute($value)
  {
    $this->attributes['maternal_surname'] = Str::upper($value);
  }

  public function setSpouseNameAttribute($value)
  {
    if ($value) {
      $this->attributes['spouse_name'] = Str::upper($value);
    }
  }

  public function setSpousePaternalSurnameAttribute($value)
  {
    if ($value) {
      $this->attributes['spouse_paternal_surname'] = Str::upper($value);
    }
  }

  public function setSpouseMaternalSurnameAttribute($value)
  {
    if ($value) {
      $this->attributes['spouse_maternal_surname'] = Str::upper($value);
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

  public function getFullNameAttribute()
  {
    return trim("{$this->name} {$this->paternal_surname} {$this->maternal_surname}");
  }

  public function getSpouseFullNameAttribute()
  {
    if (!$this->spouse_name) {
      return null;
    }
    return trim("{$this->spouse_name} {$this->spouse_paternal_surname} {$this->spouse_maternal_surname}");
  }

  public function getLegalRepresentativeFullNameAttribute()
  {
    if (!$this->legal_representative_name) {
      return null;
    }
    return trim("{$this->legal_representative_name} {$this->legal_representative_paternal_surname} {$this->legal_representative_maternal_surname}");
  }

  public function origin()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'origin_id');
  }

  public function drivingLicenseType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'driving_license_type');
  }

  public function taxClassType()
  {
    return $this->belongsTo(TaxClassTypes::class, 'tax_class_type_id');
  }

  public function typeRoad()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'type_road_id');
  }

  public function typePerson()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'type_person_id');
  }

  public function district()
  {
    return $this->belongsTo(District::class, 'district_id');
  }

  public function documentType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'document_type_id');
  }

  public function personSegment()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'person_segment_id');
  }

  public function maritalStatus()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'marital_status_id');
  }

  public function gender()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'gender_id');
  }

  public function activityEconomic()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'activity_economic_id');
  }

  public function company()
  {
    return $this->belongsTo(Company::class, 'company_id');
  }
}
