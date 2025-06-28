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
        Schema::table('help_equipos', function (Blueprint $table) {
            $table->string('marca')->nullable()->after('tipo_equipo_id');
            $table->string('modelo')->nullable()->after('marca');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('help_equipos', function (Blueprint $table) {
            $table->dropColumn(['marca', 'modelo']);
        });
    }
};
