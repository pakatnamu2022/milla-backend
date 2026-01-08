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
      $table->string('correlative', 20)->unique()->nullable()->comment('Correlativo generado automáticamente al aprobar la cotización');
      $table->enum('type_document', ['COTIZACION', 'SOLICITUD_COMPRA']);
      $table->date('quote_deadline')->nullable();
      $table->decimal('base_selling_price', 12, 4)->default(0);
      $table->decimal('sale_price', 12, 4)->default(0);
      $table->decimal('doc_sale_price', 12, 4)->default(0);
      $table->string('comment', 255)->nullable();
      $table->boolean('is_invoiced')->default(false)->comment('Indica si la cotización ha sido facturada para no poder editarla o eliminarla');
      $table->boolean('is_approved')->default(false)->comment('Indica si la cotización ha sido aprobada para generar la solicitud de compra');
      $table->string('warranty', 100)->nullable();
      $table->foreignId('type_currency_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('opportunity_id')->nullable()
        ->constrained('ap_opportunity')->onDelete('cascade');
      $table->foreignId('holder_id')
        ->constrained('business_partners')->onDelete('cascade');
      $table->foreignId('vehicle_color_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('ap_models_vn_id')->nullable()
        ->constrained('ap_models_vn')->onDelete('cascade');
      $table->foreignId('ap_vehicle_purchase_order_id')->nullable()
        ->constrained('ap_vehicle_purchase_order')->onDelete('cascade');
      $table->foreignId('doc_type_currency_id')
        ->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('exchange_rate_id')->nullable()
        ->constrained('exchange_rate')->onDelete('cascade');
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
