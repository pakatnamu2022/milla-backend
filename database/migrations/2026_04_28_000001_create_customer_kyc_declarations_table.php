<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('customer_kyc_declarations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('purchase_request_quote_id')->nullable()->constrained('purchase_request_quote')->nullOnDelete();
      $table->foreignId('business_partner_id')->constrained('business_partners')->cascadeOnDelete();
      $table->integer('sede_id')->nullable();
      $table->foreign('sede_id')->references('id')->on('config_sede')->nullOnDelete();

      // Campo 7 - Ocupación / Cargo
      $table->string('occupation')->nullable();

      // Campo 8 - Teléfono fijo con código de ciudad
      $table->string('fixed_phone')->nullable();

      // Campo 9 - Propósito de la relación con el sujeto obligado
      $table->text('purpose_relationship')->nullable();

      // Campo 10.1 - Estado PEP del declarante
      // Valores: SI_SOY, SI_HE_SIDO, NO_SOY, NO_HE_SIDO
      $table->string('pep_status')->default('NO_SOY');
      $table->string('pep_collaborator_status')->default('NO_HE_SIDO');
      $table->string('pep_position')->nullable();
      $table->string('pep_institution')->nullable();

      // Campo 10.2 - Familiares del PEP (solo si es PEP)
      // JSON: array de nombres y apellidos de parientes
      $table->json('pep_relatives')->nullable();
      $table->string('pep_spouse_name')->nullable();

      // Campo 10.3 - Pariente de PEP
      // Valores: SI_SOY, NO_SOY
      $table->string('is_pep_relative')->default('NO_SOY');
      // JSON: [{ pep_full_name, relationship }]
      $table->json('pep_relative_data')->nullable();

      // Campo 11 - Identidad del beneficiario de la operación
      // Valores: PROPIO, TERCERO_NATURAL, PERSONA_JURIDICA, ENTE_JURIDICO
      $table->string('beneficiary_type')->default('PROPIO');

      // 11.1 - A favor de sí mismo
      $table->text('own_funds_origin')->nullable();

      // 11.2 - A favor de tercero persona natural
      $table->string('third_full_name')->nullable();
      $table->string('third_doc_type')->nullable();
      $table->string('third_doc_number')->nullable();
      // Valores: ESCRITURA_PUBLICA, MANDATO, PODER, OTROS
      $table->string('third_representation_type')->nullable();
      // Valores: SI_ES, SI_HA_SIDO, NO_ES, NO_HA_SIDO
      $table->string('third_pep_status')->nullable();
      $table->string('third_pep_position')->nullable();
      $table->string('third_pep_institution')->nullable();
      $table->text('third_funds_origin')->nullable();

      // 11.3 - A favor de persona jurídica o ente jurídico
      $table->string('entity_name')->nullable();
      $table->string('entity_ruc')->nullable();
      // Valores: PODER_POR_ACTA, ESCRITURA_PUBLICA, MANDATO
      $table->string('entity_representation_type')->nullable();
      $table->text('entity_funds_origin')->nullable();
      $table->string('entity_final_beneficiary')->nullable();

      // Fecha de declaración y estado del flujo
      $table->date('declaration_date');
      // PENDIENTE: creada, sin PDF entregado | GENERADO: PDF generado y entregado | FIRMADO: doc firmado subido
      $table->string('status')->default('PENDIENTE');
      $table->string('signed_file_path')->nullable();

      $table->integer('created_by')->nullable();
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();

      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('customer_kyc_declarations');
  }
};
