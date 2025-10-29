<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SunatConceptsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $now = Carbon::now();

    // Motivos de traslado (Catálogo 20 SUNAT)
    $typeDocument = [
      ['code_nubefact' => '6', 'description' => 'RUC - REGISTRO ÚNICO DE CONTRIBUYENTE', 'type' => 'TYPE_DOCUMENT'],
      ['code_nubefact' => '1', 'description' => 'DNI - DOC. NACIONAL DE IDENTIDAD', 'type' => 'TYPE_DOCUMENT'],
      ['code_nubefact' => '4', 'description' => 'CARNET DE EXTRANJERÍA', 'type' => 'TYPE_DOCUMENT'],
      ['code_nubefact' => '7', 'description' => 'PASAPORTE', 'type' => 'TYPE_DOCUMENT'],
      ['code_nubefact' => 'A', 'description' => 'CÉDULA DIPLOMÁTICA DE IDENTIDAD', 'type' => 'TYPE_DOCUMENT'],
      ['code_nubefact' => '0', 'description' => 'NO DOMICILIADO, SIN RUC (EXPORTACIÓN)', 'type' => 'TYPE_DOCUMENT'],
    ];

    // Modalidades de transporte (Catálogo 18 SUNAT)
    $transportModalities = [
      ['code_nubefact' => '7', 'description' => 'GUÍA REMISIÓN REMITENTE', 'type' => 'TYPE_VOUCHER'],
      ['code_nubefact' => '8', 'description' => 'GUÍA REMISIÓN TRANSPORTISTA', 'type' => 'TYPE_VOUCHER'],
    ];

    // Motivo de traslado (Catálogo 19 SUNAT)
    $transferReasons = [
      ['code_nubefact' => '01', 'description' => 'VENTA', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '14', 'description' => 'VENTA SUJETA A CONFIRMACION DEL COMPRADOR', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '02', 'description' => 'COMPRA', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '04', 'description' => 'TRASLADO ENTRE ESTABLECIMIENTOS DE LA MISMA EMPRESA', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '18', 'description' => 'TRASLADO EMISOR ITINERANTE CP', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '08', 'description' => 'IMPORTACION', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '09', 'description' => 'EXPORTACION', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '13', 'description' => 'OTROS', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '05', 'description' => 'CONSIGNACION', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '17', 'description' => 'TRASLADO DE BIENES PARA TRANSFORMACION', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '03', 'description' => 'VENTA CON ENTREGA A TERCEROS', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '06', 'description' => 'DEVOLUCION', 'type' => 'TRANSFER_REASON'],
      ['code_nubefact' => '07', 'description' => 'RECOJO DE BIENES TRANSFORMADOS', 'type' => 'TRANSFER_REASON'],
    ];

    $typeTransportation = [
      ['code_nubefact' => '01', 'description' => 'TRANSPORTE PÚBLICO', 'type' => 'TYPE_TRANSPORTATION'],
      ['code_nubefact' => '02', 'description' => 'TRANSPORTE PRIVADO', 'type' => 'TYPE_TRANSPORTATION'],
    ];

    // Combinar todos los conceptos
    $concepts = array_merge($typeDocument, $transportModalities, $transferReasons, $typeTransportation);

    // Agregar timestamps y status por defecto a cada concepto
    foreach ($concepts as &$concept) {
      $concept['status'] = true;
      $concept['created_at'] = $now;
      $concept['updated_at'] = $now;
    }

    // Insertar en la base de datos
    DB::table('sunat_concepts')->insert($concepts);

    $this->command->info('Conceptos SUNAT insertados correctamente!');
    $this->command->info('Total de registros: ' . count($concepts));
  }
}
