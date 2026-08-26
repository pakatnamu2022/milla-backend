<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->boolean('has_gps_hunter')->default(false)->after('insurance_entity_id');
    });

    // Agrega la opción "CONTADO" a los tipos de crédito (CREDIT_TYPE) si no existe,
    // para que el usuario pueda declarar explícitamente que la venta no está financiada.
    $exists = DB::table('ap_masters')
      ->where('type', 'CREDIT_TYPE')
      ->where('code', 'CONTADO')
      ->exists();

    if (!$exists) {
      DB::table('ap_masters')->insert([
        'code'        => 'CONTADO',
        'description' => 'CONTADO',
        'type'        => 'CREDIT_TYPE',
        'parent_id'   => null,
        'status'      => 1,
        'created_at'  => now(),
        'updated_at'  => now(),
      ]);
    }
  }

  public function down(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropColumn('has_gps_hunter');
    });

    DB::table('ap_masters')
      ->where('type', 'CREDIT_TYPE')
      ->where('code', 'CONTADO')
      ->delete();
  }
};
