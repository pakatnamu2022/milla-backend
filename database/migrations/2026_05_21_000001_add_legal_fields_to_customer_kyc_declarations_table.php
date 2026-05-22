<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('customer_kyc_declarations', function (Blueprint $table) {
      // Discriminador de tipo — default NATURAL para no romper registros existentes
      $table->string('person_type')->default('NATURAL')->after('sede_id');

      // ── Campos exclusivos de PERSONA JURÍDICA ────────────────────────────

      // Campo 1 — Denominación o razón social
      $table->string('company_name')->nullable()->after('person_type');

      // Campo 2 — RUC / Registro equivalente
      $table->string('ruc')->nullable()->after('company_name');
      $table->string('foreign_registry_number')->nullable()->after('ruc');

      // Campo 3 — Objeto social / actividad económica
      $table->text('business_purpose')->nullable()->after('foreign_registry_number');

      // Campo 4 — Beneficiarios Finales (D.Leg. N° 1372)
      $table->text('final_beneficiaries')->nullable()->after('business_purpose');

      // Campo 6 — Representante (Ejecutante)
      // Valores rep_doc_type: DNI, PASAPORTE, CARNE_EXTRANJERIA, OTRO
      $table->string('rep_full_name')->nullable()->after('purpose_relationship');
      $table->string('rep_doc_type')->nullable()->after('rep_full_name');
      $table->string('rep_doc_number')->nullable()->after('rep_doc_type');
      $table->string('rep_doc_other')->nullable()->after('rep_doc_number');
      // Valores: PODER, MANDATO
      $table->string('rep_representation_type')->nullable()->after('rep_doc_other');

      // Instrumento público notarial
      // Valores: ESCRITURA_PUBLICA, COPIA_CERTIFICADA_ACTA, OTROS
      $table->string('rep_instrument_type')->nullable()->after('rep_representation_type');
      $table->date('rep_escritura_date')->nullable()->after('rep_instrument_type');
      $table->string('rep_notary_name')->nullable()->after('rep_escritura_date');
      $table->date('rep_acta_certified_date')->nullable()->after('rep_notary_name');
      $table->date('rep_acta_date')->nullable()->after('rep_acta_certified_date');
      $table->string('rep_instrument_other')->nullable()->after('rep_acta_date');

      // Inscripción registral
      $table->string('rep_registry_partition')->nullable()->after('rep_instrument_other');
      $table->string('rep_registry_seat')->nullable()->after('rep_registry_partition');
      $table->string('rep_registry_section')->nullable()->after('rep_registry_seat');
      $table->string('rep_registry_zone')->nullable()->after('rep_registry_section');

      // Campo 7 — Dirección de la oficina o local principal
      // Valores office_street_type: JR, AV, CALLE, PASAJE, OVALO
      $table->string('office_street_type')->nullable()->after('rep_registry_zone');
      $table->string('office_street_name')->nullable()->after('office_street_type');
      $table->string('office_number')->nullable()->after('office_street_name');
      $table->string('office_int_number')->nullable()->after('office_number');
      $table->string('office_urbanization')->nullable()->after('office_int_number');
      $table->foreignId('office_district_id')->nullable()->after('office_urbanization')->constrained('district')->nullOnDelete();
      $table->string('office_phone')->nullable()->after('office_district_id');

      // Campo 9 — Número de cuenta / billetera de activos virtuales
      $table->string('account_number')->nullable()->after('entity_final_beneficiary');
    });
  }

  public function down(): void
  {
    Schema::table('customer_kyc_declarations', function (Blueprint $table) {
      $table->dropForeign(['office_district_id']);
      $table->dropColumn([
        'person_type',
        'company_name', 'ruc', 'foreign_registry_number', 'business_purpose', 'final_beneficiaries',
        'rep_full_name', 'rep_doc_type', 'rep_doc_number', 'rep_doc_other', 'rep_representation_type',
        'rep_instrument_type', 'rep_escritura_date', 'rep_notary_name',
        'rep_acta_certified_date', 'rep_acta_date', 'rep_instrument_other',
        'rep_registry_partition', 'rep_registry_seat', 'rep_registry_section', 'rep_registry_zone',
        'office_street_type', 'office_street_name', 'office_number', 'office_int_number',
        'office_urbanization', 'office_district_id', 'office_phone',
        'account_number',
      ]);
    });
  }
};
