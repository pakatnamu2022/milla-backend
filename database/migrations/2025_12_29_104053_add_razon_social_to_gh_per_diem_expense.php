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
        Schema::table('gh_per_diem_expense', function (Blueprint $table) {
            $table->string('razon_social', 255)->nullable()->after('ruc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_per_diem_expense', function (Blueprint $table) {
            $table->dropColumn('razon_social');
        });
    }
};
