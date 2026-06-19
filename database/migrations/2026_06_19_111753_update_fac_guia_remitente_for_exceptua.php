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

            $table->integer('grt_id')->nullable()->change();
            $table->boolean('exceptua_transportista')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fac_guia_remitente', function (Blueprint $table) {
            $table->dropColumn('exceptua_transportista');
        });
    }
};
