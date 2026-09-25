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
        Schema::table('scrum_projects', function (Blueprint $table) {
            $table->decimal('hourly_cost', 10, 2)->default(10)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scrum_projects', function (Blueprint $table) {
            $table->dropColumn('hourly_cost');
        });
    }
};
