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
        // Renombrar columnas en ap_billing_electronic_documents
        // Las foreign keys ya fueron eliminadas por la migración anterior (drop_old_billing_catalog_tables)

        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->renameColumn('ap_billing_document_type_id', 'sunat_concept_document_type_id');
            $table->renameColumn('ap_billing_transaction_type_id', 'sunat_concept_transaction_type_id');
            $table->renameColumn('ap_billing_identity_document_type_id', 'sunat_concept_identity_document_type_id');
            $table->renameColumn('ap_billing_currency_id', 'sunat_concept_currency_id');
            $table->renameColumn('ap_billing_detraction_type_id', 'sunat_concept_detraction_type_id');
            $table->renameColumn('ap_billing_credit_note_type_id', 'sunat_concept_credit_note_type_id');
            $table->renameColumn('ap_billing_debit_note_type_id', 'sunat_concept_debit_note_type_id');
        });

        // Crear las foreign keys con los nuevos nombres
        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->foreign('sunat_concept_document_type_id', 'fk_billing_doc_type')
                ->references('id')->on('sunat_concepts');
            $table->foreign('sunat_concept_transaction_type_id', 'fk_billing_trans_type')
                ->references('id')->on('sunat_concepts');
            $table->foreign('sunat_concept_identity_document_type_id', 'fk_billing_identity_document_type')
                ->references('id')->on('sunat_concepts');
            $table->foreign('sunat_concept_currency_id', 'fk_billing_currency')
                ->references('id')->on('sunat_concepts');
            $table->foreign('sunat_concept_detraction_type_id', 'fk_billing_detraction_type')
                ->references('id')->on('sunat_concepts')->nullOnDelete();
            $table->foreign('sunat_concept_credit_note_type_id', 'fk_billing_credit_note_type')
                ->references('id')->on('sunat_concepts')->nullOnDelete();
            $table->foreign('sunat_concept_debit_note_type_id', 'fk_billing_debit_note_type')
                ->references('id')->on('sunat_concepts')->nullOnDelete();
        });

        // Renombrar columnas en ap_billing_electronic_document_items
        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            $table->renameColumn('ap_billing_igv_type_id', 'sunat_concept_igv_type_id');
        });

        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            $table->foreign('sunat_concept_igv_type_id', 'fk_billing_igv_type')
                ->references('id')->on('sunat_concepts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios en ap_billing_electronic_documents
        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->dropForeign('fk_billing_doc_type');
            $table->dropForeign('fk_billing_trans_type');
            $table->dropForeign('fk_billing_identity_document_type');
            $table->dropForeign('fk_billing_currency');
            $table->dropForeign('fk_billing_detraction_type');
            $table->dropForeign('fk_billing_credit_note_type');
            $table->dropForeign('fk_billing_debit_note_type');
        });

        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->renameColumn('sunat_concept_document_type_id', 'ap_billing_document_type_id');
            $table->renameColumn('sunat_concept_transaction_type_id', 'ap_billing_transaction_type_id');
            $table->renameColumn('sunat_concept_identity_document_type_id', 'ap_billing_identity_document_type_id');
            $table->renameColumn('sunat_concept_currency_id', 'ap_billing_currency_id');
            $table->renameColumn('sunat_concept_detraction_type_id', 'ap_billing_detraction_type_id');
            $table->renameColumn('sunat_concept_credit_note_type_id', 'ap_billing_credit_note_type_id');
            $table->renameColumn('sunat_concept_debit_note_type_id', 'ap_billing_debit_note_type_id');
        });

        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->foreign('ap_billing_document_type_id', 'fk_billing_doc_type')
                ->references('id')->on('sunat_concepts');
            $table->foreign('ap_billing_transaction_type_id', 'fk_billing_trans_type')
                ->references('id')->on('sunat_concepts');
            $table->foreign('ap_billing_identity_document_type_id', 'fk_billing_identity_document_type')
                ->references('id')->on('sunat_concepts');
            $table->foreign('ap_billing_currency_id', 'ap_billing_electronic_documents_ap_billing_currency_id_foreign')
                ->references('id')->on('sunat_concepts');
            $table->foreign('ap_billing_detraction_type_id', 'fk_billing_detraction_type')
                ->references('id')->on('sunat_concepts')->nullOnDelete();
            $table->foreign('ap_billing_credit_note_type_id', 'fk_billing_credit_note_type')
                ->references('id')->on('sunat_concepts')->nullOnDelete();
            $table->foreign('ap_billing_debit_note_type_id', 'fk_billing_debit_note_type')
                ->references('id')->on('sunat_concepts')->nullOnDelete();
        });

        // Revertir cambios en ap_billing_electronic_document_items
        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            $table->dropForeign('fk_billing_igv_type');
        });

        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            $table->renameColumn('sunat_concept_igv_type_id', 'ap_billing_igv_type_id');
        });

        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            $table->foreign('ap_billing_igv_type_id', 'fk_billing_igv_type')
                ->references('id')->on('sunat_concepts');
        });
    }
};
