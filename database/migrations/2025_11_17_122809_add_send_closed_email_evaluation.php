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
    Schema::table('gh_evaluation', function (Blueprint $table) {
      $table->boolean('send_opened_email')->default(false)->after('competencesPercentage')->comment('Indica si se debe enviar un correo al abrir la evaluacion');
      $table->boolean('send_closed_email')->default(false)->after('send_opened_email')->comment('Indica si se debe enviar un correo al cerrar la evaluacion');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_evaluation', function (Blueprint $table) {
      $table->dropColumn('send_opened_email');
      $table->dropColumn('send_closed_email');
    });
  }
};
