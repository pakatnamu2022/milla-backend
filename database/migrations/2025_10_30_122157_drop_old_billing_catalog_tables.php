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
        // Primero, eliminar las foreign keys de las tablas que las referencian

        // Eliminar foreign keys de ap_billing_electronic_documents
        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->dropForeign('fk_billing_doc_type');
            $table->dropForeign('fk_billing_trans_type');
            $table->dropForeign('fk_billing_identity_document_type');
            $table->dropForeign('ap_billing_electronic_documents_ap_billing_currency_id_foreign');
            $table->dropForeign('fk_billing_detraction_type');
            $table->dropForeign('fk_billing_credit_note_type');
            $table->dropForeign('fk_billing_debit_note_type');
        });

        // Eliminar foreign key de ap_billing_electronic_document_items
        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            $table->dropForeign('fk_billing_igv_type');
        });

        // Ahora sí, eliminar las tablas antiguas de catálogos
        Schema::dropIfExists('ap_billing_currencies');
        Schema::dropIfExists('ap_billing_debit_note_types');
        Schema::dropIfExists('ap_billing_credit_note_types');
        Schema::dropIfExists('ap_billing_detraction_types');
        Schema::dropIfExists('ap_billing_igv_types');
        Schema::dropIfExists('ap_billing_identity_document_types');
        Schema::dropIfExists('ap_billing_transaction_types');
        Schema::dropIfExists('ap_billing_document_types');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Si necesitas hacer rollback, tendrías que recrear las tablas
        // Pero no es recomendable, mejor usar la tabla sunat_concepts

        // Tabla de catálogos SUNAT - Tipos de comprobante
        Schema::create('ap_billing_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('1=Factura, 2=Boleta, 3=NC, 4=ND');
            $table->string('description', 100);
            $table->string('prefix', 1)->comment('F=Factura, B=Boleta');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de catálogos SUNAT - Tipos de operación
        Schema::create('ap_billing_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('Código SUNAT');
            $table->string('description', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de catálogos SUNAT - Tipos de documento de identidad
        Schema::create('ap_billing_identity_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1)->unique()->comment('6=RUC, 1=DNI, -=Varios, etc.');
            $table->string('description', 100);
            $table->integer('length')->nullable()->comment('Longitud del documento');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de catálogos SUNAT - Tipos de afectación IGV
        Schema::create('ap_billing_igv_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('Código SUNAT');
            $table->string('description', 255);
            $table->string('tribute_code', 4)->nullable()->comment('Código de tributo');
            $table->boolean('affects_total')->default(true)->comment('Si afecta al total del comprobante');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de catálogos SUNAT - Tipos de nota de crédito
        Schema::create('ap_billing_credit_note_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique()->comment('Código SUNAT');
            $table->string('description', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de catálogos SUNAT - Tipos de nota de débito
        Schema::create('ap_billing_debit_note_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1)->unique()->comment('Código SUNAT');
            $table->string('description', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de catálogos SUNAT - Monedas
        Schema::create('ap_billing_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1)->unique()->comment('1=Soles, 2=Dólares, 3=Euros, 4=Libras');
            $table->string('iso_code', 3)->comment('PEN, USD, EUR, GBP');
            $table->string('description', 100);
            $table->string('symbol', 5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de catálogos SUNAT - Tipos de detracción
        Schema::create('ap_billing_detraction_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique()->comment('001-099');
            $table->string('description', 255);
            $table->decimal('percentage', 5, 3)->comment('Porcentaje de detracción');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
