<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nubefact_shipping_guide_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_guide_id')->nullable()->constrained('shipping_guides')->cascadeOnDelete();
            $table->enum('operation', ['generar_guia', 'consultar_guia', 'generar_anulacion', 'consultar_anulacion']);
            $table->text('request_payload')->comment('JSON enviado');
            $table->text('response_payload')->nullable()->comment('JSON recibido');
            $table->integer('http_status_code')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nubefact_shipping_guide_logs');
    }
};
