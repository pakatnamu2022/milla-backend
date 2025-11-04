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
    Schema::create('ap_billing_nubefact_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_billing_electronic_document_id')->nullable()->constrained('ap_billing_electronic_documents', 'id', 'fk_ap_billing_nubefact_logs_documents')
        ->nullOnDelete();
      $table->string('operation', 50); // 'generar_comprobante', 'consultar_comprobante', 'generar_anulacion', 'consultar_anulacion'
      $table->text('request_payload')->nullable();
      $table->text('response_payload')->nullable();
      $table->integer('http_status_code')->nullable();
      $table->boolean('success')->default(false);
      $table->text('error_message')->nullable();
      $table->timestamps();

      $table->index('ap_billing_electronic_document_id');
      $table->index('operation');
      $table->index('success');
      $table->index('created_at');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_billing_nubefact_logs');
  }
};
