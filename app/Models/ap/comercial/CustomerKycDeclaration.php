<?php

namespace App\Models\ap\comercial;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
   * @property int|null $purchase_request_quote_id
   * @property int|null $business_partner_id
   * @property int|null $sede_id
   * @property string|null $occupation
   * @property string|null $fixed_phone
   * @property string|null $purpose_relationship
   * @property string|null $pep_status
   * @property string|null $pep_collaborator_status
   * @property string|null $pep_position
   * @property string|null $pep_institution
   * @property array|null $pep_relatives
   * @property string|null $pep_spouse_name
   * @property bool|null $is_pep_relative
   * @property array|null $pep_relative_data
   * @property string|null $beneficiary_type
   * @property string|null $own_funds_origin
   * @property string|null $third_full_name
   * @property string|null $third_doc_type
   * @property string|null $third_doc_number
   * @property string|null $third_representation_type
   * @property string|null $third_pep_status
   * @property string|null $third_pep_position
   * @property string|null $third_pep_institution
   * @property string|null $third_funds_origin
   * @property string|null $entity_name
   * @property string|null $entity_ruc
   * @property string|null $entity_representation_type
   * @property string|null $entity_funds_origin
   * @property string|null $entity_final_beneficiary
   * @property \Illuminate\Support\Carbon|null $declaration_date
   * @property string|null $status
   * @property string|null $signed_file_path
   * @property string|null $legal_review_status
   * @property string|null $legal_review_comments
   * @property int|null $reviewed_by
   * @property \Illuminate\Support\Carbon|null $legal_review_at
 * @property int|null $created_by
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ap\comercial\CustomerKycDeclaration whereStatus($value)
 */
class CustomerKycDeclaration extends BaseModel
{
  use SoftDeletes;

  protected $table = 'customer_kyc_declarations';

  protected $fillable = [
    'purchase_request_quote_id',
    'business_partner_id',
    'sede_id',
    'occupation',
    'fixed_phone',
    'purpose_relationship',
    'pep_status',
    'pep_collaborator_status',
    'pep_position',
    'pep_institution',
    'pep_relatives',
    'pep_spouse_name',
    'is_pep_relative',
    'pep_relative_data',
    'beneficiary_type',
    'own_funds_origin',
    'third_full_name',
    'third_doc_type',
    'third_doc_number',
    'third_representation_type',
    'third_pep_status',
    'third_pep_position',
    'third_pep_institution',
    'third_funds_origin',
    'entity_name',
    'entity_ruc',
    'entity_representation_type',
    'entity_funds_origin',
    'entity_final_beneficiary',
    'declaration_date',
    'status',
    'signed_file_path',
    'legal_review_status',
    'legal_review_comments',
    'reviewed_by',
    'legal_review_at',
    'created_by',
  ];

  protected $guarded = [];

  protected $casts = [
    'declaration_date' => 'date',
    'legal_review_at' => 'datetime',
    'pep_relatives' => 'array',
    'pep_relative_data' => 'array',
  ];

  const STATUS_PENDIENTE = 'PENDIENTE';
  const STATUS_GENERADO = 'GENERADO';
  const STATUS_FIRMADO = 'FIRMADO';
  const STATUSES = ['PENDIENTE', 'GENERADO', 'FIRMADO'];

  const LEGAL_REVIEW_STATUS_CONFIRMADO = 'CONFIRMADO';
  const LEGAL_REVIEW_STATUS_RECHAZADO = 'RECHAZADO';
  const LEGAL_REVIEW_STATUSES = ['CONFIRMADO', 'RECHAZADO'];

  const PEP_STATUSES = ['SI_SOY', 'SI_HE_SIDO', 'NO_SOY', 'NO_HE_SIDO'];
  const PEP_COLLABORATOR_STATUSES = ['SI_SOY', 'SI_HE_SIDO', 'NO_SOY', 'NO_HE_SIDO'];
  const PEP_RELATIVE_STATUSES = ['SI_SOY', 'NO_SOY'];
  const THIRD_PEP_STATUSES = ['SI_ES', 'SI_HA_SIDO', 'NO_ES', 'NO_HA_SIDO'];
  const BENEFICIARY_TYPES = ['PROPIO', 'TERCERO_NATURAL', 'PERSONA_JURIDICA', 'ENTE_JURIDICO'];
  const REPRESENTATION_TYPES = ['ESCRITURA_PUBLICA', 'MANDATO', 'PODER', 'OTROS'];
  const ENTITY_REPRESENTATION_TYPES = ['PODER_POR_ACTA', 'ESCRITURA_PUBLICA', 'MANDATO'];

  const filters = [
    'purchase_request_quote_id' => '=',
    'business_partner_id' => '=',
    'sede_id' => '=',
    'beneficiary_type' => '=',
    'status' => '=',
    'legal_review_status' => '=',
    'declaration_date' => '=',
  ];

  const sorts = [
    'declaration_date',
    'legal_review_at',
    'created_at',
  ];

  public function setAttribute($key, $value)
  {
    if (is_string($value) && $key !== 'signed_file_path') {
      $value = strtoupper($value);
    }
    return parent::setAttribute($key, $value);
  }

  public function businessPartner()
  {
    return $this->belongsTo(BusinessPartners::class, 'business_partner_id');
  }

  public function purchaseRequestQuote()
  {
    return $this->belongsTo(PurchaseRequestQuote::class, 'purchase_request_quote_id');
  }

  public function reviewedBy()
  {
    return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
  }
}
