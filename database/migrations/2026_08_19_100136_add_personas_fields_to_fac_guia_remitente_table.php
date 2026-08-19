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
        Schema::table('fac_guia_remitente', function (Blueprint $table) {
            $table->integer('remitente_id')->nullable()->after('numero');
            $table->integer('destinatario_id')->nullable()->after('remitente_id');
            $table->integer('subcontrata_id')->nullable()->after('destinatario_id');
            $table->integer('pagador_id')->nullable()->after('subcontrata_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fac_guia_remitente', function (Blueprint $table) {
            $table->dropColumn([
                'remitente_id',
                'destinatario_id',
                'subcontrata_id',
                'pagador_id',
            ]);
        });
    }
};
