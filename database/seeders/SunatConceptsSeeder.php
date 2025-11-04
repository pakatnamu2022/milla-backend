<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\gp\maestroGeneral\SunatConcepts;

class SunatConceptsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    SunatConcepts::query()->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    $now = Carbon::now();

    // ========================================
    // TIPOS ORIGINALES DE SUNAT CONCEPTS
    // ========================================

    // Tipos de Documentos de Identidad (Catálogo 06 SUNAT) - Ampliado con nuevos registros
    $typeDocument = [
      ['status' => 0, 'code_nubefact' => '0', 'description' => 'DOC.TRIB.NO.DOM.SIN.RUC', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => null],
      ['status' => 1, 'code_nubefact' => '1', 'description' => 'DNI - DOC. NACIONAL DE IDENTIDAD', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 8],
      ['status' => 1, 'code_nubefact' => '4', 'description' => 'CARNET DE EXTRANJERÍA', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 12],
      ['status' => 1, 'code_nubefact' => '6', 'description' => 'RUC - REGISTRO ÚNICO DE CONTRIBUYENTE', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 11],
      ['status' => 0, 'code_nubefact' => '7', 'description' => 'PASAPORTE', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 12],
      ['status' => 0, 'code_nubefact' => 'A', 'description' => 'CÉDULA DIPLOMÁTICA DE IDENTIDAD', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 15],
      ['status' => 0, 'code_nubefact' => 'B', 'description' => 'DOC.IDENT.PAIS.RESIDENCIA-NO.D', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 15],
      ['status' => 0, 'code_nubefact' => 'C', 'description' => 'Tax Identification Number - TIN', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 15],
      ['status' => 0, 'code_nubefact' => 'D', 'description' => 'Identification Number', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 15],
      ['status' => 0, 'code_nubefact' => 'E', 'description' => 'TAM - Tarjeta Andina de Migración', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => 15],
      ['status' => 0, 'code_nubefact' => '-', 'description' => 'Sin Documento', 'type' => SunatConcepts::TYPE_DOCUMENT, 'length' => null],
    ];

    // Tipos de Guías de Remisión (Catálogo 18 SUNAT)
    $typeVoucher = [
      ['code_nubefact' => '7', 'description' => 'GUÍA REMISIÓN REMITENTE', 'type' => SunatConcepts::TYPE_VOUCHER],
      ['code_nubefact' => '8', 'description' => 'GUÍA REMISIÓN TRANSPORTISTA', 'type' => SunatConcepts::TYPE_VOUCHER],
    ];

    // Motivos de Traslado (Catálogo 19 SUNAT)
    $transferReasons = [
      ['code_nubefact' => '01', 'description' => 'VENTA', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '02', 'description' => 'COMPRA', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '03', 'description' => 'VENTA CON ENTREGA A TERCEROS', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '04', 'description' => 'TRASLADO ENTRE ESTABLECIMIENTOS DE LA MISMA EMPRESA', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '05', 'description' => 'CONSIGNACION', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '06', 'description' => 'DEVOLUCION', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '07', 'description' => 'RECOJO DE BIENES TRANSFORMADOS', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '08', 'description' => 'IMPORTACION', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '09', 'description' => 'EXPORTACION', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '13', 'description' => 'OTROS', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '14', 'description' => 'VENTA SUJETA A CONFIRMACION DEL COMPRADOR', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '17', 'description' => 'TRASLADO DE BIENES PARA TRANSFORMACION', 'type' => SunatConcepts::TRANSFER_REASON],
      ['code_nubefact' => '18', 'description' => 'TRASLADO EMISOR ITINERANTE CP', 'type' => SunatConcepts::TRANSFER_REASON],
    ];

    // Modalidad de Transporte (Catálogo 18 SUNAT)
    $typeTransportation = [
      ['code_nubefact' => '01', 'description' => 'TRANSPORTE PÚBLICO', 'type' => SunatConcepts::TYPE_TRANSPORTATION],
      ['code_nubefact' => '02', 'description' => 'TRANSPORTE PRIVADO', 'type' => SunatConcepts::TYPE_TRANSPORTATION],
    ];

    // ========================================
    // TIPOS NUEVOS (MIGRADOS DE BILLING CATALOGS)
    // ========================================

    // 1. Tipos de Comprobante (Catálogo 01 SUNAT)
    $billingDocumentTypes = [
      ['code_nubefact' => '1', 'description' => 'Factura Electrónica', 'type' => SunatConcepts::BILLING_DOCUMENT_TYPE, 'prefix' => 'F'],
      ['code_nubefact' => '2', 'description' => 'Boleta de Venta Electrónica', 'type' => SunatConcepts::BILLING_DOCUMENT_TYPE, 'prefix' => 'B'],
      ['code_nubefact' => '3', 'description' => 'Nota de Crédito Electrónica', 'type' => SunatConcepts::BILLING_DOCUMENT_TYPE, 'prefix' => 'N'],
      ['code_nubefact' => '4', 'description' => 'Nota de Débito Electrónica', 'type' => SunatConcepts::BILLING_DOCUMENT_TYPE, 'prefix' => 'N'],
    ];

    // 2. Tipos de Operación/Transacción (Catálogo 51 SUNAT)
    $billingTransactionTypes = [
      ['status' => 1, 'code_nubefact' => '01', 'description' => 'Venta Interna', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '02', 'description' => 'Exportación', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '03', 'description' => 'No Domiciliados', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 1, 'code_nubefact' => '04', 'description' => 'Venta Interna - Anticipos', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '05', 'description' => 'Venta Itinerante', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '06', 'description' => 'Factura Guía', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '07', 'description' => 'Venta Arroz Pilado', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '08', 'description' => 'Factura - Comprobante de Percepción', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '10', 'description' => 'Factura - Guía remitente', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '11', 'description' => 'Factura - Guía transportista', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '12', 'description' => 'Boleta de Venta - Guía remitente', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '13', 'description' => 'Boleta de Venta - Guía transportista', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '14', 'description' => 'Venta de Bienes - Ley 30737', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '21', 'description' => 'Factura de Operación Sujeta a Detracción', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '31', 'description' => 'Guía de Remisión Remitente', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
      ['status' => 0, 'code_nubefact' => '32', 'description' => 'Guía de Remisión Transportista', 'type' => SunatConcepts::BILLING_TRANSACTION_TYPE],
    ];

    // 3. Tipos de Afectación IGV (Catálogo 07 SUNAT)
    $billingIgvTypes = [
      ['code_nubefact' => '10', 'description' => 'Gravado - Operación Onerosa', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1000', 'affects_total' => true],
      ['code_nubefact' => '11', 'description' => 'Gravado - Retiro por premio', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1000', 'affects_total' => true],
      ['code_nubefact' => '12', 'description' => 'Gravado - Retiro por donación', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1000', 'affects_total' => true],
      ['code_nubefact' => '13', 'description' => 'Gravado - Retiro', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1000', 'affects_total' => true],
      ['code_nubefact' => '14', 'description' => 'Gravado - Retiro por publicidad', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1000', 'affects_total' => true],
      ['code_nubefact' => '15', 'description' => 'Gravado - Bonificaciones', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1000', 'affects_total' => true],
      ['code_nubefact' => '16', 'description' => 'Gravado - Retiro por entrega a trabajadores', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1000', 'affects_total' => true],
      ['code_nubefact' => '17', 'description' => 'Gravado - IVAP', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '1016', 'affects_total' => true],
      ['code_nubefact' => '20', 'description' => 'Exonerado - Operación Onerosa', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9997', 'affects_total' => true],
      ['code_nubefact' => '21', 'description' => 'Exonerado - Transferencia Gratuita', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9997', 'affects_total' => false],
      ['code_nubefact' => '30', 'description' => 'Inafecto - Operación Onerosa', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => true],
      ['code_nubefact' => '31', 'description' => 'Inafecto - Retiro por Bonificación', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => false],
      ['code_nubefact' => '32', 'description' => 'Inafecto - Retiro', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => false],
      ['code_nubefact' => '33', 'description' => 'Inafecto - Retiro por Muestras Médicas', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => false],
      ['code_nubefact' => '34', 'description' => 'Inafecto - Retiro por Convenio Colectivo', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => false],
      ['code_nubefact' => '35', 'description' => 'Inafecto - Retiro por premio', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => false],
      ['code_nubefact' => '36', 'description' => 'Inafecto - Retiro por publicidad', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => false],
      ['code_nubefact' => '37', 'description' => 'Inafecto - Transferencia Gratuita', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9998', 'affects_total' => false],
      ['code_nubefact' => '40', 'description' => 'Exportación de Bienes o Servicios', 'type' => SunatConcepts::BILLING_IGV_TYPE, 'tribute_code' => '9995', 'affects_total' => true],
    ];

    // 4. Tipos de Nota de Crédito (Catálogo 09 SUNAT)
    $billingCreditNoteTypes = [
      ['code_nubefact' => '01', 'description' => 'Anulación de la operación', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '02', 'description' => 'Anulación por error en el RUC', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '03', 'description' => 'Corrección por error en la descripción', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '04', 'description' => 'Descuento global', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '05', 'description' => 'Descuento por ítem', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '06', 'description' => 'Devolución total', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '07', 'description' => 'Devolución por ítem', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '08', 'description' => 'Bonificación', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '09', 'description' => 'Disminución en el valor', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '10', 'description' => 'Otros conceptos', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '11', 'description' => 'Ajustes de operaciones de exportación', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '12', 'description' => 'Ajustes afectos al IVAP', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
      ['code_nubefact' => '13', 'description' => 'Corrección del monto neto pendiente de pago y/o la(s) fecha(s) de vencimiento del pago único o de las cuotas y/o los montos correspondientes a cada cuota, de ser el caso', 'type' => SunatConcepts::BILLING_CREDIT_NOTE_TYPE],
    ];

    // 5. Tipos de Nota de Débito (Catálogo 10 SUNAT)
    $billingDebitNoteTypes = [
      ['code_nubefact' => '1', 'description' => 'Intereses por mora', 'type' => SunatConcepts::BILLING_DEBIT_NOTE_TYPE],
      ['code_nubefact' => '2', 'description' => 'Aumento en el valor', 'type' => SunatConcepts::BILLING_DEBIT_NOTE_TYPE],
      ['code_nubefact' => '3', 'description' => 'Penalidades/ otros conceptos', 'type' => SunatConcepts::BILLING_DEBIT_NOTE_TYPE],
      ['code_nubefact' => '4', 'description' => 'Ajustes de operaciones de exportación', 'type' => SunatConcepts::BILLING_DEBIT_NOTE_TYPE],
      ['code_nubefact' => '5', 'description' => 'Ajustes afectos al IVAP', 'type' => SunatConcepts::BILLING_DEBIT_NOTE_TYPE],
    ];

    // 6. Monedas (Códigos ISO 4217)
    $billingCurrencies = [
      ['code_nubefact' => '1', 'description' => 'Soles', 'type' => SunatConcepts::BILLING_CURRENCY, 'iso_code' => 'PEN', 'symbol' => 'S/'],
      ['code_nubefact' => '2', 'description' => 'Dólares Americanos', 'type' => SunatConcepts::BILLING_CURRENCY, 'iso_code' => 'USD', 'symbol' => '$'],
      ['code_nubefact' => '3', 'description' => 'Euros', 'type' => SunatConcepts::BILLING_CURRENCY, 'iso_code' => 'EUR', 'symbol' => '€'],
      ['code_nubefact' => '4', 'description' => 'Libras Esterlinas', 'type' => SunatConcepts::BILLING_CURRENCY, 'iso_code' => 'GBP', 'symbol' => '£'],
    ];

    // 7. Tipos de Detracción (Anexo 3 SUNAT)
    $billingDetractionTypes = [
      ['code_nubefact' => '001', 'description' => 'Azúcar', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '002', 'description' => 'Arroz pilado', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 3.850],
      ['code_nubefact' => '003', 'description' => 'Alcohol etílico', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '004', 'description' => 'Recursos hidrobiológicos', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '005', 'description' => 'Maíz amarillo duro', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '006', 'description' => 'Algodón', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '007', 'description' => 'Caña de azúcar', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '008', 'description' => 'Madera', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '009', 'description' => 'Arena y piedra', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '010', 'description' => 'Residuos, subproductos, desechos, recortes y desperdicios', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 15.000],
      ['code_nubefact' => '011', 'description' => 'Bienes del inciso A) del Apéndice I de la Ley del IGV', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '012', 'description' => 'Intermediación laboral y tercerización', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '013', 'description' => 'Animales vivos', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '014', 'description' => 'Carnes y despojos comestibles', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '015', 'description' => 'Abonos, cueros y pieles de origen animal', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '016', 'description' => 'Aceite de pescado', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '017', 'description' => 'Harina, polvo y pellets de pescado', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '018', 'description' => 'Embarcaciones pesqueras', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 2.000],
      ['code_nubefact' => '019', 'description' => 'Arrendamiento de bienes muebles', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '020', 'description' => 'Mantenimiento y reparación de bienes muebles', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '021', 'description' => 'Movimiento de carga', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '022', 'description' => 'Otros servicios empresariales', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '023', 'description' => 'Leche', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '024', 'description' => 'Comisión mercantil', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '025', 'description' => 'Fabricación de bienes por encargo', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '026', 'description' => 'Servicio de transporte de personas', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '027', 'description' => 'Transporte de bienes por vía terrestre', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '028', 'description' => 'Transporte público de pasajeros realizado por vía terrestre', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '030', 'description' => 'Contratos de construcción', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '031', 'description' => 'Oro gravado con el IGV', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '032', 'description' => 'Páprika y otros frutos de los géneros capsicum o pimienta', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '033', 'description' => 'Espárragos', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '034', 'description' => 'Minerales metálicos no auríferos', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '035', 'description' => 'Bienes exonerados del IGV', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 1.500],
      ['code_nubefact' => '036', 'description' => 'Oro y demás minerales metálicos exonerados del IGV', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 1.500],
      ['code_nubefact' => '037', 'description' => 'Demás servicios gravados con el IGV', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 12.000],
      ['code_nubefact' => '039', 'description' => 'Minerales no metálicos', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 10.000],
      ['code_nubefact' => '040', 'description' => 'Bien inmueble gravado con el IGV', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 4.000],
      ['code_nubefact' => '041', 'description' => 'Plomo', 'type' => SunatConcepts::BILLING_DETRACTION_TYPE, 'percentage' => 15.000],
    ];

    // ========================================
    // COMBINAR Y PREPARAR DATOS
    // ========================================

    // Combinar todos los conceptos
    $concepts = array_merge(
      $typeDocument,
      $typeVoucher,
      $transferReasons,
      $typeTransportation,
      $billingDocumentTypes,
      $billingTransactionTypes,
      $billingIgvTypes,
      $billingCreditNoteTypes,
      $billingDebitNoteTypes,
      $billingCurrencies,
      $billingDetractionTypes
    );

    // Agregar timestamps y status por defecto a cada concepto
    foreach ($concepts as &$concept) {
      $concept['status'] = $concept['status'] ?? 1; // Valor por defecto 1 (activo) si no está definido
      $concept['created_at'] = $now;
      $concept['updated_at'] = $now;

      SunatConcepts::create($concept);
    }

    $this->command->info('Conceptos SUNAT consolidados insertados correctamente!');
    $this->command->info('Total de registros: ' . count($concepts));
    $this->command->info('');
    $this->command->info('Resumen por tipo:');
    $this->command->info('- TYPE_DOCUMENT: 11 registros');
    $this->command->info('- TYPE_VOUCHER: 2 registros');
    $this->command->info('- TRANSFER_REASON: 13 registros');
    $this->command->info('- TYPE_TRANSPORTATION: 2 registros');
    $this->command->info('- BILLING_DOCUMENT_TYPE: 4 registros');
    $this->command->info('- BILLING_TRANSACTION_TYPE: 16 registros');
    $this->command->info('- BILLING_IGV_TYPE: 19 registros');
    $this->command->info('- BILLING_CREDIT_NOTE_TYPE: 13 registros');
    $this->command->info('- BILLING_DEBIT_NOTE_TYPE: 5 registros');
    $this->command->info('- BILLING_CURRENCY: 4 registros');
    $this->command->info('- BILLING_DETRACTION_TYPE: 41 registros');
  }
}
