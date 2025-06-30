<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('config_vista', function (Blueprint $table) {
            $table->boolean('submodule')->nullable()->after('parent_id')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_vista', function (Blueprint $table) {
            $table->dropColumn('submodule');
        });
    }
};
