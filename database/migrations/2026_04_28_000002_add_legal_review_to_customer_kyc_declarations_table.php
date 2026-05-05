<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('customer_kyc_declarations', function (Blueprint $table) {
      // Estados: PENDIENTE, GENERADO, FIRMADO, CONFIRMADO_LEGAL, RECHAZADO_LEGAL
      $table->string('legal_review_status')->nullable()->after('signed_file_path');

      // Comentarios de rechazo o aprobación
      $table->text('legal_review_comments')->nullable()->after('legal_review_status');

      // Usuario que realizó la revisión legal
      $table->integer('reviewed_by')->nullable()->after('legal_review_comments');
      $table->foreign('reviewed_by')->references('id')->on('usr_users')->nullOnDelete();

      // Fecha de la revisión legal
      $table->timestamp('legal_review_at')->nullable()->after('reviewed_by');
    });
  }

  public function down(): void
  {
    Schema::table('customer_kyc_declarations', function (Blueprint $table) {
      $table->dropForeign(['reviewed_by']);
      $table->dropColumn(['legal_review_status', 'legal_review_comments', 'reviewed_by', 'legal_review_at']);
    });
  }
};

