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
        Schema::table('gh_per_diem_request', function (Blueprint $table) {
          $table->foreignId('company_service_id')->nullable()->after('company_id')->constrained('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_per_diem_request', function (Blueprint $table) {
          $table->dropForeign(['company_service_id']);
          $table->dropColumn('company_service_id');
        });
    }
};
