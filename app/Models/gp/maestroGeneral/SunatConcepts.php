<?php

namespace App\Models\gp\maestroGeneral;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\maestroGeneral\TypeCurrency;
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
    'status'
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

  // IDs específicos de tipos de documentos de facturación (basados en seeder)
  const ID_FACTURA_ELECTRONICA = 29;          // Factura Electrónica (code: 1)
  const ID_BOLETA_VENTA_ELECTRONICA = 30;     // Boleta de Venta Electrónica (code: 2)
  const ID_NOTA_CREDITO_ELECTRONICA = 31;     // Nota de Crédito Electrónica (code: 3)
  const ID_NOTA_DEBITO_ELECTRONICA = 32;      // Nota de Débito Electrónica (code: 4)

  // IDs específicos de tipos de transacción (basados en query)
  const ID_VENTA_INTERNA = 33;                // Venta Interna (code: 01)
  const ID_VENTA_INTERNA_ANTICIPOS = 36;      // Venta Interna - Anticipos (code: 04)

  // IDs específicos de tipos de IGV (basados en query)
  const ID_IGV_GRAVADO_ONEROSA = 49;          // Gravado - Operación Onerosa (code: 10, tribute: 1000) - SUNAT
  const ID_IGV_EXPORTACION = 67;              // Exportación de Bienes o Servicios (code: 40, tribute: 9995)
  const ID_IGV_ANTICIPO_GRAVADO = 130;        // Gravado - Operación Onerosa (code: 1, tribute: 1000) - Código Nubefact para anticipos
  // Nota: El tributo 9996 se genera automáticamente cuando sunat_transaction = 04

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

  const GUIA_REMISION_REMITENTE = 16;

  const RUC_CODE_NUBEFACT = 6;

  public function documentType()
  {
    return $this->belongsTo(ApCommercialMasters::class, 'tribute_code');
  }

  public function currencyType()
  {
    return $this->belongsTo(TypeCurrency::class, 'tribute_code');
  }
}
