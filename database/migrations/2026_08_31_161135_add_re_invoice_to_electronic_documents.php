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
        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->boolean('re_invoice')->default(false)->comment('Sirve para saber si esa nota de credito que se esta generando se va volvera refacturar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            $table->dropColumn('re_invoice');
        });
    }
};
