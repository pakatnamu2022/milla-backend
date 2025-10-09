<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new  class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('purchase_request_quote', function (Blueprint $table) {
      $table->id();
      $table->enum('type_document', ['COTIZACION', 'SOLICITUD_COMPRA']);
      $table->enum('type_vehicle', ['NUEVO', 'USADO']);
      $table->date('quote_deadline')->nullable();
      $table->decimal('exchange_rate', 12, 4)->default(1);
      $table->decimal('subtotal', 12, 4)->default(0);
      $table->decimal('total', 12, 4)->default(0);
      $table->string('comment', 255)->nullable();
      $table->foreignId('opportunity_id')->nullable()
        ->constrained('ap_opportunity')->onDelete('cascade');
      $table->foreignId('holder_id')
        ->constrained('business_partners')->onDelete('cascade');
      $table->foreignId('vehicle_color_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('ap_models_vn_id')->nullable()
        ->constrained('ap_models_vn')->onDelete('cascade');
      $table->foreignId('vehicle_vn_id')->nullable()
        ->constrained('vehicle_vn')->onDelete('cascade');
      $table->foreignId('doc_type_currency_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('purchase_request_quote');
  }
};
