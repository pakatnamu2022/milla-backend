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
        Schema::table('scrum_items', function (Blueprint $table) {
            $table->foreignId('predecessor_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('scrum_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scrum_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('predecessor_id');
        });
    }
};
