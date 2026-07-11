<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->unsignedBigInteger('electronic_document_id')->nullable()->after('synced_at');
            $table->unsignedBigInteger('area_id')->nullable()->after('electronic_document_id');
            $table->foreign('electronic_document_id')
                ->references('id')
                ->on('ap_billing_electronic_documents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounts_receivable', function (Blueprint $table) {
            $table->dropForeign(['electronic_document_id']);
            $table->dropColumn('electronic_document_id');
            $table->dropColumn('area_id');
        });
    }
};
