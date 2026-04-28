<?php

namespace App\Models\ap\comercial;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerKycDeclaration extends BaseModel
{
    use SoftDeletes;

    protected $table = 'customer_kyc_declarations';

    protected $fillable = [
        'purchase_request_quote_id',
        'business_partner_id',
        'company_id',
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
        'created_by',
    ];

    protected $casts = [
        'declaration_date' => 'date',
        'pep_relatives' => 'array',
        'pep_relative_data' => 'array',
    ];

    const STATUS_PENDIENTE = 'PENDIENTE';
    const STATUS_GENERADO  = 'GENERADO';
    const STATUS_FIRMADO   = 'FIRMADO';
    const STATUSES = ['PENDIENTE', 'GENERADO', 'FIRMADO'];

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
        'company_id' => '=',
        'beneficiary_type' => '=',
        'status' => '=',
        'declaration_date' => '=',
    ];

    const sorts = [
        'declaration_date',
        'created_at',
    ];

    public function businessPartner()
    {
        return $this->belongsTo(BusinessPartners::class, 'business_partner_id');
    }

    public function purchaseRequestQuote()
    {
        return $this->belongsTo(PurchaseRequestQuote::class, 'purchase_request_quote_id');
    }
}
