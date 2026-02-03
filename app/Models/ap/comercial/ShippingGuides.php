<?php

namespace App\Models\ap\comercial;

use App\Models\ap\configuracionComercial\vehiculo\ApClassArticle;
use App\Models\ap\maestroGeneral\AssignSalesSeries;
use App\Models\BaseModel;
use App\Models\gp\maestroGeneral\Sede;
use App\Models\gp\maestroGeneral\SunatConcepts;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShippingGuides extends BaseModel
{
  use softDeletes;

  protected $table = 'shipping_guides';

  protected $fillable = [
    'document_type',
    'type_voucher_id',
    'issuer_type',
    'document_series_id',
    'series',
    'dyn_series',
    'correlative',
    'document_number',
    'issue_date', // fecha de translado
    'requires_sunat',
    'is_sunat_registered',
    'sent_at',
    'accepted_at',
    'aceptada_por_sunat',
    'status_nubefac',
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
    'sunat_responsecode',
    'sunat_description',
    'sunat_note',
    'sunat_soap_error',
    'enlace',
    'enlace_del_pdf',
    'enlace_del_xml',
    'enlace_del_cdr',
    'cadena_para_codigo_qr',
    'codigo_hash',
    'error_message',
    'status_dynamic',
    'migration_status',
    'migrated_at',
    'ap_class_article_id',
    'origin_ubigeo',
    'origin_address',
    'destination_ubigeo',
    'destination_address',
    'ruc_transport',
    'company_name_transport',
    'created_at',
    'updated_at',
  ];

  protected $casts = [
    'issue_date' => 'datetime',
    'cancelled_at' => 'datetime',
    'sent_at' => 'datetime',
    'accepted_at' => 'datetime',
    'received_date' => 'datetime',
    'migrated_at' => 'datetime',
    'requires_sunat' => 'boolean',
    'is_sunat_registered' => 'boolean',
    'aceptada_por_sunat' => 'boolean',
    'status' => 'boolean',
    'is_received' => 'boolean',
  ];

  // Issuer types
  const ISSUER_TYPE_SUPPLIER = 'PROVEEDOR';
  const ISSUER_TYPE_AUTOMOTORES = 'NOSOTROS';

  const filters = [
    'search' => ['document_number', 'plate', 'driver_name', 'documentSeries.series'],
    'document_type',
    'issuer_type',
    'issue_date' => 'date_between',
    'requires_sunat',
    'is_sunat_registered',
    'vehicle_movement_id',
    'sede_transmitter_id',
    'sede_receiver_id',
    'transmitter_id', // Ubicacion Origen (Proveedor)
    'receiver_id', // Ubicacion Destino (Cliente)
    'transport_company_id',
    'driver_doc',
    'license',
    'plate',
    'driver_name',
    'status',
    'transfer_reason_id',
    'transfer_modality_id',
  ];

  const sorts = [
    'id',
    'issue_date',
  ];

  public function setSeriesAttribute($value): void
  {
    $this->attributes['series'] = Str::upper(Str::ascii($value));
  }

  public function setCorrelativeAttribute($value): void
  {
    $this->attributes['correlative'] = Str::upper(Str::ascii($value));
  }

  public function setLicenseAttribute($value): void
  {
    $this->attributes['license'] = Str::upper(Str::ascii($value));
  }

  public function setPlateAttribute($value): void
  {
    $this->attributes['plate'] = Str::upper(Str::ascii($value));
  }

  public function setDriverNameAttribute($value): void
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

  public function typeVoucher(): BelongsTo
  {
    return $this->belongsTo(SunatConcepts::class, 'type_voucher_id');
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

  public function receivedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'received_by');
  }

  public function logs()
  {
    return $this->hasMany(NubefactShippingGuideLog::class, 'shipping_guide_id');
  }

  public function migrationLogs()
  {
    return $this->hasMany(VehiclePurchaseOrderMigrationLog::class, 'shipping_guide_id');
  }

  public function ArticleClass()
  {
    return $this->belongsTo(ApClassArticle::class, 'ap_class_article_id');
  }

  public function receivingChecklists()
  {
    return $this->hasMany(ApReceivingChecklist::class, 'shipping_guide_id');
  }

  /**
   * Marca la guía como enviada a Nubefact/SUNAT
   */
  public function markAsSent(): void
  {
    $this->update([
      'is_sunat_registered' => true,
      'sent_at' => now(),
    ]);
  }

  /**
   * Marca la guía como enviada a Dynamic
   */
  public function markAsSentToDynamic(): void
  {
    $this->update([
      'status_dynamic' => true,
    ]);
  }

  /**
   * Marca la guía como aceptada por SUNAT
   */
  public function markAsAccepted(array $sunatResponse): void
  {
    $this->update([
      'is_sunat_registered' => true,
      'aceptada_por_sunat' => true,
      'accepted_at' => now(),
      'sunat_responsecode' => $sunatResponse['sunat_responsecode'] ?? null,
      'sunat_description' => $sunatResponse['sunat_description'] ?? null,
      'sunat_note' => $sunatResponse['sunat_note'] ?? null,
      'enlace' => $sunatResponse['enlace'] ?? null,
      'enlace_del_pdf' => $sunatResponse['enlace_del_pdf'] ?? null,
      'enlace_del_xml' => $sunatResponse['enlace_del_xml'] ?? null,
      'enlace_del_cdr' => $sunatResponse['enlace_del_cdr'] ?? null,
      'cadena_para_codigo_qr' => $sunatResponse['cadena_para_codigo_qr'] ?? null,
      'codigo_hash' => $sunatResponse['codigo_hash'] ?? null,
    ]);
  }

  /**
   * Marca la guía como rechazada por SUNAT
   */
  public function markAsRejected(string $errorMessage, array $sunatResponse = []): void
  {
    $this->update([
      'aceptada_por_sunat' => false,
      'error_message' => $errorMessage,
      'sunat_responsecode' => $sunatResponse['sunat_responsecode'] ?? null,
      'sunat_description' => $sunatResponse['sunat_description'] ?? null,
      'sunat_note' => $sunatResponse['sunat_note'] ?? null,
      'sunat_soap_error' => $sunatResponse['sunat_soap_error'] ?? null,
    ]);
  }

  /**
   * Marca la guía como anulada
   */
  public function markAsCancelled(string $reason = null): void
  {
    $this->update([
      'status' => false,
      'cancelled_at' => now(),
      'cancellation_reason' => $reason,
      'cancelled_by' => auth()->id(),
    ]);
  }

  /**
   * Verifica si la guía puede ser enviada a SUNAT
   */
  public function canBeSentToSunat(): bool
  {
    return $this->requires_sunat && !$this->is_sunat_registered && !$this->cancelled_at;
  }

  /**
   * Verifica si la guía está aceptada por SUNAT
   */
  public function isAcceptedBySunat(): bool
  {
    return $this->aceptada_por_sunat === true;
  }

  public static function generateNextCorrelative(int $documentSeriesId, int $correlativeStart = 1): array
  {
    // Buscar el último correlativo usado para esta serie
    $lastShippingGuide = self::where('document_series_id', $documentSeriesId)
      ->orderBy('correlative', 'desc')
      ->first();

    // Si existe un correlativo previo, sumar 1; si no, usar el correlative_start
    $correlativeNumber = $lastShippingGuide
      ? ((int)$lastShippingGuide->correlative + 1)
      : $correlativeStart;

    // Formatear a 8 dígitos con ceros a la izquierda
    $correlative = str_pad($correlativeNumber, 8, '0', STR_PAD_LEFT);

    return [
      'correlative_number' => $correlativeNumber,
      'correlative' => $correlative,
    ];
  }
}
