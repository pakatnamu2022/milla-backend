<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditOptionsSeeder extends Seeder
{
  public function run(): void
  {
    $now = now();

    // CREDIT_TYPE — sin parent
    $creditTypes = [
      ['code' => 'CREDITO_INCHCAPE', 'description' => 'CREDITO INCHCAPE'],
      ['code' => 'FONDO_COLECTIVO',  'description' => 'FONDO COLECTIVO'],
      ['code' => 'CAJAS',            'description' => 'CAJAS'],
      ['code' => 'CREDITO_PROPIO',   'description' => 'CREDITO PROPIO'],
      ['code' => 'LEASING',          'description' => 'LEASING'],
    ];

    $typeIds = [];
    foreach ($creditTypes as $type) {
      $id = DB::table('ap_masters')->insertGetId(array_merge($type, [
        'type'       => 'CREDIT_TYPE',
        'parent_id'  => null,
        'status'     => 1,
        'created_at' => $now,
        'updated_at' => $now,
      ]));
      $typeIds[$type['code']] = $id;
    }

    // CREDIT_ENTITY — parent_id apunta al CREDIT_TYPE correspondiente
    $creditEntities = [
      'CREDITO_INCHCAPE' => ['SANTANDER', 'BCP', 'BBVA', 'BANBIF', 'MI BANCO'],
      'FONDO_COLECTIVO'  => ['MAQUISISTEMA', 'MI FONDO', 'AUTOPLAN', 'FONBIENES', 'PANDERO', 'OPCION'],
      'CAJAS'            => ['CAJA PIURA', 'CAJA SULLANA', 'CAJA HUANCAYO', 'CAJA TRUJILLO'],
      'CREDITO_PROPIO'   => ['INTERBANK', 'SCOTIABANK'],
      'LEASING'          => ['BCP', 'BBVA', 'INTERBANK', 'SCOTIABANK'],
    ];

    foreach ($creditEntities as $typeCode => $entities) {
      foreach ($entities as $entity) {
        DB::table('ap_masters')->insert([
          'code'        => strtoupper(str_replace(' ', '_', $entity)),
          'description' => $entity,
          'type'        => 'CREDIT_ENTITY',
          'parent_id'   => $typeIds[$typeCode],
          'status'      => 1,
          'created_at'  => $now,
          'updated_at'  => $now,
        ]);
      }
    }

    // INSURANCE_ENTITY — sin parent
    $insuranceEntities = ['SANTANDER', 'LA POSITIVA', 'MAPFRE', 'PACIFICO', 'RIMAC', 'QUALITAS'];
    foreach ($insuranceEntities as $entity) {
      DB::table('ap_masters')->insert([
        'code'        => strtoupper(str_replace(' ', '_', $entity)),
        'description' => $entity,
        'type'        => 'INSURANCE_ENTITY',
        'parent_id'   => null,
        'status'      => 1,
        'created_at'  => $now,
        'updated_at'  => $now,
      ]);
    }
  }
}
