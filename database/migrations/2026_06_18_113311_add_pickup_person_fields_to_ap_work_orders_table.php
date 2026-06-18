<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->string('num_doc_pickup', 20)->nullable()->after('phone_contact')->comment('Número de documento de quien recoge el vehículo');
      $table->string('full_pickup_name')->nullable()->after('num_doc_pickup')->comment('Nombre completo de quien recoge el vehículo');
      $table->string('phone_pickup')->nullable()->after('full_pickup_name')->comment('Teléfono de quien recoge el vehículo');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropColumn(['num_doc_pickup', 'full_pickup_name', 'phone_pickup']);
    });
  }
};
