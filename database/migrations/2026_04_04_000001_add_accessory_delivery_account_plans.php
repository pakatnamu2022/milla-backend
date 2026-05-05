<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
  /**
   * Agrega la clase de artículo ACCESORIO_VEHICULO y su mapeo de cuentas contables,
   * siguiendo el mismo patrón que los demás tipos de vehículo en ApClassArticleAccountMappingSeeder.
   *
   * Cuentas para el asiento de entrega de accesorios (solo posventa):
   *   - Origen  (4961700): diferido al momento de vender el accesorio (código Dynamics V0000016)
   *   - Destino (7011118): ingreso que se reconoce al momento de la entrega del vehículo
   *
   * También inserta los planes de cuenta correspondientes en ap_accounting_account_plan.
   */
  public function up(): void
  {
    // 1. Clase de artículo ACCESORIO_VEHICULO
    $classId = DB::table('ap_class_article')
      ->where('dyn_code', 'M_ACC_VEH')
      ->whereNull('deleted_at')
      ->value('id');

    if (!$classId) {
      $classId = DB::table('ap_class_article')->insertGetId([
        'dyn_code'    => 'M_ACC_VEH',
        'description' => 'ACCESORIO VEHICULO',
        'account'     => '4961700',
        'status'      => true,
        'created_at'  => now(),
        'updated_at'  => now(),
      ]);
    }

    // 2. Mapeo PRECIO en ap_class_article_account_mapping
    DB::table('ap_class_article_account_mapping')->updateOrInsert(
      [
        'ap_class_article_id' => $classId,
        'account_type'        => 'PRECIO',
      ],
      [
        'account_origin'      => '4961700',
        'account_destination' => '7011118',
        'is_debit_origin'     => true,
        'status'              => true,
        'created_at'          => now(),
        'updated_at'          => now(),
      ]
    );

    // 3. Planes de cuenta
    $existing4961700 = DB::table('ap_accounting_account_plan')
      ->where('account', '4961700')
      ->whereNull('deleted_at')
      ->exists();

    if (!$existing4961700) {
      DB::table('ap_accounting_account_plan')->insert([
        'account'       => '4961700',
        'code_dynamics' => 'V0000016',
        'description'   => 'ACCESORIOS VEHICULO - CUENTA DIFERIDA ENTREGA',
        'is_detraction' => false,
        'type'          => 0,
        'status'        => true,
        'created_at'    => now(),
        'updated_at'    => now(),
      ]);
    }

    $existing7011118 = DB::table('ap_accounting_account_plan')
      ->where('account', '7011118')
      ->whereNull('deleted_at')
      ->exists();

    if (!$existing7011118) {
      DB::table('ap_accounting_account_plan')->insert([
        'account'       => '7011118',
        'code_dynamics' => '7011118',
        'description'   => 'VENTAS - ACCESORIOS VEHICULO ENTREGADO',
        'is_detraction' => false,
        'type'          => 0,
        'status'        => true,
        'created_at'    => now(),
        'updated_at'    => now(),
      ]);
    }
  }

  public function down(): void
  {
    $classId = DB::table('ap_class_article')
      ->where('dyn_code', 'M_ACC_VEH')
      ->value('id');

    if ($classId) {
      DB::table('ap_class_article_account_mapping')
        ->where('ap_class_article_id', $classId)
        ->where('account_type', 'PRECIO')
        ->delete();

      DB::table('ap_class_article')->where('id', $classId)->delete();
    }

    DB::table('ap_accounting_account_plan')
      ->whereIn('account', ['4961700', '7011118'])
      ->delete();
  }
};
