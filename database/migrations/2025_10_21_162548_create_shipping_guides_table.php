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
    Schema::create('shipping_guides', function (Blueprint $table) {
      $table->id();
      $table->string('document_type', 100);
      $table->string('issuer_type', 100)->comment('Representa quien emitio el documento');
      $table->foreignId('document_series_id')->constrained('assign_sales_series')->onDelete('cascade');
      $table->string('document_number', 50)->nullable();
      $table->date('issue_date')->nullable()->comment('Se puede usar para fecha de translado o fecha de emisión del documento');
      $table->boolean('requires_sunat')->default(false); // ¿Se declara en SUNAT?
      $table->boolean('is_sunat_registered')->default(false); // ¿Ya se envió a SUNAT?
      $table->integer('total_packages')->nullable();
      $table->decimal('total_weight', 12, 4)->nullable();
      $table->foreignId('vehicle_movement_id')->constrained('ap_vehicle_movement')->onDelete('cascade');
      $table->integer('sede_transmitter_id');
      $table->foreign('sede_transmitter_id')->references('id')->on('config_sede');
      $table->integer('sede_receiver_id');
      $table->foreign('sede_receiver_id')->references('id')->on('config_sede');
      $table->foreignId('transmitter_id')->constrained('business_partners_establishment')->onDelete('cascade');
      $table->foreignId('receiver_id')->constrained('business_partners_establishment')->onDelete('cascade');
      // Archivo en DigitalOcean
      $table->string('file_path')->nullable(); // Ruta en DO Spaces
      $table->string('file_name')->nullable();
      $table->string('file_type')->nullable(); // pdf, jpg, png, xml
      $table->string('file_url')->nullable(); // URL pública temporal
      // Datos del transporte (asociados a la guía de remisión)
      $table->foreignId('transport_company_id')->constrained('business_partners')->onDelete('cascade');
      $table->string('driver_doc')->nullable();
      $table->string('license')->nullable();
      $table->string('plate')->nullable();
      $table->string('driver_name')->nullable();
      $table->integer('created_by')->nullable();
      $table->text('notes')->nullable();
      $table->boolean('status')->default(true);
      $table->foreignId('transfer_reason_id')->nullable()
        ->constrained('sunat_concepts')->onDelete('cascade');
      $table->foreignId('transfer_modality_id')->nullable()
        ->constrained('sunat_concepts')->onDelete('cascade');
      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users');
      // Info de recepcion
      $table->boolean('is_received')->default(false);
      $table->string('note_received', 250)->nullable();
      $table->integer('received_by')->nullable();
      $table->date('received_date')->nullable();
      // Cancelación
      $table->text('cancellation_reason')->nullable();
      $table->integer('cancelled_by')->nullable();
      $table->foreign('cancelled_by')
        ->references('id')
        ->on('usr_users');
      $table->datetime('cancelled_at')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('shipping_guides');
  }
};
