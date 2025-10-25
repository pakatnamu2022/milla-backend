<?php

namespace App\Models\ap\comercial;

use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShippingGuides extends Model
{
  use softDeletes;

  protected $table = 'shipping_guides';

  protected $fillable = [
    'document_type',
    'issuer_type',
    'document_series_id',
    'document_number',
    'issue_date',
    'requires_sunat',
    'is_sunat_registered',
    'total_packages',
    'total_weight',
    'vehicle_movement_id',
    'sede_transmitter_id',
    'sede_receiver_id',
    'transmitter_id',
    'receiver_id',
    'file_path',
    'file_name',
    'file_type',
    'file_url',
    'transport_company_id',
    'driver_doc',
    'license',
    'plate',
    'driver_name',
    'notes',
    'status',
    'created_by',
    'transfer_reason_id',
    'transfer_modality_id',
    'is_received',
    'note_received',
    'received_by',
    'received_date',
    'cancellation_reason',
    'cancelled_by',
    'cancelled_at',
  ];

  protected $casts = [
    'issue_date' => 'datetime',
    'cancelled_at' => 'datetime',
    'requires_sunat' => 'boolean',
    'is_sunat_registered' => 'boolean',
    'status' => 'boolean',
  ];

  const filters = [
    'document_type',
    'issuer_type',
    'document_series',
    'document_number',
    'issue_date',
    'requires_sunat',
    'is_sunat_registered',
    'vehicle_movement_id',
    'sede_transmitter_id',
    'sede_receiver_id',
    'transmitter_id',
    'receiver_id',
    'transport_company_id',
    'driver_doc',
    'license',
    'plate',
    'driver_name',
    'status',
    'transfer_reason_id',
    'transfer_modality_id',
  ];

  const search = [
    'document_series',
    'document_number',
    'license',
    'plate',
    'driver_name',
  ];

  public function setLicenseAttribute($value)
  {
    $this->attributes['license'] = Str::upper(Str::ascii($value));
  }

  public function setPlateAttribute($value)
  {
    $this->attributes['plate'] = Str::upper(Str::ascii($value));
  }

  public function setDriverNameAttribute($value)
  {
    $this->attributes['driver_name'] = Str::upper(Str::ascii($value));
  }

  public function setNotesAttribute($value): void
  {
    $this->attributes['notes'] = Str::upper(Str::ascii($value));
  }

  public function setNoteReceivedAttribute($value): void
  {
    $this->attributes['note_received'] = Str::upper(Str::ascii($value));
  }

  public function vehicleMovement(): BelongsTo
  {
    return $this->belongsTo(VehicleMovement::class, 'vehicle_movement_id');
  }

  public function sedeTransmitter(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_transmitter_id');
  }

  public function sedeReceiver(): BelongsTo
  {
    return $this->belongsTo(Sede::class, 'sede_receiver_id');
  }

  public function transmitter(): BelongsTo
  {
    return $this->belongsTo(BusinessPartnersEstablishment::class, 'transmitter_id');
  }

  public function receiver(): BelongsTo
  {
    return $this->belongsTo(BusinessPartnersEstablishment::class, 'receiver_id');
  }

  public function transferModality(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'transfer_modality_id');
  }

  public function transferReason(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'transfer_reason_id');
  }

  public function transportCompany(): BelongsTo
  {
    return $this->belongsTo(BusinessPartners::class, 'transport_company_id');
  }

  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function canceller(): BelongsTo
  {
    return $this->belongsTo(User::class, 'cancelled_by');
  }

  public function documentSeries(): BelongsTo
  {
    return $this->belongsTo(AssignSalesSeries::class, 'document_series_id');
  }
}
