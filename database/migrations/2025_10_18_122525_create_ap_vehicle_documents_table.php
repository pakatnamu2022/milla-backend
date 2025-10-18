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
    Schema::create('ap_vehicle_documents', function (Blueprint $table) {
      $table->id();
      $table->string('document_type', 100);
      $table->string('issuer_type', 100)->comment('Representa quien emitio el documento');
      $table->string('document_series', 20)->nullable();
      $table->string('document_number', 50)->nullable();
      $table->date('issue_date')->nullable()->comment('Se puede usar para fecha de translado o fecha de emisión del documento');
      $table->boolean('requires_sunat')->default(false); // ¿Se declara en SUNAT?
      $table->boolean('is_sunat_registered')->default(false); // ¿Ya se envió a SUNAT?
      $table->foreignId('vehicle_movement_id')->constrained('ap_vehicle_movement')->onDelete('cascade');
      $table->foreignId('transmitter_id')->constrained('business_partners')->onDelete('cascade');
      $table->foreignId('receiver_id')->constrained('business_partners')->onDelete('cascade');
      // Archivo en DigitalOcean
      $table->string('file_path')->nullable(); // Ruta en DO Spaces
      $table->string('file_name')->nullable();
      $table->string('file_type')->nullable(); // pdf, jpg, png, xml
      $table->string('file_url')->nullable(); // URL pública temporal
      // Datos del transporte (asociados a la guía de remisión)
      $table->string('num_doc')->nullable();
      $table->string('company_name')->nullable();
      // Cancelación
      $table->text('cancellation_reason')->nullable();
      $table->foreignId('cancelled_by')->nullable()->constrained('users');
      $table->datetime('cancelled_at')->nullable();
      $table->text('notes')->nullable();
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_vehicle_documents');
  }
};
