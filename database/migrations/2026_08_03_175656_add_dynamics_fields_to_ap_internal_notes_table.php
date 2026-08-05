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
        Schema::table('ap_internal_notes', function (Blueprint $table) {
            $table->string('dyn_series', 50)->nullable()->after('status')->comment('Serie generada para Dynamics (NIP-00001)');
            $table->string('migration_status', 20)->default('pending')->after('dyn_series')->comment('Estado de migración a Dynamics: pending, in_progress, completed, failed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_internal_notes', function (Blueprint $table) {
            $table->dropColumn(['dyn_series', 'migration_status']);
        });
    }
};
