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
        Schema::table('config_sede', function (Blueprint $table) {
            $table->string('code_afs', 10)->nullable()->after('dyn_code')->comment('Código AFS de la sede');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_sede', function (Blueprint $table) {
            $table->dropColumn('code_afs');
        });
    }
};
