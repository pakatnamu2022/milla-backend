<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Registro de comunicaciones de baja (anulaciones) enviadas/consultadas en Nubefact.
   * Una fila por operación (generar_anulacion / consultar_anulacion).
   */
  public function up(): void
  {
    Schema::create('ap_billing_electronic_document_cancellations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_billing_electronic_document_id')
        ->constrained('ap_billing_electronic_documents', 'id', 'fk_ed_cancellation_document');
      $table->string('operation', 30)->comment('generar_anulacion | consultar_anulacion');
      $table->string('motivo', 255)->nullable();
      $table->string('codigo_unico', 100)->nullable();

      // Respuesta Nubefact
      $table->boolean('success')->default(false)->comment('La operación fue aceptada por Nubefact (sin errors)');
      $table->unsignedSmallInteger('http_status_code')->nullable();
      $table->unsignedSmallInteger('error_code')->nullable()->comment('Código de error Nubefact (10, 20, 24...)');
      $table->text('error_message')->nullable();
      $table->unsignedInteger('numero')->nullable()->comment('Número de la comunicación de baja generada');
      $table->string('enlace', 500)->nullable();
      $table->string('sunat_ticket_numero', 100)->nullable();
      $table->boolean('aceptada_por_sunat')->nullable();
      $table->text('sunat_description')->nullable();
      $table->text('sunat_note')->nullable();
      $table->string('sunat_responsecode', 20)->nullable();
      $table->text('sunat_soap_error')->nullable();
      $table->string('enlace_del_pdf', 500)->nullable();
      $table->string('enlace_del_xml', 500)->nullable();
      $table->string('enlace_del_cdr', 500)->nullable();

      $table->json('request_payload')->nullable();
      $table->json('response_payload')->nullable();

      $table->integer('user_id')->nullable(); // usr_users.id es INT
      $table->foreign('user_id', 'fk_ed_cancellation_user')->references('id')->on('usr_users');
      $table->timestamps();

      $table->index(['ap_billing_electronic_document_id', 'operation'], 'idx_ed_cancellation_doc_op');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_billing_electronic_document_cancellations');
  }
};
