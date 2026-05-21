<?php

namespace App\Models\ap\comercial;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $purchase_request_quote_id
 * @property int $business_partner_id
 * @property int|null $sede_id
 * @property string $person_type
 *
 * — Persona Natural —
 * @property string|null $occupation
 * @property string|null $fixed_phone
 * @property string|null $pep_status
 * @property string|null $pep_collaborator_status
 * @property string|null $pep_position
 * @property string|null $pep_institution
 * @property array|null $pep_relatives
 * @property string|null $pep_spouse_name
 * @property bool|null $is_pep_relative
 * @property array|null $pep_relative_data
 *
 * — Persona Jurídica —
 * @property string|null $company_name
 * @property string|null $ruc
 * @property string|null $foreign_registry_number
 * @property string|null $business_purpose
 * @property string|null $final_beneficiaries
 * @property string|null $rep_full_name
 * @property string|null $rep_doc_type
 * @property string|null $rep_doc_number
 * @property string|null $rep_doc_other
 * @property string|null $rep_representation_type
 * @property string|null $rep_instrument_type
 * @property \Illuminate\Support\Carbon|null $rep_escritura_date
 * @property string|null $rep_notary_name
 * @property \Illuminate\Support\Carbon|null $rep_acta_certified_date
 * @property \Illuminate\Support\Carbon|null $rep_acta_date
 * @property string|null $rep_instrument_other
 * @property string|null $rep_registry_partition
 * @property string|null $rep_registry_seat
 * @property string|null $rep_registry_section
 * @property string|null $rep_registry_zone
 * @property string|null $office_street_type
 * @property string|null $office_street_name
 * @property string|null $office_number
 * @property string|null $office_int_number
 * @property string|null $office_urbanization
 * @property int|null $office_district_id
 * @property string|null $office_phone
 * @property string|null $account_number
 *
 * — Compartidos —
 * @property string|null $purpose_relationship
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
 */
class CustomerKycDeclaration extends BaseModel
{
  use SoftDeletes;

  protected $table = 'customer_kyc_declarations';

  protected $fillable = [
    'purchase_request_quote_id',
    'business_partner_id',
    'sede_id',
    'person_type',

    // Persona Natural
    'occupation',
    'fixed_phone',
    'pep_status',
    'pep_collaborator_status',
    'pep_position',
    'pep_institution',
    'pep_relatives',
    'pep_spouse_name',
    'is_pep_relative',
    'pep_relative_data',

    // Persona Jurídica
    'company_name',
    'ruc',
    'foreign_registry_number',
    'business_purpose',
    'final_beneficiaries',
    'rep_full_name',
    'rep_doc_type',
    'rep_doc_number',
    'rep_doc_other',
    'rep_representation_type',
    'rep_instrument_type',
    'rep_escritura_date',
    'rep_notary_name',
    'rep_acta_certified_date',
    'rep_acta_date',
    'rep_instrument_other',
    'rep_registry_partition',
    'rep_registry_seat',
    'rep_registry_section',
    'rep_registry_zone',
    'office_street_type',
    'office_street_name',
    'office_number',
    'office_int_number',
    'office_urbanization',
    'office_district_id',
    'office_phone',
    'account_number',

    // Compartidos
    'purpose_relationship',
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
    'declaration_date'        => 'date',
    'rep_escritura_date'      => 'date',
    'rep_acta_certified_date' => 'date',
    'rep_acta_date'           => 'date',
    'legal_review_at'         => 'datetime',
    'pep_relatives'           => 'array',
    'pep_relative_data'       => 'array',
  ];

  const PERSON_TYPE_NATURAL  = 'NATURAL';
  const PERSON_TYPE_JURIDICA = 'JURIDICA';
  const PERSON_TYPES         = ['NATURAL', 'JURIDICA'];

  const STATUS_PENDIENTE = 'PENDIENTE';
  const STATUS_GENERADO  = 'GENERADO';
  const STATUS_FIRMADO   = 'FIRMADO';
  const STATUSES         = ['PENDIENTE', 'GENERADO', 'FIRMADO'];

  const LEGAL_REVIEW_STATUS_CONFIRMADO = 'CONFIRMADO';
  const LEGAL_REVIEW_STATUS_RECHAZADO  = 'RECHAZADO';
  const LEGAL_REVIEW_STATUSES          = ['CONFIRMADO', 'RECHAZADO'];

  // Persona Natural
  const PEP_STATUSES              = ['SI_SOY', 'SI_HE_SIDO', 'NO_SOY', 'NO_HE_SIDO'];
  const PEP_COLLABORATOR_STATUSES = ['SI_SOY', 'SI_HE_SIDO', 'NO_SOY', 'NO_HE_SIDO'];
  const PEP_RELATIVE_STATUSES     = ['SI_SOY', 'NO_SOY'];

  // Persona Jurídica
  const REP_DOC_TYPES            = ['DNI', 'PASAPORTE', 'CARNE_EXTRANJERIA', 'OTRO'];
  const REP_REPRESENTATION_TYPES = ['PODER', 'MANDATO'];
  const REP_INSTRUMENT_TYPES     = ['ESCRITURA_PUBLICA', 'COPIA_CERTIFICADA_ACTA', 'OTROS'];
  const OFFICE_STREET_TYPES      = ['JR', 'AV', 'CALLE', 'PASAJE', 'OVALO'];

  // Compartidos — beneficiario
  const BENEFICIARY_TYPES      = ['PROPIO', 'TERCERO_NATURAL', 'PERSONA_JURIDICA', 'ENTE_JURIDICO'];
  const THIRD_PEP_STATUSES     = ['SI_ES', 'SI_HA_SIDO', 'NO_ES', 'NO_HA_SIDO'];
  const ENTITY_REPRESENTATION_TYPES = ['PODER_POR_ACTA', 'ESCRITURA_PUBLICA', 'MANDATO'];
  // Natural: ESCRITURA_PUBLICA, MANDATO, PODER, OTROS | Jurídica: PODER_ESCRITURA_PUBLICA, MANDATO
  const THIRD_REPRESENTATION_TYPES  = ['ESCRITURA_PUBLICA', 'MANDATO', 'PODER', 'OTROS', 'PODER_ESCRITURA_PUBLICA'];

  const filters = [
    'purchase_request_quote_id' => '=',
    'business_partner_id'       => '=',
    'sede_id'                   => '=',
    'person_type'               => '=',
    'beneficiary_type'          => '=',
    'status'                    => '=',
    'legal_review_status'       => '=',
    'declaration_date'          => '=',
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

  public function isNatural(): bool
  {
    return $this->person_type === self::PERSON_TYPE_NATURAL;
  }

  public function isJuridica(): bool
  {
    return $this->person_type === self::PERSON_TYPE_JURIDICA;
  }

  public function businessPartner()
  {
    return $this->belongsTo(BusinessPartners::class, 'business_partner_id');
  }

  public function purchaseRequestQuote()
  {
    return $this->belongsTo(PurchaseRequestQuote::class, 'purchase_request_quote_id');
  }

  public function officeDistrict()
  {
    return $this->belongsTo(\App\Models\gp\gestionsistema\District::class, 'office_district_id');
  }

  public function reviewedBy()
  {
    return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
  }
}
