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
            $table->string('slug')->nullable()->after('descripcion');
            $table->string('icon')->nullable()->after('icono');
            $table->string('route')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_vista', function (Blueprint $table) {
            $table->dropColumn(['slug', 'icon', 'route']);
        });
    }
};
