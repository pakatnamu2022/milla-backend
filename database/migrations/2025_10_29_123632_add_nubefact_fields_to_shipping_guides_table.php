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
        Schema::table('shipping_guides', function (Blueprint $table) {
            // Respuesta de Nubefact
            $table->string('enlace', 500)->nullable()->after('status_nubefac');
            $table->string('enlace_del_pdf', 500)->nullable();
            $table->string('enlace_del_xml', 500)->nullable();
            $table->string('enlace_del_cdr', 500)->nullable();
            $table->boolean('aceptada_por_sunat')->nullable();
            $table->text('sunat_description')->nullable();
            $table->text('sunat_note')->nullable();
            $table->string('sunat_responsecode', 10)->nullable();
            $table->text('sunat_soap_error')->nullable();
            $table->string('cadena_para_codigo_qr', 500)->nullable();
            $table->string('codigo_hash', 100)->nullable();

            // Estado interno
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipping_guides', function (Blueprint $table) {
            $table->dropColumn([
                'enlace',
                'enlace_del_pdf',
                'enlace_del_xml',
                'enlace_del_cdr',
                'aceptada_por_sunat',
                'sunat_description',
                'sunat_note',
                'sunat_responsecode',
                'sunat_soap_error',
                'cadena_para_codigo_qr',
                'codigo_hash',
                'error_message',
                'sent_at',
                'accepted_at',
            ]);
        });
    }
};
