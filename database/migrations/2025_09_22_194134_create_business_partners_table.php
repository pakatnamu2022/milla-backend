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
    Schema::create('business_partners', function (Blueprint $table) {
      $table->id();
      $table->string('first_name')->nullable();
      $table->string('middle_name')->nullable();
      $table->string('paternal_surname')->nullable();
      $table->string('maternal_surname')->nullable();
      $table->string('full_name');
      $table->date('birth_date')->nullable();
      $table->enum('nationality', ['NACIONAL', 'EXTRANJERO']);
      $table->string('num_doc');
      $table->string('spouse_num_doc')->nullable();
      $table->string('spouse_full_name')->nullable();
      $table->string('direction');
      // representante legal
      $table->string('legal_representative_num_doc')->nullable();
      $table->string('legal_representative_name')->nullable();
      $table->string('legal_representative_paternal_surname')->nullable();
      $table->string('legal_representative_maternal_surname')->nullable();
      $table->string('legal_representative_full_name')->nullable();
      //contactos
      $table->string('email')->nullable();
      $table->string('secondary_email')->nullable();
      $table->string('phone')->nullable();
      $table->string('secondary_phone')->nullable();
      $table->string('secondary_phone_contact_name')->nullable();
      //numero de carnet de conducir
      $table->string('driver_num_doc')->nullable();
      $table->string('driver_full_name')->nullable();
      $table->string('driving_license')->nullable();
      $table->date('driving_license_issue_date')->nullable();
      $table->date('driving_license_expiration_date')->nullable();
      $table->string('status_license')->nullable();
      $table->string('restriction')->nullable();
      $table->boolean('status_gp')->default(false);
      $table->boolean('status_ap')->default(false);
      $table->boolean('status_tp')->default(false);
      $table->boolean('status_dp')->default(false);
      $table->string('company_status')->nullable();
      $table->string('company_condition')->nullable();
      $table->foreignId('origin_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('driving_license_type_id')->nullable()
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('tax_class_type_id')
        ->constrained('tax_class_types')->onDelete('cascade');
      $table->foreignId('type_road_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('type_person_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('district_id')
        ->constrained('district')->onDelete('cascade');
      $table->foreignId('document_type_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('person_segment_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('marital_status_id')->nullable()
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('gender_id')->nullable()
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('activity_economic_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('company_id')
        ->constrained('companies')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('business_partners');
  }
};
