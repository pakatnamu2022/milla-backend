<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingCatalogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // 1. Tipos de Comprobante (Catálogo 01 SUNAT)
        DB::table('ap_billing_document_types')->insert([
            ['code' => '1', 'description' => 'Factura Electrónica', 'prefix' => 'F', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2', 'description' => 'Boleta de Venta Electrónica', 'prefix' => 'B', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '3', 'description' => 'Nota de Crédito Electrónica', 'prefix' => 'N', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4', 'description' => 'Nota de Débito Electrónica', 'prefix' => 'N', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 2. Tipos de Operación/Transacción (Catálogo 51 SUNAT)
        DB::table('ap_billing_transaction_types')->insert([
            ['code' => '01', 'description' => 'Venta Interna', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '02', 'description' => 'Exportación', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '03', 'description' => 'No Domiciliados', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '04', 'description' => 'Venta Interna - Anticipos', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '05', 'description' => 'Venta Itinerante', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '06', 'description' => 'Factura Guía', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '07', 'description' => 'Venta Arroz Pilado', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '08', 'description' => 'Factura - Comprobante de Percepción', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '10', 'description' => 'Factura - Guía remitente', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '11', 'description' => 'Factura - Guía transportista', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '12', 'description' => 'Boleta de Venta - Guía remitente', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '13', 'description' => 'Boleta de Venta - Guía transportista', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '14', 'description' => 'Venta de Bienes - Ley 30737', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '21', 'description' => 'Factura de Operación Sujeta a Detracción', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '31', 'description' => 'Guía de Remisión Remitente', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '32', 'description' => 'Guía de Remisión Transportista', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 3. Tipos de Documento de Identidad (Catálogo 06 SUNAT)
        DB::table('ap_billing_identity_document_types')->insert([
            ['code' => '0', 'description' => 'DOC.TRIB.NO.DOM.SIN.RUC', 'length' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1', 'description' => 'DNI', 'length' => 8, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4', 'description' => 'Carnet de Extranjería', 'length' => 12, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '6', 'description' => 'RUC', 'length' => 11, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '7', 'description' => 'Pasaporte', 'length' => 12, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'A', 'description' => 'Cédula Diplomática de Identidad', 'length' => 15, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'B', 'description' => 'DOC.IDENT.PAIS.RESIDENCIA-NO.D', 'length' => 15, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'C', 'description' => 'Tax Identification Number - TIN', 'length' => 15, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'D', 'description' => 'Identification Number', 'length' => 15, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'E', 'description' => 'TAM - Tarjeta Andina de Migración', 'length' => 15, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '-', 'description' => 'Sin Documento', 'length' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 4. Tipos de Afectación IGV (Catálogo 07 SUNAT)
        DB::table('ap_billing_igv_types')->insert([
            ['code' => '10', 'description' => 'Gravado - Operación Onerosa', 'tribute_code' => '1000', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '11', 'description' => 'Gravado - Retiro por premio', 'tribute_code' => '1000', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '12', 'description' => 'Gravado - Retiro por donación', 'tribute_code' => '1000', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '13', 'description' => 'Gravado - Retiro', 'tribute_code' => '1000', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '14', 'description' => 'Gravado - Retiro por publicidad', 'tribute_code' => '1000', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '15', 'description' => 'Gravado - Bonificaciones', 'tribute_code' => '1000', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '16', 'description' => 'Gravado - Retiro por entrega a trabajadores', 'tribute_code' => '1000', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '17', 'description' => 'Gravado - IVAP', 'tribute_code' => '1016', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '20', 'description' => 'Exonerado - Operación Onerosa', 'tribute_code' => '9997', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '21', 'description' => 'Exonerado - Transferencia Gratuita', 'tribute_code' => '9997', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '30', 'description' => 'Inafecto - Operación Onerosa', 'tribute_code' => '9998', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '31', 'description' => 'Inafecto - Retiro por Bonificación', 'tribute_code' => '9998', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '32', 'description' => 'Inafecto - Retiro', 'tribute_code' => '9998', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '33', 'description' => 'Inafecto - Retiro por Muestras Médicas', 'tribute_code' => '9998', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '34', 'description' => 'Inafecto - Retiro por Convenio Colectivo', 'tribute_code' => '9998', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '35', 'description' => 'Inafecto - Retiro por premio', 'tribute_code' => '9998', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '36', 'description' => 'Inafecto - Retiro por publicidad', 'tribute_code' => '9998', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '37', 'description' => 'Inafecto - Transferencia Gratuita', 'tribute_code' => '9998', 'affects_total' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '40', 'description' => 'Exportación de Bienes o Servicios', 'tribute_code' => '9995', 'affects_total' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 5. Tipos de Nota de Crédito (Catálogo 09 SUNAT)
        DB::table('ap_billing_credit_note_types')->insert([
            ['code' => '01', 'description' => 'Anulación de la operación', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '02', 'description' => 'Anulación por error en el RUC', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '03', 'description' => 'Corrección por error en la descripción', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '04', 'description' => 'Descuento global', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '05', 'description' => 'Descuento por ítem', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '06', 'description' => 'Devolución total', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '07', 'description' => 'Devolución por ítem', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '08', 'description' => 'Bonificación', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '09', 'description' => 'Disminución en el valor', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '10', 'description' => 'Otros conceptos', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '11', 'description' => 'Ajustes de operaciones de exportación', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '12', 'description' => 'Ajustes afectos al IVAP', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '13', 'description' => 'Corrección del monto neto pendiente de pago y/o la(s) fecha(s) de vencimiento del pago único o de las cuotas y/o los montos correspondientes a cada cuota, de ser el caso', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 6. Tipos de Nota de Débito (Catálogo 10 SUNAT)
        DB::table('ap_billing_debit_note_types')->insert([
            ['code' => '1', 'description' => 'Intereses por mora', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2', 'description' => 'Aumento en el valor', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '3', 'description' => 'Penalidades/ otros conceptos', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4', 'description' => 'Ajustes de operaciones de exportación', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5', 'description' => 'Ajustes afectos al IVAP', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 7. Monedas (Códigos ISO 4217)
        DB::table('ap_billing_currencies')->insert([
            ['code' => '1', 'iso_code' => 'PEN', 'description' => 'Soles', 'symbol' => 'S/', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2', 'iso_code' => 'USD', 'description' => 'Dólares Americanos', 'symbol' => '$', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '3', 'iso_code' => 'EUR', 'description' => 'Euros', 'symbol' => '€', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4', 'iso_code' => 'GBP', 'description' => 'Libras Esterlinas', 'symbol' => '£', 'is_active' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // 8. Tipos de Detracción (Anexo 3 SUNAT - Principales)
        DB::table('ap_billing_detraction_types')->insert([
            ['code' => '001', 'description' => 'Azúcar', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '002', 'description' => 'Arroz pilado', 'percentage' => 3.850, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '003', 'description' => 'Alcohol etílico', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '004', 'description' => 'Recursos hidrobiológicos', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '005', 'description' => 'Maíz amarillo duro', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '006', 'description' => 'Algodón', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '007', 'description' => 'Caña de azúcar', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '008', 'description' => 'Madera', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '009', 'description' => 'Arena y piedra', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '010', 'description' => 'Residuos, subproductos, desechos, recortes y desperdicios', 'percentage' => 15.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '011', 'description' => 'Bienes del inciso A) del Apéndice I de la Ley del IGV', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '012', 'description' => 'Intermediación laboral y tercerización', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '013', 'description' => 'Animales vivos', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '014', 'description' => 'Carnes y despojos comestibles', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '015', 'description' => 'Abonos, cueros y pieles de origen animal', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '016', 'description' => 'Aceite de pescado', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '017', 'description' => 'Harina, polvo y pellets de pescado', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '018', 'description' => 'Embarcaciones pesqueras', 'percentage' => 2.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '019', 'description' => 'Arrendamiento de bienes muebles', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '020', 'description' => 'Mantenimiento y reparación de bienes muebles', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '021', 'description' => 'Movimiento de carga', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '022', 'description' => 'Otros servicios empresariales', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '023', 'description' => 'Leche', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '024', 'description' => 'Comisión mercantil', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '025', 'description' => 'Fabricación de bienes por encargo', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '026', 'description' => 'Servicio de transporte de personas', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '027', 'description' => 'Transporte de bienes por vía terrestre', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '028', 'description' => 'Transporte público de pasajeros realizado por vía terrestre', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '030', 'description' => 'Contratos de construcción', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '031', 'description' => 'Oro gravado con el IGV', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '032', 'description' => 'Páprika y otros frutos de los géneros capsicum o pimienta', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '033', 'description' => 'Espárragos', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '034', 'description' => 'Minerales metálicos no auríferos', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '035', 'description' => 'Bienes exonerados del IGV', 'percentage' => 1.500, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '036', 'description' => 'Oro y demás minerales metálicos exonerados del IGV', 'percentage' => 1.500, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '037', 'description' => 'Demás servicios gravados con el IGV', 'percentage' => 12.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '039', 'description' => 'Minerales no metálicos', 'percentage' => 10.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '040', 'description' => 'Bien inmueble gravado con el IGV', 'percentage' => 4.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '041', 'description' => 'Plomo', 'percentage' => 15.000, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $this->command->info('Catálogos de facturación electrónica cargados correctamente.');
    }
}
