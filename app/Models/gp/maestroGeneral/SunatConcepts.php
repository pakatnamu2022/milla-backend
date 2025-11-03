<?php

namespace App\Models\gp\maestroGeneral;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SunatConcepts extends Model
{
  use softDeletes;

  protected $table = 'sunat_concepts';

  protected $fillable = [
    'code_nubefact',
    'description',
    'type',
    'status',
    'prefix',
    'length',
    'tribute_code',
    'affects_total',
    'iso_code',
    'symbol',
    'percentage',
  ];

  // Tipos actuales (de SunatConceptsSeeder original)
  const TYPE_DOCUMENT = 'TYPE_DOCUMENT'; // Tipos de documentos de identidad (DNI, RUC, etc.)
  const TYPE_VOUCHER = 'TYPE_VOUCHER'; // Tipos de guías de remisión
  const TRANSFER_REASON = 'TRANSFER_REASON'; // Motivos de traslado
  const TYPE_TRANSPORTATION = 'TYPE_TRANSPORTATION'; // Modalidad de transporte

  // Tipos nuevos (migrados de BillingCatalogsSeeder)
  const BILLING_DOCUMENT_TYPE = 'BILLING_DOCUMENT_TYPE'; // Factura, Boleta, NC, ND
  const BILLING_TRANSACTION_TYPE = 'BILLING_TRANSACTION_TYPE'; // Tipos de operación/transacción
  const BILLING_IGV_TYPE = 'BILLING_IGV_TYPE'; // Tipos de afectación IGV
  const BILLING_CREDIT_NOTE_TYPE = 'BILLING_CREDIT_NOTE_TYPE'; // Tipos de notas de crédito
  const BILLING_DEBIT_NOTE_TYPE = 'BILLING_DEBIT_NOTE_TYPE'; // Tipos de notas de débito
  const BILLING_CURRENCY = 'BILLING_CURRENCY'; // Monedas
  const BILLING_DETRACTION_TYPE = 'BILLING_DETRACTION_TYPE'; // Tipos de detracción

  const filters = [
    'id' => '=',
    'search' => ['code_nubefact', 'description', 'type'],
    'type' => 'in',
    'status' => '=',
  ];

  const sorts = [
    'id',
    'code_nubefact',
    'description',
    'type',
    'status',
  ];

  const GUIA_REMISION_REMITENTE = 68;

  const RUC_CODE_NUBEFACT = 6;
}
