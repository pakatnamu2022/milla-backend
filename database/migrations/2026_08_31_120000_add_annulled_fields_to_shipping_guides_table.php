<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * Campos propios de la anulación de una guía de remisión, separados del
   * flujo de cancelación (cancelled_at / cancelled_by / cancellation_reason),
   * que sí implica reversión en Dynamics. La anulación solo deja la guía
   * fuera de circulación (is_annulled = true, status = false).
   */
  public function up(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->timestamp('annulled_at')->nullable()->after('cancelled_at');
      $table->unsignedBigInteger('annulled_by')->nullable()->after('annulled_at');
      $table->text('annulled_reason')->nullable()->after('annulled_by');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->dropColumn(['annulled_at', 'annulled_by', 'annulled_reason']);
    });
  }
};
