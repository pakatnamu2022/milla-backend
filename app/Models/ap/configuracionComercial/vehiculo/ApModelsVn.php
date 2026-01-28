<?php

namespace App\Models\ap\configuracionComercial\vehiculo;

use App\Models\ap\ApMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApModelsVn extends Model
{
  use SoftDeletes;

  protected $table = 'ap_models_vn';

  protected $fillable = [
    'code',
    'version',
    'power',
    'model_year',
    'wheelbase',
    'axles_number',
    'width',
    'length',
    'height',
    'seats_number',
    'doors_number',
    'net_weight',
    'gross_weight',
    'payload',
    'displacement',
    'cylinders_number',
    'passengers_number',
    'wheels_number',
    'distributor_price',
    'transport_cost',
    'other_amounts',
    'purchase_discount',
    'igv_amount',
    'total_purchase_excl_igv',
    'total_purchase_incl_igv',
    'sale_price',
    'margin',
    'family_id',
    'class_id',
    'fuel_id',
    'vehicle_type_id',
    'body_type_id',
    'traction_type_id',
    'transmission_id',
    'currency_type_id',
    'type_operation_id',
    'status'
  ];

  const filters = [
    'search' => ['code', 'version'],
    'status' => '=',
    'family_id' => '=',
    'family.brand_id' => '=',
    'class_id' => '=',
    'fuel_id' => '=',
    'vehicle_type_id' => '=',
    'body_type_id' => '=',
    'traction_type_id' => '=',
    'transmission_id' => '=',
    'currency_type_id' => '=',
    'type_operation_id' => '=',
  ];

  const sorts = [
    'code',
    'version',
  ];

  public function family()
  {
    return $this->belongsTo(ApFamilies::class, 'family_id');
  }

  public function classArticle()
  {
    return $this->belongsTo(ApClassArticle::class, 'class_id');
  }

  public function fuelType()
  {
    return $this->belongsTo(ApFuelType::class, 'fuel_id');
  }

  public function vehicleType()
  {
    return $this->belongsTo(ApMasters::class, 'vehicle_type_id');
  }

  public function bodyType()
  {
    return $this->belongsTo(ApMasters::class, 'body_type_id');
  }

  public function tractionType()
  {
    return $this->belongsTo(ApMasters::class, 'traction_type_id');
  }

  public function vehicleTransmission()
  {
    return $this->belongsTo(ApMasters::class, 'transmission_id');
  }

  public function typeCurrency()
  {
    return $this->belongsTo(TypeCurrency::class, 'currency_type_id');
  }

  public function setCodeAttribute($value)
  {
    $this->attributes['code'] = Str::upper(Str::ascii($value));
  }

  public function setVersionAttribute($value)
  {
    $this->attributes['version'] = Str::upper(Str::ascii($value));
  }

  public function setPowerAttribute($value)
  {
    $this->attributes['power'] = Str::upper(Str::ascii($value));
  }

  public function setWheelbaseAttribute($value)
  {
    $this->attributes['wheelbase'] = Str::upper(Str::ascii($value));
  }

  public function setAxlesNumberAttribute($value)
  {
    $this->attributes['axles_number'] = Str::upper(Str::ascii($value));
  }

  public function setWidthAttribute($value)
  {
    $this->attributes['width'] = Str::upper(Str::ascii($value));
  }

  public function setLengthAttribute($value)
  {
    $this->attributes['length'] = Str::upper(Str::ascii($value));
  }

  public function setHeightAttribute($value)
  {
    $this->attributes['height'] = Str::upper(Str::ascii($value));
  }

  public function setSeatsNumberAttribute($value)
  {
    $this->attributes['seats_number'] = Str::upper(Str::ascii($value));
  }

  public function setDoorsNumberAttribute($value)
  {
    $this->attributes['doors_number'] = Str::upper(Str::ascii($value));
  }

  public function setNetWeightAttribute($value)
  {
    $this->attributes['net_weight'] = Str::upper(Str::ascii($value));
  }

  public function setGrossWeightAttribute($value)
  {
    $this->attributes['gross_weight'] = Str::upper(Str::ascii($value));
  }

  public function setPayloadAttribute($value)
  {
    $this->attributes['payload'] = Str::upper(Str::ascii($value));
  }

  public function setDisplacementAttribute($value)
  {
    $this->attributes['displacement'] = Str::upper(Str::ascii($value));
  }

  public function setCylindersNumberAttribute($value)
  {
    $this->attributes['cylinders_number'] = Str::upper(Str::ascii($value));
  }

  public function setPassengersNumberAttribute($value)
  {
    $this->attributes['passengers_number'] = Str::upper(Str::ascii($value));
  }

  public function setWheelsNumberAttribute($value)
  {
    $this->attributes['wheels_number'] = Str::upper(Str::ascii($value));
  }

  public function typeOperation()
  {
    return $this->belongsTo(ApMasters::class, 'type_operation_id');
  }

  /**
   * Generate next code for a model based on family, year and operation type
   * Each operation type (COMERCIAL/POSTVENTA) has its own correlative sequence
   *
   * @param int $familyId Family ID
   * @param string $modelYear Model year (e.g., "2024")
   * @param int $typeOperationId Operation type ID (COMERCIAL or POSTVENTA)
   * @return string Generated code (e.g., "HILUX24001")
   */
  public static function generateNextCode(int $familyId, string $modelYear, int $typeOperationId): string
  {
    // Get family code
    $familia = ApFamilies::findOrFail($familyId);
    $familyCode = $familia->code;

    // Get short year (last 2 digits)
    $shortYear = substr($modelYear, -2);

    // Get next correlative for this family, year AND operation type
    $lastModel = self::where('family_id', $familyId)
      ->where('type_operation_id', $typeOperationId)
      ->where('code', 'LIKE', $familyCode . $shortYear . '%')
      ->whereNull('deleted_at')
      ->orderByRaw('CAST(SUBSTRING(code, -3) AS UNSIGNED) DESC')
      ->first();

    $nextNumber = $lastModel
      ? ((int)substr($lastModel->code, -3)) + 1
      : 1;

    // Format correlative with 3 digits (001, 002, etc.)
    $correlative = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    // Return complete code: FAMILY + YEAR + CORRELATIVE
    return $familyCode . $shortYear . $correlative;
  }
}
