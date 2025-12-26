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
        Schema::table('gh_request_budget', function (Blueprint $table) {
            $table->decimal('daily_amount', 8, 2)->nullable()->change();
            $table->integer('days')->nullable()->change();
            $table->decimal('total', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_request_budget', function (Blueprint $table) {
            $table->decimal('daily_amount', 8, 2)->nullable(false)->change();
            $table->integer('days')->nullable(false)->change();
            $table->decimal('total', 8, 2)->nullable(false)->change();
        });
    }
};
