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
        Schema::table('phone_line_worker', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('assigned_at')->comment('Estado activo de la asignación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_line_worker', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
