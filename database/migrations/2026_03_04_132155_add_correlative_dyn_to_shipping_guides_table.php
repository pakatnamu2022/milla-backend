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
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->dropColumn('correlative_dyn');
      $table->boolean('is_accounted')->default(0)
        ->comment('Indica si el documento está contabilizado en Dynamics 365 (encontrado en SOP30200)')
        ->after('dyn_series');
      $table->boolean('is_annulled')->default(0)
        ->comment('Indica si el documento está anulado en Dynamics 365 (VOIDSTTS = 1)')
        ->after('is_accounted');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->string('correlative_dyn', 20)->nullable()->after('correlative');
      $table->dropColumn(['is_accounted', 'is_annulled']);
    });
  }
};
