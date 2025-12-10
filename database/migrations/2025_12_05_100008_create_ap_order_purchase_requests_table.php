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
    Schema::create('ap_order_purchase_requests', function (Blueprint $table) {
      $table->id();

      // Unique request number
      $table->string('request_number')->unique()->comment('Número único de la solicitud de compra');

      // Relations
      $table->foreignId('ap_order_quotation_id')->nullable()->comment('De la cotización aprobada')
        ->constrained('ap_order_quotations')->onDelete('cascade');

      $table->foreignId('purchase_order_id')->nullable()->comment('Orden de compra cuando se genere')
        ->constrained('ap_purchase_order')->onDelete('set null');

      $table->foreignId('warehouse_id')->comment('Almacén desde donde se surtirá el producto')
        ->constrained('warehouse')->onDelete('cascade');

      // Dates tracking
      $table->date('requested_date')->comment('Fecha de solicitud');
      $table->date('ordered_date')->nullable()->comment('Fecha en que se generó la orden de compra');
      $table->date('received_date')->nullable()->comment('Fecha de recepción de repuestos');

      // Notification tracking
      $table->boolean('advisor_notified')->default(false)
        ->comment('Si ya se notificó al asesor');
      $table->dateTime('notified_at')->nullable()
        ->comment('Cuándo se notificó al asesor');

      // Notes
      $table->text('observations')->nullable()->comment('Observaciones');

      // Status
      $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index('received_date');
      $table->index('advisor_notified');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_order_purchase_requests');
  }
};
