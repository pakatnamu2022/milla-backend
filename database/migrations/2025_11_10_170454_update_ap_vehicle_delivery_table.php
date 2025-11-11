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
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      // eliminamos status_nubefact, status_sunat, status_dynamic
      $table->dropColumn('status_nubefact');
      $table->dropColumn('status_sunat');
      $table->dropColumn('status_dynamic');
      $table->dropColumn('status');
      $table->foreignId('shipping_guide_id')->nullable()->after('vehicle_id')->constrained('shipping_guides')->onDelete('cascade');
      $table->foreignId('vehicle_movement_id')->nullable()->after('shipping_guide_id')->constrained('ap_vehicle_movement')->onDelete('cascade');
      $table->foreignId('ap_class_article_id')->nullable()->after('vehicle_movement_id')
        ->constrained('ap_class_article')
        ->onDelete('cascade');
      $table->foreignId('client_id')->nullable()->after('ap_class_article_id')
        ->constrained('business_partners')
        ->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->boolean('status_nubefact')->default(false)->comment('Indica si la entrega ha sido sincronizada con Nubefact');
      $table->boolean('status_sunat')->default(false)->comment('Indica si la entrega ha sido sincronizada con Sunat');
      $table->boolean('status_dynamic')->default(false)->comment('Indica si la entrega ha sido sincronizada con Dynamic');
      $table->boolean('status')->default(true)->comment('Estado de la entrega: true=activo, false=inactivo');
      $table->dropForeign(['shipping_guide_id']);
      $table->dropColumn('shipping_guide_id');
      $table->dropForeign(['vehicle_movement_id']);
      $table->dropColumn('vehicle_movement_id');
      $table->dropForeign(['ap_class_article_id']);
      $table->dropColumn('ap_class_article_id');
    });
  }
};
