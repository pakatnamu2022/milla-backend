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
      Schema::table('ap_grupo_marca', function (Blueprint $table) {
        $table->softDeletes();
        $table->dropColumn('status_deleted');
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('ap_grupo_marca', function (Blueprint $table) {
        $table->dropSoftDeletes();
        $table->boolean('status_deleted')->default(1);
      });
    }
};
