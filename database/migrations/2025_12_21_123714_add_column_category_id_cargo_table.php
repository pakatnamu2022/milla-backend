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
        Schema::table('rrhh_cargo', function (Blueprint $table) {
            $table->foreignId('per_diem_category_id')->nullable()->after('perfil_id')->default('1')->constrained('gh_per_diem_category')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rrhh_cargo', function (Blueprint $table) {
            $table->dropForeign('per_diem_category_id');
            $table->dropColumn('per_diem_category_id');
        });
    }
};
