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
        Schema::table('rrhh_persona', function (Blueprint $table) {
            $table->tinyInteger('no_attendance_required')->default(0)->after('supervisor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rrhh_persona', function (Blueprint $table) {
            $table->dropColumn('no_attendance_required');
        });
    }
};
