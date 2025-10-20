<?php

namespace App\Models\ap\comercial;

use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingGuides extends Model
{
  use softDeletes;

  protected $table = 'ap_vehicle_documents';

  protected $fillable = [
    'document_type',
    'issuer_type',
    'document_series',
    'document_number',
    'issue_date',
    'requires_sunat',
    'is_sunat_registered',
    'vehicle_movement_id',
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
    'cancellation_reason',
    'cancelled_by',
    'cancelled_at',
    'notes',
    'status',
    'transfer_reason_id',
    'transfer_modality_id',
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

  public function vehicleMovement(): BelongsTo
  {
    return $this->belongsTo(VehicleMovement::class, 'vehicle_movement_id');
  }

  public function transmitter(): BelongsTo
  {
    return $this->belongsTo(BusinessPartnersEstablishment::class, 'transmitter_id');
  }

  public function receiver(): BelongsTo
  {
    return $this->belongsTo(BusinessPartnersEstablishment::class, 'receiver_id');
  }

  public function cancellationReason(): BelongsTo
  {
    return $this->belongsTo(User::class, 'cancelled_by');
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
}
