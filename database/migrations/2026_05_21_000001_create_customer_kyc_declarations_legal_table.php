<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('customer_kyc_declarations_legal', function (Blueprint $table) {
      $table->id();
      $table->foreignId('purchase_request_quote_id')->nullable()->constrained('purchase_request_quote')->nullOnDelete();
      $table->foreignId('business_partner_id')->constrained('business_partners')->cascadeOnDelete();
      $table->integer('sede_id')->nullable();
      $table->foreign('sede_id')->references('id')->on('config_sede')->nullOnDelete();

      // Campo 1 - Denominación o razón social
      $table->string('company_name')->nullable();

      // Campo 2 - RUC / Registro equivalente
      $table->string('ruc')->nullable();
      $table->string('foreign_registry_number')->nullable();

      // Campo 3 - Objeto social / actividad económica principal
      $table->text('business_purpose')->nullable();

      // Campo 4 - Identificación de Beneficiarios Finales (D.Leg. N° 1372)
      $table->text('final_beneficiaries')->nullable();

      // Campo 5 - Propósito de la relación con el sujeto obligado
      $table->text('purpose_relationship')->nullable();

      // Campo 6 - Datos del Representante (Ejecutante)
      $table->string('rep_full_name')->nullable();
      // Valores: DNI, PASAPORTE, CARNE_EXTRANJERIA, OTRO
      $table->string('rep_doc_type')->nullable();
      $table->string('rep_doc_number')->nullable();
      $table->string('rep_doc_other')->nullable();
      // Valores: PODER, MANDATO
      $table->string('rep_representation_type')->nullable();

      // Instrumento público notarial
      // Valores: ESCRITURA_PUBLICA, COPIA_CERTIFICADA_ACTA, OTROS
      $table->string('rep_instrument_type')->nullable();
      $table->date('rep_escritura_date')->nullable();
      $table->string('rep_notary_name')->nullable();
      $table->date('rep_acta_certified_date')->nullable();
      $table->date('rep_acta_date')->nullable();
      $table->string('rep_instrument_other')->nullable();

      // Datos de inscripción registral
      $table->string('rep_registry_partition')->nullable();
      $table->string('rep_registry_seat')->nullable();
      $table->string('rep_registry_section')->nullable();
      $table->string('rep_registry_zone')->nullable();

      // Campo 7.1 - Dirección de la oficina o local principal
      // Valores: JR, AV, CALLE, PASAJE, OVALO
      $table->string('office_street_type')->nullable();
      $table->string('office_street_name')->nullable();
      $table->string('office_number')->nullable();
      $table->string('office_int_number')->nullable();
      $table->string('office_urbanization')->nullable();
      $table->foreignId('office_district_id')->nullable()->constrained('district')->nullOnDelete();

      // Campo 7.2 - Teléfono de la oficina
      $table->string('office_phone')->nullable();

      // Campo 8 - Identidad del beneficiario de la operación
      // Valores: PROPIO, TERCERO_NATURAL, PERSONA_JURIDICA, ENTE_JURIDICO
      $table->string('beneficiary_type')->default('PROPIO');

      // 8.1 - A favor de sí mismo
      $table->text('own_funds_origin')->nullable();

      // 8.2 - A favor de tercero persona natural
      $table->string('third_full_name')->nullable();
      $table->string('third_doc_type')->nullable();
      $table->string('third_doc_number')->nullable();
      // Valores: PODER_ESCRITURA_PUBLICA, MANDATO
      $table->string('third_representation_type')->nullable();
      // Valores: SI_ES, SI_HA_SIDO, NO_ES, NO_HA_SIDO
      $table->string('third_pep_status')->nullable();
      $table->string('third_pep_position')->nullable();
      $table->string('third_pep_institution')->nullable();
      $table->text('third_funds_origin')->nullable();

      // 8.3 - A favor de tercero persona jurídica o ente jurídico
      $table->string('entity_name')->nullable();
      $table->string('entity_ruc')->nullable();
      // Valores: PODER_POR_ACTA, ESCRITURA_PUBLICA, MANDATO
      $table->string('entity_representation_type')->nullable();
      $table->text('entity_funds_origin')->nullable();
      $table->text('entity_final_beneficiary')->nullable();

      // Campo 9 - Número de cuenta / billetera de activos virtuales
      $table->string('account_number')->nullable();

      // Fecha de declaración y estado del flujo
      $table->date('declaration_date');
      // PENDIENTE: creada, sin PDF | GENERADO: PDF generado | FIRMADO: doc firmado subido
      $table->string('status')->default('PENDIENTE');
      $table->string('signed_file_path')->nullable();

      $table->string('legal_review_status')->nullable();
      $table->text('legal_review_comments')->nullable();
      $table->integer('reviewed_by')->nullable();
      $table->foreign('reviewed_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->timestamp('legal_review_at')->nullable();

      $table->integer('created_by')->nullable();
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();

      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('customer_kyc_declarations_legal');
  }
};
