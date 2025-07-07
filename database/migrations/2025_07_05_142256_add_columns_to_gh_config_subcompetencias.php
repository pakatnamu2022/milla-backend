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
        Schema::table('gh_config_subcompetencias', function (Blueprint $table) {
            $table->text("level1")->nullable();
            $table->text("level2")->nullable();
            $table->text("level3")->nullable();
            $table->text("level4")->nullable();
            $table->text("level5")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_config_subcompetencias', function (Blueprint $table) {
            $table->dropColumn(['level1', 'level2', 'level3', 'level4', 'level5']);
        });
    }
};
